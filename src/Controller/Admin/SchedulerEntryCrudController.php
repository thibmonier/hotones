<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\SchedulerEntry;
use App\Security\CompanyContext;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use Override;

/**
 * @extends AbstractCrudController<SchedulerEntry>
 */
class SchedulerEntryCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly CompanyContext $companyContext,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return SchedulerEntry::class;
    }

    #[Override]
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Tâche planifiée')
            ->setEntityLabelInPlural('Tâches planifiées')
            ->setSearchFields(['name', 'command', 'cronExpression'])
            ->setDefaultSort(['name' => 'ASC'])
            ->setPaginatorPageSize(25);
    }

    #[Override]
    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        yield TextField::new('name', 'Nom')->setRequired(true)->setHelp('Nom descriptif de la tâche');

        yield TextField::new('cronExpression', 'Expression CRON')->setRequired(true)->setHelp(
            'Ex: */5 * * * * (toutes les 5 minutes)',
        );

        yield TextField::new('command', 'Commande')->setRequired(true)->setHelp(
            'Nom de la commande Symfony à exécuter',
        );

        yield TextField::new('timezone', 'Fuseau horaire')
            ->setRequired(true)
            ->setHelp('Ex: Europe/Paris')
            ->hideOnIndex();

        yield ArrayField::new('payload', 'Paramètres (JSON)')->hideOnIndex()->setHelp(
            'Paramètres additionnels au format JSON',
        );

        yield BooleanField::new('enabled', 'Activé')->renderAsSwitch(false);

        yield DateTimeField::new('createdAt', 'Créé le')->hideOnForm();

        yield DateTimeField::new('updatedAt', 'Modifié le')->hideOnForm()->hideOnIndex();
    }

    #[Override]
    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add(BooleanFilter::new('enabled', 'Activé'));
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
     * Create a new SchedulerEntry with company pre-assigned.
     */
    #[Override]
    public function createEntity(string $entityFqcn): SchedulerEntry
    {
        $entry = new SchedulerEntry();
        $entry->setCompany($this->companyContext->getCurrentCompany());

        return $entry;
    }
}
