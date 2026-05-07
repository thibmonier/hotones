<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Override;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[AdminDashboard(routePath: '/backoffice', routeName: 'backoffice')]
class BackofficeDashboardController extends AbstractDashboardController
{
    #[Override]
    public function index(): Response
    {
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);

        return $this->redirect($adminUrlGenerator->setController(TechnologyCrudController::class)->generateUrl());
    }

    #[Override]
    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()->setTitle('HotOnes - Backoffice')->setFaviconPath('favicon.ico')->setLocales(['fr']);
    }

    #[Override]
    public function configureAssets(): Assets
    {
        return Assets::new()->addJsFile('assets/js/admin/blog-post-image-fields.js');
    }

    #[Override]
    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Tableau de bord', 'fa fa-home');

        yield MenuItem::section('Configuration');
        yield MenuItem::linkTo(TechnologyCrudController::class, 'Technologies', 'fas fa-code');
        yield MenuItem::linkTo(ServiceCategoryCrudController::class, 'Catégories de service', 'fas fa-tags');
        yield MenuItem::linkTo(ProfileCrudController::class, 'Profils métier', 'fas fa-user-tie');
        yield MenuItem::linkTo(SkillCrudController::class, 'Compétences', 'fas fa-certificate');

        yield MenuItem::section('SaaS');
        yield MenuItem::linkTo(SaasProviderCrudController::class, 'Fournisseurs', 'fas fa-building');
        yield MenuItem::linkTo(SaasServiceCrudController::class, 'Services', 'fas fa-cube');
        yield MenuItem::linkTo(SaasSubscriptionCrudController::class, 'Abonnements', 'fas fa-calendar-check');

        yield MenuItem::section('Blog');
        yield MenuItem::linkTo(BlogPostCrudController::class, 'Articles', 'fas fa-newspaper');
        yield MenuItem::linkTo(BlogCategoryCrudController::class, 'Catégories', 'fas fa-folder');
        yield MenuItem::linkTo(BlogTagCrudController::class, 'Tags', 'fas fa-tag');

        yield MenuItem::section('Système');
        yield MenuItem::linkTo(CompanyCrudController::class, 'Sociétés', 'fas fa-building');
        yield MenuItem::linkTo(SchedulerEntryCrudController::class, 'Scheduler', 'fas fa-clock');
        yield MenuItem::linkToRoute('Notifications', 'fas fa-bell', 'admin_notification_settings');

        yield MenuItem::section();
        yield MenuItem::linkToRoute('Retour à l\'application', 'fas fa-arrow-left', 'home');
    }
}
