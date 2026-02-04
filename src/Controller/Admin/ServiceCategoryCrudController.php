<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\ServiceCategory;
use App\Security\CompanyContext;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ColorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use Override;

/**
 * @extends AbstractCrudController<ServiceCategory>
 */
class ServiceCategoryCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly CompanyContext $companyContext,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return ServiceCategory::class;
    }

    #[Override]
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Catégorie de service')
            ->setEntityLabelInPlural('Catégories de service')
            ->setSearchFields(['name', 'description'])
            ->setDefaultSort(['name' => 'ASC'])
            ->setPaginatorPageSize(25);
    }

    #[Override]
    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->hideOnForm();

        yield TextField::new('name', 'Nom')
            ->setRequired(true);

        yield TextareaField::new('description', 'Description')
            ->hideOnIndex();

        yield ColorField::new('color', 'Couleur');

        yield IntegerField::new('projects.count', 'Nombre de projets')
            ->hideOnForm()
            ->formatValue(fn ($value, ServiceCategory $entity) => $entity->getProjects()->count());

        yield BooleanField::new('active', 'Actif')
            ->renderAsSwitch(false);
    }

    #[Override]
    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(BooleanFilter::new('active', 'Actif'));
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
     * Create a new ServiceCategory with company pre-assigned.
     */
    #[Override]
    public function createEntity(string $entityFqcn): ServiceCategory
    {
        $category = new ServiceCategory();
        $category->setCompany($this->companyContext->getCurrentCompany());

        return $category;
    }
}
