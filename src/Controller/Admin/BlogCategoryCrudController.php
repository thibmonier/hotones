<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\BlogCategory;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ColorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use Override;

/**
 * CRUD controller for blog categories (global entities, managed by SUPERADMIN).
 */
class BlogCategoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BlogCategory::class;
    }

    #[Override]
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Catégorie de blog')
            ->setEntityLabelInPlural('Catégories de blog')
            ->setSearchFields(['name', 'description'])
            ->setDefaultSort(['name' => 'ASC'])
            ->setPaginatorPageSize(25);
    }

    #[Override]
    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        yield TextField::new('name', 'Nom')->setRequired(true)->setHelp(
            'Nom de la catégorie (ex: "Gestion de projet", "KPIs")',
        );

        yield SlugField::new('slug', 'Slug')->setTargetFieldName('name')->setHelp(
            'URL-friendly identifier (auto-généré depuis le nom)',
        );

        yield TextareaField::new('description', 'Description')
            ->setMaxLength(500)
            ->setHelp('Description courte de la catégorie')
            ->hideOnIndex();

        yield ColorField::new('color', 'Couleur')->setHelp('Couleur pour l\'affichage (ex: #6366f1)');

        yield BooleanField::new('active', 'Active')->renderAsSwitch(false)->setHelp(
            'Catégories inactives ne sont pas affichées dans le frontend',
        );

        yield IntegerField::new('posts.count', 'Nombre d\'articles')->hideOnForm()->formatValue(
            fn ($value, BlogCategory $entity) => $entity->getPosts()->count(),
        );
    }

    #[Override]
    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add(BooleanFilter::new('active', 'Active'));
    }

    #[Override]
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->setPermission(Action::INDEX, 'ROLE_SUPERADMIN')
            ->setPermission(Action::NEW, 'ROLE_SUPERADMIN')
            ->setPermission(Action::EDIT, 'ROLE_SUPERADMIN')
            ->setPermission(Action::DELETE, 'ROLE_SUPERADMIN')
            ->setPermission(Action::DETAIL, 'ROLE_SUPERADMIN');
    }
}
