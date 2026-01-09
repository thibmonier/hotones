<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\BlogPost;
use App\Entity\User;
use App\Exception\BlogImageGenerationException;
use App\Security\CompanyContext;
use App\Service\AI\BlogImageGenerationService;
use App\Service\SecureFileUploadService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Override;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * CRUD controller for blog posts (multi-tenant, ROLE_ADMIN).
 */
class BlogPostCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly CompanyContext $companyContext,
        private readonly BlogImageGenerationService $imageGenerationService,
        private readonly SecureFileUploadService $uploadService,
        private readonly RequestStack $requestStack
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return BlogPost::class;
    }

    #[Override]
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Article de blog')
            ->setEntityLabelInPlural('Articles de blog')
            ->setSearchFields(['title', 'content', 'excerpt'])
            ->setDefaultSort(['publishedAt' => 'DESC', 'createdAt' => 'DESC'])
            ->setPaginatorPageSize(25)
            ->showEntityActionsInlined();
    }

    #[Override]
    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->hideOnForm();

        yield TextField::new('title', 'Titre')
            ->setRequired(true)
            ->setHelp('Titre de l\'article');

        yield SlugField::new('slug', 'Slug')
            ->setTargetFieldName('title')
            ->setHelp('URL-friendly identifier (auto-généré depuis le titre)')
            ->hideOnIndex();

        yield ChoiceField::new('status', 'Statut')
            ->setChoices(BlogPost::STATUS_OPTIONS)
            ->setRequired(true)
            ->renderAsBadges([
                BlogPost::STATUS_DRAFT     => 'warning',
                BlogPost::STATUS_PUBLISHED => 'success',
                BlogPost::STATUS_ARCHIVED  => 'secondary',
            ]);

        yield TextEditorField::new('content', 'Contenu')
            ->setHelp('Contenu complet de l\'article (HTML supporté)')
            ->hideOnIndex();

        yield TextareaField::new('excerpt', 'Extrait')
            ->setMaxLength(500)
            ->setHelp('Résumé court (max 500 caractères). Si vide, sera auto-généré depuis le contenu.')
            ->hideOnIndex();

        // === IMAGE SECTION ===
        yield ChoiceField::new('imageSource', 'Source de l\'image')
            ->setChoices(BlogPost::IMAGE_SOURCE_OPTIONS)
            ->setRequired(true)
            ->setHelp('Choisissez comment fournir l\'image à la une')
            ->hideOnIndex();

        yield UrlField::new('featuredImage', 'URL externe')
            ->setHelp('URL complète de l\'image (ex: https://example.com/image.jpg)')
            ->hideOnIndex()
            ->setFormTypeOption('attr', ['data-image-field' => 'external']);

        yield TextareaField::new('imagePrompt', 'Prompt pour l\'IA')
            ->setMaxLength(1000)
            ->setHelp('Décrivez l\'image souhaitée en détail (10-1000 caractères). Ex: "A modern minimalist office with plants and natural light"')
            ->hideOnIndex()
            ->setFormTypeOption('attr', ['data-image-field' => 'ai_generated', 'rows' => 3]);

        // Metadata fields (readonly)
        yield DateTimeField::new('imageGeneratedAt', 'Image générée le')
            ->hideOnForm()
            ->hideOnIndex();

        yield TextField::new('imageModel', 'Modèle IA utilisé')
            ->hideOnForm()
            ->hideOnIndex();

        yield AssociationField::new('category', 'Catégorie')
            ->setHelp('Catégorie principale de l\'article')
            ->setRequired(false);

        yield AssociationField::new('tags', 'Tags')
            ->setHelp('Tags associés à l\'article')
            ->setFormTypeOption('by_reference', false)
            ->setFormTypeOption('multiple', true)
            ->hideOnIndex();

        yield AssociationField::new('author', 'Auteur')
            ->setHelp('Auteur de l\'article (auto-assigné si non spécifié)')
            ->hideOnForm()
            ->formatValue(fn ($value, BlogPost $entity) => $entity->getAuthor()->getFullName());

        yield DateTimeField::new('publishedAt', 'Date de publication')
            ->setHelp('Date de publication (auto-définie lors du passage en "Publié")')
            ->hideOnIndex();

        yield DateTimeField::new('createdAt', 'Créé le')
            ->hideOnForm();

        yield DateTimeField::new('updatedAt', 'Modifié le')
            ->hideOnForm()
            ->hideOnIndex();
    }

    #[Override]
    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('status', 'Statut')->setChoices([
                'Brouillon' => BlogPost::STATUS_DRAFT,
                'Publié'    => BlogPost::STATUS_PUBLISHED,
                'Archivé'   => BlogPost::STATUS_ARCHIVED,
            ]))
            ->add(EntityFilter::new('category', 'Catégorie'))
            ->add(EntityFilter::new('tags', 'Tags'))
            ->add(EntityFilter::new('author', 'Auteur'))
            ->add(DateTimeFilter::new('publishedAt', 'Date de publication'))
            ->add(DateTimeFilter::new('createdAt', 'Date de création'));
    }

    #[Override]
    public function configureActions(Actions $actions): Actions
    {
        // Custom action: Regenerate AI image
        $regenerateImage = Action::new('regenerateImage', 'Régénérer l\'image IA')
            ->linkToCrudAction('regenerateImageAction')
            ->setIcon('fa fa-refresh')
            ->displayIf(static fn (BlogPost $post) => $post->getImageSource() === BlogPost::IMAGE_SOURCE_AI_GENERATED
                && $post->getImagePrompt() !== null)
            ->addCssClass('btn btn-warning');

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_EDIT, $regenerateImage)
            ->add(Crud::PAGE_DETAIL, $regenerateImage)
            ->setPermission(Action::INDEX, 'ROLE_ADMIN')
            ->setPermission(Action::NEW, 'ROLE_ADMIN')
            ->setPermission(Action::EDIT, 'ROLE_ADMIN')
            ->setPermission(Action::DELETE, 'ROLE_ADMIN')
            ->setPermission(Action::DETAIL, 'ROLE_ADMIN');
    }

    /**
     * Create a new BlogPost with author and company pre-assigned.
     * This is called BEFORE the form is created, ensuring validation passes.
     */
    #[Override]
    public function createEntity(string $entityFqcn): BlogPost
    {
        $post = new BlogPost();

        // Auto-assign author (current user)
        $currentUser = $this->getUser();
        if ($currentUser instanceof User) {
            $post->setAuthor($currentUser);
        }

        // Auto-assign company from context
        $post->setCompany($this->companyContext->getCurrentCompany());

        return $post;
    }

    /**
     * Auto-set publishedAt when status is published and handle image generation/upload.
     */
    #[Override]
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof BlogPost) {
            parent::persistEntity($entityManager, $entityInstance);

            return;
        }

        // Auto-set publishedAt if status is published and publishedAt is null
        if ($entityInstance->getStatus() === BlogPost::STATUS_PUBLISHED && $entityInstance->getPublishedAt() === null) {
            $entityInstance->setPublishedAt(new DateTimeImmutable());
        }

        // Handle image upload or AI generation
        $this->handleBlogPostImage($entityInstance);

        parent::persistEntity($entityManager, $entityInstance);
    }

    /**
     * Auto-set publishedAt when status changes to published and handle image generation/upload.
     */
    #[Override]
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof BlogPost) {
            parent::updateEntity($entityManager, $entityInstance);

            return;
        }

        // Auto-set publishedAt if status is published and publishedAt is null
        if ($entityInstance->getStatus() === BlogPost::STATUS_PUBLISHED && $entityInstance->getPublishedAt() === null) {
            $entityInstance->setPublishedAt(new DateTimeImmutable());
        }

        // Handle image upload or AI generation
        $this->handleBlogPostImage($entityInstance);

        parent::updateEntity($entityManager, $entityInstance);
    }

    /**
     * Handle blog post image upload or AI generation.
     * Priority: 1. Manual upload, 2. AI generation, 3. External URL.
     */
    private function handleBlogPostImage(BlogPost $blogPost): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return;
        }

        // Check if there's an uploaded file (not implemented yet - would need ImageField in EasyAdmin)
        // For now, we focus on AI generation workflow

        // Priority 2: AI generation
        if ($blogPost->getImageSource() === BlogPost::IMAGE_SOURCE_AI_GENERATED
            && $blogPost->getImagePrompt() !== null
            && $blogPost->getImageGeneratedAt() === null) {
            try {
                $this->imageGenerationService->generateImage(
                    $blogPost->getImagePrompt(),
                    $blogPost,
                );

                $this->addFlash('success', 'Image générée avec succès par l\'IA (DALL-E 3).');
            } catch (BlogImageGenerationException $e) {
                $this->addFlash('error', sprintf('Erreur lors de la génération de l\'image: %s', $e->getMessage()));

                // Don't throw - allow the blog post to be saved without image
                $blogPost->setImageSource(BlogPost::IMAGE_SOURCE_EXTERNAL);
                $blogPost->setFeaturedImage(null);
            }

            return;
        }

        // Priority 3: External URL (backward compatibility)
        if ($blogPost->getImageSource() === BlogPost::IMAGE_SOURCE_EXTERNAL) {
            // Validate URL format if provided
            if ($blogPost->getFeaturedImage()    !== null
                && $blogPost->getFeaturedImage() !== ''
                && !filter_var($blogPost->getFeaturedImage(), FILTER_VALIDATE_URL)) {
                $this->addFlash('error', 'L\'URL de l\'image externe n\'est pas valide.');
                $blogPost->setFeaturedImage(null);
            }
        }
    }

    /**
     * Custom action: Regenerate AI image with the same prompt.
     */
    public function regenerateImageAction(
        AdminContext $context,
        AdminUrlGenerator $adminUrlGenerator,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var BlogPost $blogPost */
        $blogPost = $context->getEntity()->getInstance();

        if ($blogPost->getImageSource() !== BlogPost::IMAGE_SOURCE_AI_GENERATED) {
            $this->addFlash('error', 'Cette image n\'a pas été générée par l\'IA.');

            return $this->redirect($adminUrlGenerator->setAction(Action::INDEX)->generateUrl());
        }

        if ($blogPost->getImagePrompt() === null) {
            $this->addFlash('error', 'Aucun prompt enregistré pour régénérer l\'image.');

            return $this->redirect($adminUrlGenerator->setAction(Action::INDEX)->generateUrl());
        }

        try {
            $this->imageGenerationService->regenerateImage($blogPost);
            $this->addFlash('success', 'Nouvelle image générée avec succès ! L\'ancienne a été supprimée.');

            // Persist the updated BlogPost
            $entityManager->flush();
        } catch (BlogImageGenerationException $e) {
            $this->addFlash('error', sprintf('Erreur lors de la régénération: %s', $e->getMessage()));
        }

        // Redirect back to the detail or edit page
        return $this->redirect($context->getReferrer() ?? $adminUrlGenerator->setAction(Action::INDEX)->generateUrl());
    }
}
