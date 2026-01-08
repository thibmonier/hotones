<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\BlogTag;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Override;

/**
 * CRUD controller for blog tags (global entities, managed by SUPERADMIN).
 */
class BlogTagCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BlogTag::class;
    }

    #[Override]
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Tag de blog')
            ->setEntityLabelInPlural('Tags de blog')
            ->setSearchFields(['name'])
            ->setDefaultSort(['name' => 'ASC'])
            ->setPaginatorPageSize(50);
    }

    #[Override]
    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->hideOnForm();

        yield TextField::new('name', 'Nom')
            ->setRequired(true)
            ->setHelp('Nom du tag (ex: "symfony", "performance", "sécurité")');

        yield SlugField::new('slug', 'Slug')
            ->setTargetFieldName('name')
            ->setHelp('URL-friendly identifier (auto-généré depuis le nom)');

        yield IntegerField::new('posts.count', 'Nombre d\'articles')
            ->hideOnForm()
            ->formatValue(fn ($value, BlogTag $entity) => $entity->getPosts()->count());
    }

    #[Override]
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->setPermission(Action::INDEX, 'ROLE_SUPERADMIN')
            ->setPermission(Action::NEW, 'ROLE_SUPERADMIN')
            ->setPermission(Action::EDIT, 'ROLE_SUPERADMIN')
            ->setPermission(Action::DELETE, 'ROLE_SUPERADMIN')
            ->setPermission(Action::DETAIL, 'ROLE_SUPERADMIN');
    }
}
