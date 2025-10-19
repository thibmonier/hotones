<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Analytics\FactProjectMetrics;
use App\Entity\Analytics\DimTime;
use App\Entity\Analytics\DimProjectType;
use App\Entity\Analytics\DimContributor;
use App\Service\Analytics\MetricsCalculationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/analytics')]
#[IsGranted('ROLE_USER')]
class AnalyticsController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MetricsCalculationService $metricsService
    ) {}

    #[Route('/dashboard', name: 'analytics_dashboard')]
    public function dashboard(Request $request): Response
    {
        $granularity = $request->query->get('granularity', 'monthly');
        $year = (int) $request->query->get('year', date('Y'));
        $month = $request->query->get('month') ? (int) $request->query->get('month') : null;
        
        // Filtres optionnels
        $projectType = $request->query->get('project_type') ?: null;
        $projectManager = $request->query->get('project_manager') ? (int) $request->query->get('project_manager') : null;
        $salesPerson = $request->query->get('sales_person') ? (int) $request->query->get('sales_person') : null;

        // Récupérer les métriques selon les filtres
        $metrics = $this->getMetrics($granularity, $year, $month, $projectType, $projectManager, $salesPerson);
        
        // Calculer les KPIs principaux
        $kpis = $this->calculateKPIs($metrics);
        
        // Données pour les graphiques
        $chartData = $this->prepareChartData($metrics, $granularity);
        
        // Listes pour les filtres
        $filterData = $this->getFilterData();

        return $this->render('analytics/dashboard.html.twig', [
            'kpis' => $kpis,
            'chartData' => $chartData,
            'metrics' => $metrics,
            'filters' => [
                'granularity' => $granularity,
                'year' => $year,
                'month' => $month,
                'project_type' => $projectType,
                'project_manager' => $projectManager,
                'sales_person' => $salesPerson,
            ],
            'filterData' => $filterData,
        ]);
    }

    #[Route('/data', name: 'analytics_data', methods: ['GET'])]
    public function getData(Request $request): JsonResponse
    {
        $granularity = $request->query->get('granularity', 'monthly');
        $year = (int) $request->query->get('year', date('Y'));
        $month = $request->query->get('month') ? (int) $request->query->get('month') : null;
        
        $projectType = $request->query->get('project_type') ?: null;
        $projectManager = $request->query->get('project_manager') ? (int) $request->query->get('project_manager') : null;
        $salesPerson = $request->query->get('sales_person') ? (int) $request->query->get('sales_person') : null;

        $metrics = $this->getMetrics($granularity, $year, $month, $projectType, $projectManager, $salesPerson);
        $kpis = $this->calculateKPIs($metrics);
        $chartData = $this->prepareChartData($metrics, $granularity);

        return new JsonResponse([
            'kpis' => $kpis,
            'chartData' => $chartData,
            'success' => true
        ]);
    }

    #[Route('/recalculate/{year}', name: 'analytics_recalculate', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function recalculate(int $year): JsonResponse
    {
        try {
            $this->metricsService->recalculateMetricsForYear($year);
            return new JsonResponse(['success' => true, 'message' => 'Métriques recalculées avec succès']);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function getMetrics(
        string $granularity,
        int $year,
        ?int $month = null,
        ?string $projectType = null,
        ?int $projectManager = null,
        ?int $salesPerson = null
    ): array {
        $qb = $this->entityManager->getRepository(FactProjectMetrics::class)
            ->createQueryBuilder('f')
            ->join('f.dimTime', 't')
            ->join('f.dimProjectType', 'pt')
            ->leftJoin('f.dimProjectManager', 'pm')
            ->leftJoin('f.dimSalesPerson', 'sp')
            ->where('f.granularity = :granularity')
            ->andWhere('t.year = :year')
            ->setParameter('granularity', $granularity)
            ->setParameter('year', $year)
            ->orderBy('t.date', 'ASC');

        if ($month !== null) {
            $qb->andWhere('t.month = :month')->setParameter('month', $month);
        }

        if ($projectType) {
            $qb->andWhere('pt.projectType = :projectType')->setParameter('projectType', $projectType);
        }

        if ($projectManager) {
            $qb->andWhere('pm.id = :projectManager')->setParameter('projectManager', $projectManager);
        }

        if ($salesPerson) {
            $qb->andWhere('sp.id = :salesPerson')->setParameter('salesPerson', $salesPerson);
        }

        return $qb->getQuery()->getResult();
    }

    private function calculateKPIs(array $metrics): array
    {
        $totals = [
            'totalRevenue' => '0.00',
            'totalCosts' => '0.00',
            'grossMargin' => '0.00',
            'marginPercentage' => '0.00',
            'pendingRevenue' => '0.00',
            'projectCount' => 0,
            'activeProjectCount' => 0,
            'completedProjectCount' => 0,
            'pendingOrderCount' => 0,
            'wonOrderCount' => 0,
            'averageOrderValue' => '0.00',
            'utilizationRate' => '0.00',
        ];

        foreach ($metrics as $metric) {
            $totals['totalRevenue'] = bcadd($totals['totalRevenue'], $metric->getTotalRevenue(), 2);
            $totals['totalCosts'] = bcadd($totals['totalCosts'], $metric->getTotalCosts(), 2);
            $totals['pendingRevenue'] = bcadd($totals['pendingRevenue'], $metric->getPendingRevenue(), 2);
            $totals['projectCount'] += $metric->getProjectCount();
            $totals['activeProjectCount'] += $metric->getActiveProjectCount();
            $totals['completedProjectCount'] += $metric->getCompletedProjectCount();
            $totals['pendingOrderCount'] += $metric->getPendingOrderCount();
            $totals['wonOrderCount'] += $metric->getWonOrderCount();
        }

        // Calcul de la marge globale
        $totals['grossMargin'] = bcsub($totals['totalRevenue'], $totals['totalCosts'], 2);
        
        if (bccomp($totals['totalRevenue'], '0', 2) > 0) {
            $totals['marginPercentage'] = bcmul(
                bcdiv($totals['grossMargin'], $totals['totalRevenue'], 4),
                '100',
                2
            );
        }

        // Calcul de la valeur moyenne des devis
        $totalOrders = $totals['pendingOrderCount'] + $totals['wonOrderCount'];
        if ($totalOrders > 0) {
            $totalOrderValue = bcadd($totals['totalRevenue'], $totals['pendingRevenue'], 2);
            $totals['averageOrderValue'] = bcdiv($totalOrderValue, (string) $totalOrders, 2);
        }

        return $totals;
    }

    private function prepareChartData(array $metrics, string $granularity): array
    {
        $revenueData = [];
        $marginData = [];
        $projectCountData = [];
        $labels = [];

        foreach ($metrics as $metric) {
            $dimTime = $metric->getDimTime();
            
            $label = match ($granularity) {
                'monthly' => $dimTime->getMonthName(),
                'quarterly' => $dimTime->getQuarterName(),
                'yearly' => (string) $dimTime->getYear(),
                default => $dimTime->getMonthName()
            };

            $labels[] = $label;
            $revenueData[] = (float) $metric->getTotalRevenue();
            $marginData[] = (float) $metric->getMarginPercentage();
            $projectCountData[] = $metric->getProjectCount();
        }

        return [
            'labels' => $labels,
            'revenue' => $revenueData,
            'margin' => $marginData,
            'projectCount' => $projectCountData,
        ];
    }

    private function getFilterData(): array
    {
        $projectTypes = $this->entityManager->getRepository(DimProjectType::class)
            ->createQueryBuilder('pt')
            ->select('pt.projectType')
            ->distinct()
            ->getQuery()
            ->getResult();

        $projectManagers = $this->entityManager->getRepository(DimContributor::class)
            ->createQueryBuilder('c')
            ->where('c.role = :role')
            ->andWhere('c.isActive = true')
            ->setParameter('role', 'project_manager')
            ->getQuery()
            ->getResult();

        $salesPersons = $this->entityManager->getRepository(DimContributor::class)
            ->createQueryBuilder('c')
            ->where('c.role = :role')
            ->andWhere('c.isActive = true')
            ->setParameter('role', 'sales_person')
            ->getQuery()
            ->getResult();

        return [
            'projectTypes' => array_column($projectTypes, 'projectType'),
            'projectManagers' => $projectManagers,
            'salesPersons' => $salesPersons,
        ];
    }
}