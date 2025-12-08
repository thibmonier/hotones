<?php

namespace App\Controller\Admin;

use App\Entity\SchedulerEntry;
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

class SchedulerEntryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SchedulerEntry::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Tâche planifiée')
            ->setEntityLabelInPlural('Tâches planifiées')
            ->setSearchFields(['name', 'command', 'cronExpression'])
            ->setDefaultSort(['name' => 'ASC'])
            ->setPaginatorPageSize(25);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->hideOnForm();

        yield TextField::new('name', 'Nom')
            ->setRequired(true)
            ->setHelp('Nom descriptif de la tâche');

        yield TextField::new('cronExpression', 'Expression CRON')
            ->setRequired(true)
            ->setHelp('Ex: */5 * * * * (toutes les 5 minutes)');

        yield TextField::new('command', 'Commande')
            ->setRequired(true)
            ->setHelp('Nom de la commande Symfony à exécuter');

        yield TextField::new('timezone', 'Fuseau horaire')
            ->setRequired(true)
            ->setHelp('Ex: Europe/Paris')
            ->hideOnIndex();

        yield ArrayField::new('payload', 'Paramètres (JSON)')
            ->hideOnIndex()
            ->setHelp('Paramètres additionnels au format JSON');

        yield BooleanField::new('enabled', 'Activé')
            ->renderAsSwitch(false);

        yield DateTimeField::new('createdAt', 'Créé le')
            ->hideOnForm();

        yield DateTimeField::new('updatedAt', 'Modifié le')
            ->hideOnForm()
            ->hideOnIndex();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(BooleanFilter::new('enabled', 'Activé'));
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->setPermission(Action::NEW, 'ROLE_ADMIN')
            ->setPermission(Action::EDIT, 'ROLE_ADMIN')
            ->setPermission(Action::DELETE, 'ROLE_ADMIN');
    }
}
