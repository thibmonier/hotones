<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Skill;
use App\Security\CompanyContext;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use Override;

/**
 * @extends AbstractCrudController<Skill>
 */
class SkillCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly CompanyContext $companyContext,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Skill::class;
    }

    #[Override]
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Compétence')
            ->setEntityLabelInPlural('Compétences')
            ->setSearchFields(['name', 'description', 'category'])
            ->setDefaultSort(['category' => 'ASC', 'name' => 'ASC'])
            ->setPaginatorPageSize(25);
    }

    #[Override]
    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        yield TextField::new('name', 'Nom')->setRequired(true);

        yield ChoiceField::new('category', 'Catégorie')->setChoices([
            'Langage'      => 'language',
            'Framework'    => 'framework',
            'Outil'        => 'tool',
            'Méthodologie' => 'methodology',
            'Autre'        => 'other',
        ])->setRequired(true);

        yield TextareaField::new('description', 'Description')->hideOnIndex();

        yield IntegerField::new('contributorCount', 'Collaborateurs')->hideOnForm();

        yield BooleanField::new('active', 'Actif')->renderAsSwitch(false);
    }

    #[Override]
    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add(ChoiceFilter::new('category', 'Catégorie')->setChoices([
            'Langage'      => 'language',
            'Framework'    => 'framework',
            'Outil'        => 'tool',
            'Méthodologie' => 'methodology',
            'Autre'        => 'other',
        ]))->add(BooleanFilter::new('active', 'Actif'));
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
     * Create a new Skill with company pre-assigned.
     */
    #[Override]
    public function createEntity(string $entityFqcn): Skill
    {
        $skill = new Skill();
        $skill->setCompany($this->companyContext->getCurrentCompany());

        return $skill;
    }
}
