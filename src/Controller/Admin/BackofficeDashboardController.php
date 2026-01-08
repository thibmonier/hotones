<?php

namespace App\Controller\Admin;

use App\Entity\Profile;
use App\Entity\SaasProvider;
use App\Entity\SaasService;
use App\Entity\SaasSubscription;
use App\Entity\SchedulerEntry;
use App\Entity\ServiceCategory;
use App\Entity\Skill;
use App\Entity\Technology;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Override;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class BackofficeDashboardController extends AbstractDashboardController
{
    #[Route('/backoffice', name: 'backoffice')]
    #[Override]
    public function index(): Response
    {
        // Option 1. You can make your dashboard redirect to some common page of your backend
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);

        return $this->redirect($adminUrlGenerator->setController(TechnologyCrudController::class)->generateUrl());

        // Option 2. You can make your dashboard redirect to different pages depending on the user
        //
        // if ('jane' === $this->getUser()->getUsername()) {
        //     return $this->redirect('...');
        // }

        // Option 3. You can render some custom template to display a proper dashboard with widgets, etc.
        // (tip: it's easier if your template extends from @EasyAdmin/page/content.html.twig)
        //
        // return $this->render('admin/dashboard.html.twig');
    }

    #[Override]
    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('HotOnes - Backoffice')
            ->setFaviconPath('favicon.ico')
            ->setLocales(['fr']);
    }

    #[Override]
    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Tableau de bord', 'fa fa-home');

        yield MenuItem::section('Configuration');
        yield MenuItem::linkToCrud('Technologies', 'fas fa-code', Technology::class);
        yield MenuItem::linkToCrud('Catégories de service', 'fas fa-tags', ServiceCategory::class);
        yield MenuItem::linkToCrud('Profils métier', 'fas fa-user-tie', Profile::class);
        yield MenuItem::linkToCrud('Compétences', 'fas fa-certificate', Skill::class);

        yield MenuItem::section('SaaS');
        yield MenuItem::linkToCrud('Fournisseurs', 'fas fa-building', SaasProvider::class);
        yield MenuItem::linkToCrud('Services', 'fas fa-cube', SaasService::class);
        yield MenuItem::linkToCrud('Abonnements', 'fas fa-calendar-check', SaasSubscription::class);

        yield MenuItem::section('Système');
        yield MenuItem::linkToCrud('Scheduler', 'fas fa-clock', SchedulerEntry::class);
        yield MenuItem::linkToRoute('Notifications', 'fas fa-bell', 'admin_notification_settings');

        yield MenuItem::section();
        yield MenuItem::linkToRoute('Retour à l\'application', 'fas fa-arrow-left', 'home');
    }
}
