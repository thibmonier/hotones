<?php

declare(strict_types=1);

namespace App\Controller\Analytics;

use App\Message\RecalculateMetricsMessage;
use App\Service\Analytics\DashboardReadService;
use App\Service\ExcelExportService;
use DateTime;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/analytics')]
#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    public function __construct(
        private readonly DashboardReadService $dashboardReadService,
        private readonly ExcelExportService $excelExportService,
        private readonly MessageBusInterface $messageBus,
        private readonly \App\Repository\ProjectRepository $projectRepository
    ) {
    }

    #[Route('/dashboard', name: 'analytics_dashboard', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // Récupérer la période depuis l'URL en priorité
        $period = $request->query->get('period');

        // Si pas de période dans l'URL, utiliser la session ou 'month' par défaut
        if ($period === null) {
            $session = $request->getSession();
            $period  = $session->get('dashboard_period', 'month');
        } else {
            // Si période fournie dans l'URL, la sauvegarder en session
            $request->getSession()->set('dashboard_period', $period);
        }

        $customStart = $request->query->get('start_date');
        $customEnd   = $request->query->get('end_date');

        // Calculer les dates selon la période sélectionnée
        [$startDate, $endDate] = $this->calculatePeriodDates($period, $customStart, $customEnd);

        // Filtres (Lot 3.2)
        $filters = [
            'project_type'        => $request->query->get('project_type') ?: null, // 'forfait' | 'regie'
            'is_internal'         => $request->query->get('client_type') === 'internal' ? true : ($request->query->get('client_type') === 'client' ? false : null),
            'project_manager_id'  => $request->query->get('project_manager_id') ? (int) $request->query->get('project_manager_id') : null,
            'sales_person_id'     => $request->query->get('sales_person_id') ? (int) $request->query->get('sales_person_id') : null,
            'technology_id'       => $request->query->get('technology_id') ? (int) $request->query->get('technology_id') : null,
            'service_category_id' => $request->query->get('service_category_id') ? (int) $request->query->get('service_category_id') : null,
        ];

        // Récupérer les KPIs depuis le modèle en étoile (avec fallback temps réel)
        $kpis = $this->dashboardReadService->getKPIs($startDate, $endDate, $filters);

        // Adapter le nombre de mois pour l'évolution selon la période
        $monthsToShow = match ($period) {
            'today', 'week' => 3,      // 3 mois de contexte pour les courtes périodes
            'month'   => 12,              // 12 mois glissants
            'quarter' => 12,            // 12 mois pour voir les tendances
            'year'    => 12,               // 12 derniers mois
            'custom'  => max(3, min(12, (int) ceil($startDate->diff($endDate)->days / 30))), // Entre 3 et 12 mois
            default   => 12,
        };

        // Récupérer l'évolution mensuelle depuis le modèle en étoile
        $monthlyEvolution = $this->dashboardReadService->getMonthlyEvolution($monthsToShow, $filters);

        // Options de filtres (listes déroulantes)
        $filterOptions = [
            'project_managers'   => $this->projectRepository->getDistinctProjectManagersBetweenDates($startDate, $endDate),
            'sales_persons'      => $this->projectRepository->getDistinctSalesPersonsBetweenDates($startDate, $endDate),
            'technologies'       => $this->projectRepository->getDistinctTechnologiesBetweenDates($startDate, $endDate),
            'service_categories' => $this->projectRepository->getDistinctServiceCategoriesBetweenDates($startDate, $endDate),
        ];

        return $this->render('analytics/dashboard.html.twig', [
            'kpis'              => $kpis,
            'monthly_evolution' => $monthlyEvolution,
            'selected_period'   => $period,
            'start_date'        => $startDate,
            'end_date'          => $endDate,
            'filters'           => $filters,
            'filter_options'    => $filterOptions,
        ]);
    }

    /**
     * Calcule les dates de début et fin selon la période sélectionnée.
     */
    private function calculatePeriodDates(string $period, ?string $customStart, ?string $customEnd): array
    {
        $now = new DateTime();

        return match ($period) {
            'today' => [
                new DateTime('today'),
                new DateTime('today'),
            ],
            'week' => [
                (clone $now)->modify('monday this week'),
                (clone $now)->modify('sunday this week'),
            ],
            'month' => [
                new DateTime('first day of this month'),
                new DateTime('last day of this month'),
            ],
            'quarter' => [
                $this->getQuarterStart($now),
                $this->getQuarterEnd($now),
            ],
            'year' => [
                new DateTime('first day of January this year'),
                new DateTime('last day of December this year'),
            ],
            'custom' => [
                $customStart ? new DateTime($customStart) : new DateTime('first day of this month'),
                $customEnd ? new DateTime($customEnd) : new DateTime('last day of this month'),
            ],
            default => [
                new DateTime('first day of this month'),
                new DateTime('last day of this month'),
            ],
        };
    }

    /**
     * Retourne le premier jour du trimestre en cours.
     */
    private function getQuarterStart(DateTime $date): DateTime
    {
        $month             = (int) $date->format('n');
        $quarterStartMonth = (int) (floor(($month - 1) / 3) * 3) + 1;

        return (clone $date)->setDate(
            (int) $date->format('Y'),
            $quarterStartMonth,
            1,
        )->setTime(0, 0);
    }

    /**
     * Retourne le dernier jour du trimestre en cours.
     */
    private function getQuarterEnd(DateTime $date): DateTime
    {
        $quarterStart = $this->getQuarterStart($date);

        return (clone $quarterStart)->modify('+3 months')->modify('-1 day')->setTime(23, 59, 59);
    }

    #[Route('/export-excel', name: 'analytics_dashboard_export_excel', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function exportExcel(Request $request): Response
    {
        // Récupérer les mêmes paramètres que pour le dashboard
        $period      = $request->query->get('period', 'month');
        $customStart = $request->query->get('start_date');
        $customEnd   = $request->query->get('end_date');

        [$startDate, $endDate] = $this->calculatePeriodDates($period, $customStart, $customEnd);

        // Filtres
        $filters = [
            'project_type'        => $request->query->get('project_type') ?: null,
            'is_internal'         => $request->query->get('client_type') === 'internal' ? true : ($request->query->get('client_type') === 'client' ? false : null),
            'project_manager_id'  => $request->query->get('project_manager_id') ? (int) $request->query->get('project_manager_id') : null,
            'sales_person_id'     => $request->query->get('sales_person_id') ? (int) $request->query->get('sales_person_id') : null,
            'technology_id'       => $request->query->get('technology_id') ? (int) $request->query->get('technology_id') : null,
            'service_category_id' => $request->query->get('service_category_id') ? (int) $request->query->get('service_category_id') : null,
        ];

        // Récupérer les données
        $kpis             = $this->dashboardReadService->getKPIs($startDate, $endDate, $filters);
        $monthlyEvolution = $this->dashboardReadService->getMonthlyEvolution(12, $filters);

        // Générer et télécharger le fichier Excel
        return $this->excelExportService->exportDashboard($kpis, $monthlyEvolution, $startDate, $endDate, $filters);
    }

    #[Route('/recalculate', name: 'analytics_recalculate', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function recalculate(Request $request): JsonResponse
    {
        $period      = $request->request->get('period', date('Y'));
        $granularity = $request->request->get('granularity', 'monthly');

        try {
            // Dispatch le message de recalcul
            $this->messageBus->dispatch(
                new RecalculateMetricsMessage($period, $granularity),
            );

            $this->addFlash('success', 'Recalcul des métriques lancé en arrière-plan.');

            return new JsonResponse([
                'success' => true,
                'message' => 'Recalcul des métriques lancé en arrière-plan.',
            ]);
        } catch (Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
