<?php

namespace App\Controller\Admin;

use App\Entity\SaasService;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use Override;

class SaasServiceCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SaasService::class;
    }

    #[Override]
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Service SaaS')
            ->setEntityLabelInPlural('Services SaaS')
            ->setSearchFields(['name', 'description', 'category'])
            ->setDefaultSort(['provider' => 'ASC', 'name' => 'ASC'])
            ->setPaginatorPageSize(25);
    }

    #[Override]
    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->hideOnForm();

        yield TextField::new('name', 'Nom')
            ->setRequired(true)
            ->setHelp('Nom du service (ex: Google Workspace, Slack Premium)');

        yield AssociationField::new('provider', 'Fournisseur')
            ->setRequired(false)
            ->setQueryBuilder(fn ($qb) => $qb
                ->andWhere('entity.active = :active')
                ->setParameter('active', true)
                ->orderBy('entity.name', 'ASC'))
            ->setHelp('Laisser vide si souscription directe');

        yield TextField::new('category', 'Catégorie')
            ->setHelp('Ex: Communication, Productivité, Développement');

        yield TextareaField::new('description', 'Description')
            ->hideOnIndex();

        yield UrlField::new('serviceUrl', 'URL du service')
            ->hideOnIndex();

        yield UrlField::new('logoUrl', 'URL du logo')
            ->hideOnIndex();

        yield NumberField::new('defaultMonthlyPrice', 'Prix mensuel')
            ->setNumDecimals(2)
            ->setHelp('Prix mensuel de référence (€)')
            ->hideOnIndex();

        yield NumberField::new('defaultYearlyPrice', 'Prix annuel')
            ->setNumDecimals(2)
            ->setHelp('Prix annuel de référence (€)')
            ->hideOnIndex();

        yield ChoiceField::new('currency', 'Devise')
            ->setChoices([
                'EUR' => 'EUR',
                'USD' => 'USD',
                'GBP' => 'GBP',
            ])
            ->hideOnIndex();

        yield TextareaField::new('notes', 'Notes')
            ->hideOnIndex();

        yield IntegerField::new('subscriptions.count', 'Abonnements')
            ->hideOnForm()
            ->formatValue(fn ($value, SaasService $entity) => $entity->getSubscriptions()->count());

        yield BooleanField::new('active', 'Actif')
            ->renderAsSwitch(false);

        yield DateTimeField::new('createdAt', 'Date de création')
            ->hideOnForm()
            ->setFormat('dd/MM/yyyy HH:mm');

        yield DateTimeField::new('updatedAt', 'Dernière modification')
            ->hideOnForm()
            ->setFormat('dd/MM/yyyy HH:mm');
    }

    #[Override]
    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('provider', 'Fournisseur'))
            ->add('category')
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
}
