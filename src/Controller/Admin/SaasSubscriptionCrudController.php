<?php

namespace App\Controller\Admin;

use App\Entity\SaasSubscription;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use Override;

class SaasSubscriptionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SaasSubscription::class;
    }

    #[Override]
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Abonnement SaaS')
            ->setEntityLabelInPlural('Abonnements SaaS')
            ->setSearchFields(['customName', 'externalReference', 'notes'])
            ->setDefaultSort(['nextRenewalDate' => 'ASC'])
            ->setPaginatorPageSize(25);
    }

    #[Override]
    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->hideOnForm();

        yield AssociationField::new('service', 'Service')
            ->setRequired(true)
            ->setQueryBuilder(fn ($qb) => $qb
                ->leftJoin('entity.provider', 'p')
                ->andWhere('entity.active = :active')
                ->setParameter('active', true)
                ->orderBy('p.name', 'ASC')
                ->addOrderBy('entity.name', 'ASC'))
            ->formatValue(function ($value, SaasSubscription $entity) {
                $service = $entity->getService();
                if (!$service) {
                    return '';
                }
                $provider = $service->getProvider();

                return $provider
                    ? sprintf('%s (%s)', $service->getName(), $provider->getName())
                    : $service->getName();
            });

        yield TextField::new('customName', 'Nom personnalisé')
            ->setHelp('Si vide, le nom du service sera utilisé')
            ->hideOnIndex();

        yield ChoiceField::new('billingPeriod', 'Périodicité')
            ->setChoices(array_flip(SaasSubscription::BILLING_PERIODS))
            ->setRequired(true);

        yield NumberField::new('price', 'Prix')
            ->setNumDecimals(2)
            ->setRequired(true)
            ->setHelp('Prix unitaire (€)')
            ->formatValue(fn ($value): string => number_format((float) $value, 2, ',', ' ').' €');

        yield ChoiceField::new('currency', 'Devise')
            ->setChoices([
                'EUR' => 'EUR',
                'USD' => 'USD',
                'GBP' => 'GBP',
            ])
            ->hideOnIndex();

        yield IntegerField::new('quantity', 'Quantité')
            ->setHelp('Nombre de licences/utilisateurs');

        yield ChoiceField::new('status', 'Statut')
            ->setChoices(array_flip(SaasSubscription::STATUSES))
            ->setRequired(true);

        yield DateField::new('startDate', 'Date de début')
            ->setRequired(true)
            ->setFormat('dd/MM/yyyy');

        yield DateField::new('nextRenewalDate', 'Prochain renouvellement')
            ->setRequired(true)
            ->setFormat('dd/MM/yyyy');

        yield DateField::new('endDate', 'Date de fin')
            ->setFormat('dd/MM/yyyy')
            ->hideOnIndex();

        yield DateField::new('lastRenewalDate', 'Dernier renouvellement')
            ->setFormat('dd/MM/yyyy')
            ->hideOnIndex();

        yield BooleanField::new('autoRenewal', 'Renouvellement auto')
            ->renderAsSwitch(false);

        yield TextField::new('externalReference', 'Référence externe')
            ->hideOnIndex();

        yield TextareaField::new('notes', 'Notes')
            ->hideOnIndex();

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
            ->add(EntityFilter::new('service', 'Service'))
            ->add(ChoiceFilter::new('status', 'Statut')->setChoices(array_flip(SaasSubscription::STATUSES)))
            ->add(ChoiceFilter::new('billingPeriod', 'Périodicité')->setChoices(array_flip(SaasSubscription::BILLING_PERIODS)));
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
