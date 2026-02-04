<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Profile;
use App\Security\CompanyContext;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ColorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use Override;

/**
 * @extends AbstractCrudController<Profile>
 */
class ProfileCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly CompanyContext $companyContext,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Profile::class;
    }

    #[Override]
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Profil métier')
            ->setEntityLabelInPlural('Profils métier')
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

        yield MoneyField::new('defaultDailyRate', 'TJM par défaut')
            ->setCurrency('EUR')
            ->setNumDecimals(2);

        yield MoneyField::new('cjm', 'CJM (Coût Journalier Moyen)')
            ->setCurrency('EUR')
            ->setNumDecimals(2);

        yield NumberField::new('marginCoefficient', 'Coefficient de marge')
            ->setNumDecimals(2);

        yield ColorField::new('color', 'Couleur');

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
     * Create a new Profile with company pre-assigned.
     */
    #[Override]
    public function createEntity(string $entityFqcn): Profile
    {
        $profile = new Profile();
        $profile->setCompany($this->companyContext->getCurrentCompany());

        return $profile;
    }
}
