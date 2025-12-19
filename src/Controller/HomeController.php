<?php

namespace App\Controller;

use App\Entity\Contributor;
use App\Entity\Invoice;
use App\Entity\Order;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Entity\Vacation;
use App\Enum\OrderStatus;
use App\Service\Analytics\DashboardReadService;
use App\Service\HrMetricsService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class HomeController extends AbstractController
{
    public function __construct(
        private DashboardReadService $dashboardReadService,
        private ?HrMetricsService $hrMetricsService = null
    ) {
    }

    #[Route('/app', name: 'home')]
    #[IsGranted('ROLE_USER')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $contributorRepo = $em->getRepository(Contributor::class);
        $contributor     = $contributorRepo->findByUser($this->getUser());

        // Récupérer tous les rôles disponibles pour l'utilisateur
        $availableRoles = $this->getAvailableRoles();

        // Déterminer le rôle à afficher (session > query > défaut)
        $selectedRole = $request->query->get('view');
        if ($selectedRole && in_array($selectedRole, array_keys($availableRoles), true)) {
            // Sauvegarder la préférence en session
            $request->getSession()->set('home_dashboard_view', $selectedRole);
        } elseif ($request->getSession()->has('home_dashboard_view')) {
            // Récupérer depuis la session
            $selectedRole = $request->getSession()->get('home_dashboard_view');
            // Vérifier que le rôle est toujours disponible
            if (!in_array($selectedRole, array_keys($availableRoles), true)) {
                $selectedRole = $this->getUserPrimaryRole();
            }
        } else {
            // Utiliser le rôle par défaut (le plus élevé)
            $selectedRole = $this->getUserPrimaryRole();
        }

        // Préparer les données selon le rôle sélectionné
        $data = match ($selectedRole) {
            'admin'       => $this->getAdminData($em, $contributor),
            'compta'      => $this->getComptaData($em, $contributor),
            'manager'     => $this->getManagerData($em, $contributor),
            'chef_projet' => $this->getChefProjetData($em, $contributor),
            'intervenant' => $this->getIntervenantData($em, $contributor),
            default       => $this->getDefaultData($em, $contributor),
        };

        $data['userRole']       = $selectedRole;
        $data['availableRoles'] = $availableRoles;
        $data['contributor']    = $contributor;

        return $this->render('home/index.html.twig', $data);
    }

    /**
     * Détermine le rôle principal de l'utilisateur pour l'affichage personnalisé.
     */
    private function getUserPrimaryRole(): string
    {
        // Ordre de priorité des rôles (du plus élevé au plus bas)
        if ($this->isGranted('ROLE_ADMIN')) {
            return 'admin';
        }
        if ($this->isGranted('ROLE_COMPTA')) {
            return 'compta';
        }
        if ($this->isGranted('ROLE_MANAGER')) {
            return 'manager';
        }
        if ($this->isGranted('ROLE_CHEF_PROJET')) {
            return 'chef_projet';
        }
        if ($this->isGranted('ROLE_INTERVENANT')) {
            return 'intervenant';
        }

        return 'user';
    }

    /**
     * Retourne tous les rôles disponibles pour l'utilisateur avec leurs labels.
     */
    private function getAvailableRoles(): array
    {
        $roles = [];

        if ($this->isGranted('ROLE_ADMIN')) {
            $roles['admin'] = 'Dashboard Direction';
        }
        if ($this->isGranted('ROLE_COMPTA')) {
            $roles['compta'] = 'Dashboard Comptabilité';
        }
        if ($this->isGranted('ROLE_MANAGER')) {
            $roles['manager'] = 'Dashboard Management';
        }
        if ($this->isGranted('ROLE_CHEF_PROJET')) {
            $roles['chef_projet'] = 'Dashboard Commercial';
        }
        if ($this->isGranted('ROLE_INTERVENANT')) {
            $roles['intervenant'] = 'Mon Dashboard';
        }

        // Si aucun rôle spécifique, afficher la vue par défaut
        if (empty($roles)) {
            $roles['user'] = 'Dashboard';
        }

        return $roles;
    }

    /**
     * Données pour les administrateurs (vue d'ensemble complète).
     */
    private function getAdminData(EntityManagerInterface $em, ?Contributor $contributor): array
    {
        $currentMonth = new DateTime('first day of this month');
        $endMonth     = new DateTime('last day of this month');

        $projectRepo  = $em->getRepository(Project::class);
        $orderRepo    = $em->getRepository(Order::class);
        $invoiceRepo  = $em->getRepository(Invoice::class);
        $vacationRepo = $em->getRepository(Vacation::class);

        // KPIs globaux (CA produit = temps valorisés)
        $kpis = $this->dashboardReadService->getKPIs($currentMonth, $endMonth, []);

        // CA signé ce mois (devis)
        $monthlySignedRevenue = $orderRepo->getSignedRevenueForPeriod($currentMonth, $endMonth);

        // Projets actifs
        $activeProjects = $projectRepo->findBy(['status' => 'active'], ['name' => 'ASC'], 5);

        // Devis en attente
        $pendingOrders = $orderRepo->findBy(['status' => OrderStatus::PENDING->value], ['createdAt' => 'DESC'], 5);

        // Factures en attente
        $pendingInvoices = $invoiceRepo->findBy(['status' => 'pending'], ['createdAt' => 'DESC'], 5);

        // Demandes de congés en attente
        $pendingVacations = [];
        if ($contributor) {
            $managedContributors = $contributor->getManagedContributors();
            $pendingVacations    = $vacationRepo->findPendingForContributors($managedContributors->toArray());
        }

        return [
            'kpis'                 => $kpis,
            'monthlySignedRevenue' => $monthlySignedRevenue,
            'activeProjects'       => $activeProjects,
            'pendingOrders'        => $pendingOrders,
            'pendingInvoices'      => $pendingInvoices,
            'pendingVacations'     => $pendingVacations,
        ];
    }

    /**
     * Données pour la comptabilité.
     */
    private function getComptaData(EntityManagerInterface $em, ?Contributor $contributor): array
    {
        $currentMonth = new DateTime('first day of this month');
        $endMonth     = new DateTime('last day of this month');

        $invoiceRepo = $em->getRepository(Invoice::class);
        $orderRepo   = $em->getRepository(Order::class);

        // Factures en attente
        $pendingInvoices = $invoiceRepo->findBy(['status' => 'pending'], ['dueDate' => 'ASC'], 10);

        // CA facturé ce mois (si méthode disponible)
        $monthlyRevenue = 0;
        if (method_exists($invoiceRepo, 'getTotalRevenueForPeriod')) {
            $monthlyRevenue = $invoiceRepo->getTotalRevenueForPeriod($currentMonth, $endMonth);
        }

        // Devis signés ce mois
        $monthlySignedOrders = $orderRepo->getSignedRevenueForPeriod($currentMonth, $endMonth);

        return [
            'pendingInvoices'     => $pendingInvoices,
            'monthlyRevenue'      => $monthlyRevenue,
            'monthlySignedOrders' => $monthlySignedOrders,
        ];
    }

    /**
     * Données pour les managers.
     */
    private function getManagerData(EntityManagerInterface $em, ?Contributor $contributor): array
    {
        $currentMonth    = new DateTime('first day of this month');
        $endMonth        = new DateTime('last day of this month');
        $contributorRepo = $em->getRepository(Contributor::class);
        $vacationRepo    = $em->getRepository(Vacation::class);
        $projectRepo     = $em->getRepository(Project::class);

        // Demandes de congés en attente
        $pendingVacations = [];
        if ($contributor) {
            $managedContributors = $contributor->getManagedContributors();
            $pendingVacations    = $vacationRepo->findPendingForContributors($managedContributors->toArray());
        }

        // Effectif actuel
        $activeContributors = $contributorRepo->countActiveContributors();

        // Projets actifs
        $activeProjects = $projectRepo->findBy(['status' => 'active'], ['name' => 'ASC'], 10);

        // Métriques RH si disponible
        $hrMetrics = null;
        if ($this->hrMetricsService) {
            $hrMetrics = $this->hrMetricsService->getAllMetrics($currentMonth, $endMonth);
        }

        return [
            'pendingVacations'   => $pendingVacations,
            'activeContributors' => $activeContributors,
            'activeProjects'     => $activeProjects,
            'hrMetrics'          => $hrMetrics,
        ];
    }

    /**
     * Données pour les chefs de projet / commerciaux.
     */
    private function getChefProjetData(EntityManagerInterface $em, ?Contributor $contributor): array
    {
        $currentMonth = new DateTime('first day of this month');
        $endMonth     = new DateTime('last day of this month');

        $projectRepo = $em->getRepository(Project::class);
        $orderRepo   = $em->getRepository(Order::class);

        // Devis en attente de signature
        $pendingOrders = $orderRepo->findBy(['status' => OrderStatus::PENDING->value], ['createdAt' => 'DESC'], 5);

        // CA signé ce mois
        $monthlySignedRevenue = $orderRepo->getSignedRevenueForPeriod($currentMonth, $endMonth);

        // Mes projets actifs
        $myProjects = $projectRepo->findBy(['status' => 'active'], ['name' => 'ASC'], 10);

        // Devis récents
        $recentOrders = $orderRepo->getRecentOrders(5);

        return [
            'pendingOrders'        => $pendingOrders,
            'monthlySignedRevenue' => $monthlySignedRevenue,
            'myProjects'           => $myProjects,
            'recentOrders'         => $recentOrders,
        ];
    }

    /**
     * Données pour les contributeurs / intervenants.
     */
    private function getIntervenantData(EntityManagerInterface $em, ?Contributor $contributor): array
    {
        if (!$contributor) {
            return [];
        }

        $timesheetRepo   = $em->getRepository(Timesheet::class);
        $contributorRepo = $em->getRepository(Contributor::class);

        $startOfWeek = new DateTime('monday this week');
        $endOfWeek   = new DateTime('sunday this week');

        // Mes temps cette semaine
        $weeklyTimesheets = $timesheetRepo->findByContributorAndDateRange($contributor, $startOfWeek, $endOfWeek);

        // Total heures cette semaine
        $weeklyHours = array_reduce($weeklyTimesheets, fn ($sum, $t) => $sum + $t->getHours(), 0);

        // Mes temps récents
        $recentTimesheets = $timesheetRepo->findRecentByContributor($contributor, 5);

        // Mes projets actifs (via les tâches assignées)
        $projectsWithTasks = $contributorRepo->findProjectsWithTasksForContributor($contributor);

        return [
            'weeklyTimesheets'  => $weeklyTimesheets,
            'weeklyHours'       => $weeklyHours,
            'recentTimesheets'  => $recentTimesheets,
            'projectsWithTasks' => $projectsWithTasks,
        ];
    }

    /**
     * Données par défaut (fallback).
     */
    private function getDefaultData(EntityManagerInterface $em, ?Contributor $contributor): array
    {
        $currentMonth = new DateTime('first day of this month');
        $endMonth     = new DateTime('last day of this month');

        $projectRepo     = $em->getRepository(Project::class);
        $contributorRepo = $em->getRepository(Contributor::class);
        $timesheetRepo   = $em->getRepository(Timesheet::class);

        // KPIs globaux
        $totalProjects     = $projectRepo->count([]);
        $activeProjects    = $projectRepo->countActiveProjects();
        $totalContributors = $contributorRepo->countActiveContributors();
        $totalRevenue      = $projectRepo->getTotalRevenue();
        $monthlyHours      = $timesheetRepo->getTotalHoursForMonth($currentMonth, $endMonth);

        // Projets récents
        $recentProjects = $projectRepo->findRecentProjects(5);

        // Mes temps récents
        $myRecentTimesheets = [];
        if ($contributor) {
            $myRecentTimesheets = $timesheetRepo->findRecentByContributor($contributor, 5);
        }

        return [
            'totalProjects'      => $totalProjects,
            'activeProjects'     => $activeProjects,
            'totalContributors'  => $totalContributors,
            'totalRevenue'       => $totalRevenue,
            'monthlyHours'       => $monthlyHours,
            'recentProjects'     => $recentProjects,
            'myRecentTimesheets' => $myRecentTimesheets,
        ];
    }
}
