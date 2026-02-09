<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\SaasProvider;
use App\Security\CompanyContext;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use Override;

/**
 * @extends AbstractCrudController<SaasProvider>
 */
class SaasProviderCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly CompanyContext $companyContext,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return SaasProvider::class;
    }

    #[Override]
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Fournisseur SaaS')
            ->setEntityLabelInPlural('Fournisseurs SaaS')
            ->setSearchFields(['name', 'website', 'contactEmail'])
            ->setDefaultSort(['name' => 'ASC'])
            ->setPaginatorPageSize(25);
    }

    #[Override]
    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        yield TextField::new('name', 'Nom')->setRequired(true)->setHelp(
            'Nom du fournisseur (ex: Google, Microsoft, Adobe)',
        );

        yield UrlField::new('website', 'Site web')->setHelp('URL du site web du fournisseur');

        yield EmailField::new('contactEmail', 'Email de contact');

        yield TelephoneField::new('contactPhone', 'Téléphone');

        yield UrlField::new('logoUrl', 'URL du logo')->setHelp(
            'URL ou chemin vers le logo du fournisseur',
        )->hideOnIndex();

        yield TextareaField::new('notes', 'Notes')->hideOnIndex()->setHelp('Notes internes sur le fournisseur');

        yield IntegerField::new('services.count', 'Nombre de services')->hideOnForm()->formatValue(
            fn ($value, SaasProvider $entity) => $entity->getServices()->count(),
        );

        yield BooleanField::new('active', 'Actif')->renderAsSwitch(false);

        yield DateTimeField::new('createdAt', 'Date de création')->hideOnForm()->setFormat('dd/MM/yyyy HH:mm');

        yield DateTimeField::new('updatedAt', 'Dernière modification')->hideOnForm()->setFormat('dd/MM/yyyy HH:mm');
    }

    #[Override]
    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add(BooleanFilter::new('active', 'Actif'));
    }

    #[Override]
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->setPermission(Action::NEW, 'ROLE_ADMIN')
            ->setPermission(Action::EDIT, 'ROLE_ADMIN')
            ->setPermission(Action::DELETE, 'ROLE_ADMIN');
    }

    /**
     * Create a new SaasProvider with company pre-assigned.
     */
    #[Override]
    public function createEntity(string $entityFqcn): SaasProvider
    {
        $provider = new SaasProvider();
        $provider->setCompany($this->companyContext->getCurrentCompany());

        return $provider;
    }
}
