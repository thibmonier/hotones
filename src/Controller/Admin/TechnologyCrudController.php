<?php

namespace App\Controller\Admin;

use App\Entity\Technology;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ColorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use Override;

class TechnologyCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Technology::class;
    }

    #[Override]
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Technologie')
            ->setEntityLabelInPlural('Technologies')
            ->setSearchFields(['name', 'category'])
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

        yield ChoiceField::new('category', 'Catégorie')
            ->setChoices([
                'Framework'       => 'framework',
                'CMS'             => 'cms',
                'Bibliothèque'    => 'library',
                'Outil'           => 'tool',
                'Hébergement'     => 'hosting',
                'Base de données' => 'database',
                'Langage'         => 'language',
                'Autre'           => 'other',
            ])
            ->setRequired(true);

        yield ColorField::new('color', 'Couleur');

        yield IntegerField::new('projects.count', 'Nombre de projets')
            ->hideOnForm()
            ->formatValue(fn ($value, Technology $entity) => $entity->getProjects()->count());

        yield BooleanField::new('active', 'Actif')
            ->renderAsSwitch(false);
    }

    #[Override]
    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('category', 'Catégorie')->setChoices([
                'Framework'       => 'framework',
                'CMS'             => 'cms',
                'Bibliothèque'    => 'library',
                'Outil'           => 'tool',
                'Hébergement'     => 'hosting',
                'Base de données' => 'database',
                'Langage'         => 'language',
                'Autre'           => 'other',
            ]))
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
