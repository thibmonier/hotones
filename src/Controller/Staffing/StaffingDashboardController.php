<?php

declare(strict_types=1);

namespace App\Controller\Staffing;

use App\Entity\Profile;
use App\Repository\ContributorRepository;
use App\Repository\StaffingMetricsRepository;
use DateInterval;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/staffing')]
#[IsGranted('ROLE_USER')]
class StaffingDashboardController extends AbstractController
{
    public function __construct(
        private StaffingMetricsRepository $staffingRepo,
        private ContributorRepository $contributorRepo,
        private ManagerRegistry $doctrine,
    ) {
    }

    #[Route('/dashboard', name: 'staffing_dashboard', methods: ['GET'])]
    public function index(Request $request): Response
    {
        return $this->renderDashboard($request, 'standard');
    }

    #[Route('/dashboard/annual', name: 'staffing_dashboard_annual', methods: ['GET'])]
    public function annual(Request $request): Response
    {
        return $this->renderDashboard($request, 'annual');
    }

    private function renderDashboard(Request $request, string $viewMode): Response
    {
        // Filtres depuis la requête
        $profileId     = $request->query->get('profile');
        $contributorId = $request->query->get('contributor');
        $granularity   = $request->query->get('granularity', 'monthly');
        $year          = (int) $request->query->get('year', date('Y'));

        // Période selon le mode de vue
        if ($viewMode === 'annual') {
            // Vue annuelle : toute l'année sélectionnée
            $startDate = new DateTime("$year-01-01");
            $endDate   = new DateTime("$year-12-31");
            // Forcer la granularité hebdomadaire pour la vue annuelle
            $granularity = 'weekly';
        } else {
            // Vue standard : -6 mois à aujourd'hui
            $startDate = new DateTime();
            $startDate->sub(new DateInterval('P6M'));
            $startDate->modify('first day of this month');

            $endDate = new DateTime();
            $endDate->modify('last day of this month');
        }

        // Charger les entités associées aux filtres (si fournies)
        $selectedProfile     = null;
        $selectedContributor = null;

        if ($profileId) {
            $selectedProfile = $this->doctrine->getRepository(Profile::class)->find($profileId);
        }

        if ($contributorId) {
            $selectedContributor = $this->contributorRepo->find($contributorId);
        }

        // Récupérer les métriques agrégées pour les graphiques
        $metrics = $this->staffingRepo->getAggregatedMetricsByPeriod(
            $startDate,
            $endDate,
            $granularity,
            $selectedProfile,
            $selectedContributor,
        );

        // Récupérer les métriques par profil
        $metricsByProfile = $this->staffingRepo->getMetricsByProfile(
            $startDate,
            $endDate,
            $granularity,
            $selectedProfile,
            $selectedContributor,
        );

        // Récupérer les métriques par contributeur
        $metricsByContributor = $this->staffingRepo->getMetricsByContributor(
            $startDate,
            $endDate,
            $granularity,
            $selectedProfile,
            $selectedContributor,
        );

        // Données spécifiques pour la vue annuelle
        $weeklyOccupancy  = [];
        $weeklyGlobalTACE = [];
        if ($viewMode === 'annual') {
            $weeklyOccupancy  = $this->staffingRepo->getWeeklyOccupancyByContributor($year, $selectedProfile);
            $weeklyGlobalTACE = $this->staffingRepo->getWeeklyGlobalTACE($year, $selectedProfile);
        }

        // Récupérer les profils et contributeurs actifs pour les filtres
        $profileRepo = $this->doctrine->getRepository(Profile::class);
        $profiles    = $profileRepo->findBy(['active' => true], ['name' => 'ASC']);

        if ($selectedProfile) {
            $contributors = $this->contributorRepo->findActiveContributorsByProfile($selectedProfile);
        } else {
            $contributors = $this->contributorRepo->findActiveContributors();
        }

        // Préparer les données pour Chart.js
        $chartData = $this->prepareChartData($metrics);

        // Préparer les données pour le graphique TACE global hebdomadaire
        $weeklyTaceChartData = $this->prepareWeeklyTaceChartData($weeklyGlobalTACE);

        // Années disponibles pour le sélecteur
        $currentYear    = (int) date('Y');
        $availableYears = range($currentYear - 3, $currentYear + 1);

        return $this->render('staffing/dashboard.html.twig', [
            'chart_data'             => $chartData,
            'metrics_by_profile'     => $metricsByProfile,
            'metrics_by_contributor' => $metricsByContributor,
            'weekly_occupancy'       => $weeklyOccupancy,
            'weekly_tace_chart_data' => $weeklyTaceChartData,
            'profiles'               => $profiles,
            'contributors'           => $contributors,
            'selected_profile'       => $profileId,
            'selected_contributor'   => $contributorId,
            'selected_granularity'   => $granularity,
            'selected_year'          => $year,
            'selected_view_mode'     => $viewMode,
            'available_years'        => $availableYears,
            'start_date'             => $startDate,
            'end_date'               => $endDate,
        ]);
    }

    /**
     * Prépare les données pour les graphiques Chart.js.
     */
    private function prepareChartData(array $metrics): array
    {
        $labels        = [];
        $staffingRates = [];
        $taceRates     = [];

        foreach ($metrics as $metric) {
            $labels[]        = $metric['yearMonth'];
            $staffingRates[] = (float) $metric['staffingRate'];
            $taceRates[]     = (float) $metric['tace'];
        }

        return [
            'labels'        => $labels,
            'staffingRates' => $staffingRates,
            'taceRates'     => $taceRates,
        ];
    }

    /**
     * Prépare les données pour le graphique TACE global hebdomadaire.
     */
    private function prepareWeeklyTaceChartData(array $weeklyTace): array
    {
        $labels = [];
        $tace   = [];

        foreach ($weeklyTace as $week) {
            $labels[] = $week['weekNumber'];
            $tace[]   = (float) $week['tace'];
        }

        return [
            'labels' => $labels,
            'tace'   => $tace,
        ];
    }
}
