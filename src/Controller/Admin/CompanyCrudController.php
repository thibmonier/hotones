<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Company;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use Override;

class CompanyCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Company::class;
    }

    #[Override]
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Société')
            ->setEntityLabelInPlural('Sociétés')
            ->setSearchFields(['name', 'slug', 'description'])
            ->setDefaultSort(['name' => 'ASC'])
            ->setPaginatorPageSize(25)
            ->showEntityActionsInlined();
    }

    #[Override]
    public function configureFields(string $pageName): iterable
    {
        // Identity section
        yield IdField::new('id')
            ->hideOnForm();

        yield TextField::new('name', 'Nom')
            ->setRequired(true)
            ->setHelp('Nom de la société');

        yield TextField::new('slug', 'Slug')
            ->setRequired(true)
            ->setHelp('Identifiant unique pour les URLs (lettres minuscules, chiffres et tirets uniquement)');

        yield TextareaField::new('description', 'Description')
            ->hideOnIndex();

        // Status & Subscription
        yield ChoiceField::new('status', 'Statut')
            ->setChoices([
                'Actif'    => Company::STATUS_ACTIVE,
                'Essai'    => Company::STATUS_TRIAL,
                'Suspendu' => Company::STATUS_SUSPENDED,
                'Annulé'   => Company::STATUS_CANCELLED,
            ])
            ->setRequired(true);

        yield ChoiceField::new('subscriptionTier', 'Forfait')
            ->setChoices([
                'Starter'      => Company::TIER_STARTER,
                'Professional' => Company::TIER_PROFESSIONAL,
                'Enterprise'   => Company::TIER_ENTERPRISE,
            ])
            ->setRequired(true);

        // Limits
        yield IntegerField::new('maxUsers', 'Max utilisateurs')
            ->setHelp('Nombre maximum d\'utilisateurs (null = illimité)')
            ->hideOnIndex();

        yield IntegerField::new('maxProjects', 'Max projets')
            ->setHelp('Nombre maximum de projets (null = illimité)')
            ->hideOnIndex();

        yield IntegerField::new('maxStorageMb', 'Max stockage (Mo)')
            ->setHelp('Stockage maximum en Mo (null = illimité)')
            ->hideOnIndex();

        // Billing
        yield DateField::new('billingStartDate', 'Date début facturation')
            ->hideOnIndex();

        yield IntegerField::new('billingDayOfMonth', 'Jour facturation')
            ->setHelp('Jour du mois pour la facturation (1-28)')
            ->hideOnIndex();

        yield TextField::new('currency', 'Devise')
            ->setHelp('Code devise ISO (ex: EUR, USD)')
            ->hideOnIndex();

        // Features
        yield ArrayField::new('enabledFeatures', 'Fonctionnalités activées')
            ->setHelp('Features: '.implode(', ', [
                Company::FEATURE_INVOICING,
                Company::FEATURE_PLANNING,
                Company::FEATURE_ANALYTICS,
                Company::FEATURE_BUSINESS_UNITS,
                Company::FEATURE_AI_TOOLS,
                Company::FEATURE_API_ACCESS,
            ]))
            ->hideOnIndex();

        // Company Settings (coefficients)
        yield NumberField::new('structureCostCoefficient', 'Coefficient coût structure')
            ->setNumDecimals(4)
            ->setHelp('Coefficient multiplicateur pour les coûts de structure (défaut: 1.3500)')
            ->hideOnIndex();

        yield NumberField::new('employerChargesCoefficient', 'Coefficient charges patronales')
            ->setNumDecimals(4)
            ->setHelp('Coefficient multiplicateur pour les charges patronales (défaut: 1.4500)')
            ->hideOnIndex();

        yield IntegerField::new('annualPaidLeaveDays', 'Jours de congés payés')
            ->setHelp('Nombre de jours de congés payés par an (défaut: 25)')
            ->hideOnIndex();

        yield IntegerField::new('annualRttDays', 'Jours RTT')
            ->setHelp('Nombre de jours RTT par an (défaut: 10)')
            ->hideOnIndex();

        // Relations
        yield AssociationField::new('owner', 'Propriétaire')
            ->hideOnIndex();

        yield IntegerField::new('users.count', 'Nombre utilisateurs')
            ->hideOnForm()
            ->formatValue(fn ($value, Company $entity) => $entity->getUsers()->count());

        // Timestamps
        yield DateTimeField::new('createdAt', 'Créé le')
            ->hideOnForm()
            ->hideOnIndex();

        yield DateTimeField::new('updatedAt', 'Modifié le')
            ->hideOnForm()
            ->hideOnIndex();

        yield DateTimeField::new('trialEndsAt', 'Fin d\'essai')
            ->hideOnForm()
            ->hideOnIndex();

        yield DateTimeField::new('suspendedAt', 'Suspendu le')
            ->hideOnForm()
            ->hideOnIndex();
    }

    #[Override]
    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('status', 'Statut')->setChoices([
                'Actif'    => Company::STATUS_ACTIVE,
                'Essai'    => Company::STATUS_TRIAL,
                'Suspendu' => Company::STATUS_SUSPENDED,
                'Annulé'   => Company::STATUS_CANCELLED,
            ]))
            ->add(ChoiceFilter::new('subscriptionTier', 'Forfait')->setChoices([
                'Starter'      => Company::TIER_STARTER,
                'Professional' => Company::TIER_PROFESSIONAL,
                'Enterprise'   => Company::TIER_ENTERPRISE,
            ]));
    }

    #[Override]
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->setPermission(Action::NEW, 'ROLE_SUPERADMIN')
            ->setPermission(Action::EDIT, 'ROLE_SUPERADMIN')
            ->setPermission(Action::DELETE, 'ROLE_SUPERADMIN')
            ->setPermission(Action::DETAIL, 'ROLE_ADMIN');
    }
}
