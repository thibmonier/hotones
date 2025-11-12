<?php

declare(strict_types=1);

namespace App\Controller\Staffing;

use App\Repository\ContributorRepository;
use App\Repository\StaffingMetricsRepository;
use DateInterval;
use DateTime;
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
        private ContributorRepository $contributorRepo
    ) {
    }

    #[Route('/dashboard', name: 'staffing_dashboard', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // Filtres depuis la requête
        $profileId     = $request->query->get('profile');
        $contributorId = $request->query->get('contributor');
        $granularity   = $request->query->get('granularity', 'monthly');

        // Période : -6 mois à aujourd'hui par défaut
        $startDate = new DateTime();
        $startDate->sub(new DateInterval('P6M'));
        $startDate->modify('first day of this month');

        $endDate = new DateTime();
        $endDate->modify('last day of this month');

        // Récupérer les métriques agrégées pour les graphiques
        $metrics = $this->staffingRepo->getAggregatedMetricsByPeriod(
            $startDate,
            $endDate,
            $granularity,
        );

        // Récupérer les métriques par profil
        $metricsByProfile = $this->staffingRepo->getMetricsByProfile(
            $startDate,
            $endDate,
            $granularity,
        );

        // Récupérer les métriques par contributeur
        $metricsByContributor = $this->staffingRepo->getMetricsByContributor(
            $startDate,
            $endDate,
            $granularity,
        );

        // Récupérer les contributeurs actifs pour les filtres
        $contributors = $this->contributorRepo->findActiveContributors();

        // Préparer les données pour Chart.js
        $chartData = $this->prepareChartData($metrics);

        return $this->render('staffing/dashboard.html.twig', [
            'chart_data'             => $chartData,
            'metrics_by_profile'     => $metricsByProfile,
            'metrics_by_contributor' => $metricsByContributor,
            'contributors'           => $contributors,
            'selected_profile'       => $profileId,
            'selected_contributor'   => $contributorId,
            'selected_granularity'   => $granularity,
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
}
