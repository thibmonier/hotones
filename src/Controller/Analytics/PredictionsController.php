<?php

declare(strict_types=1);

namespace App\Controller\Analytics;

use App\Repository\NotificationRepository;
use App\Repository\ProjectRepository;
use App\Service\ProfitabilityPredictor;
use App\Service\WorkloadPredictionService;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[Route('/analytics/predictions')]
#[IsGranted('ROLE_MANAGER')]
class PredictionsController extends AbstractController
{
    public function __construct(
        private readonly ProjectRepository $projectRepository,
        private readonly NotificationRepository $notificationRepository,
        private readonly ProfitabilityPredictor $profitabilityPredictor,
        private readonly WorkloadPredictionService $workloadPredictionService,
        private readonly CacheInterface $cache,
    ) {
    }

    #[Route('', name: 'analytics_predictions', methods: ['GET'])]
    public function index(): Response
    {
        // Cache predictions data for 10 minutes
        $data = $this->cache->get('analytics_predictions_data', function (ItemInterface $item) {
            $item->expiresAfter(600); // 10 minutes

            return [
                'profitability_predictions' => $this->getProfitabilityPredictions(),
                'workload_data'             => $this->getWorkloadData(),
                'alert_stats'               => $this->getAlertStats(),
            ];
        });

        $recentAlerts = $this->getRecentAlerts();

        return $this->render('analytics/predictions.html.twig', [
            'profitability_predictions' => $data['profitability_predictions'],
            'workload_data'             => $data['workload_data'],
            'alert_stats'               => $data['alert_stats'],
            'recent_alerts'             => $recentAlerts,
        ]);
    }

    /**
     * Get profitability predictions for active projects.
     */
    private function getProfitabilityPredictions(): array
    {
        $projects = $this->projectRepository->findBy(
            ['status' => 'en_cours'],
            ['id' => 'DESC'],
            20, // Limit to 20 projects for performance
        );

        $predictions = [];

        foreach ($projects as $project) {
            $prediction = $this->profitabilityPredictor->predictProfitability($project);

            if ($prediction['canPredict'] ?? false) {
                $predictions[] = [
                    'project'    => $project,
                    'prediction' => $prediction,
                ];
            }
        }

        return $predictions;
    }

    /**
     * Get workload data for next 6 months.
     */
    private function getWorkloadData(): array
    {
        // Inclure la charge confirmée pour avoir une vue complète
        $pipelineAnalysis = $this->workloadPredictionService->analyzePipeline([], [], includeConfirmed: true);

        // Prepare data for Chart.js stacked bar chart
        $labels    = [];
        $confirmed = [];
        $potential = [];
        $now       = new DateTimeImmutable();

        for ($i = 0; $i < 6; ++$i) {
            $month    = $now->modify("+{$i} months");
            $monthKey = $month->format('Y-m');
            $labels[] = $month->format('M Y');

            $data        = $pipelineAnalysis['workloadByMonth'][$monthKey] ?? ['confirmed' => 0, 'potential' => 0];
            $confirmed[] = round($data['confirmed'], 1);
            $potential[] = round($data['potential'], 1);
        }

        return [
            'labels'   => $labels,
            'datasets' => [
                [
                    'label'           => 'Charge confirmée',
                    'data'            => $confirmed,
                    'backgroundColor' => 'rgba(54, 162, 235, 0.8)',
                    'borderColor'     => 'rgba(54, 162, 235, 1)',
                    'borderWidth'     => 1,
                ],
                [
                    'label'           => 'Charge potentielle',
                    'data'            => $potential,
                    'backgroundColor' => 'rgba(255, 206, 86, 0.6)',
                    'borderColor'     => 'rgba(255, 206, 86, 1)',
                    'borderWidth'     => 1,
                    'borderDash'      => [5, 5],
                ],
            ],
        ];
    }

    /**
     * Get alert statistics by type.
     */
    private function getAlertStats(): array
    {
        $sevenDaysAgo = (new DateTimeImmutable())->modify('-7 days');

        // Count alerts by type in last 7 days using query builder
        $budgetAlerts = $this->notificationRepository->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.type = :type')
            ->andWhere('n.createdAt >= :since')
            ->setParameter('type', 'project_budget_alert')
            ->setParameter('since', $sevenDaysAgo)
            ->getQuery()
            ->getSingleScalarResult();

        $marginAlerts = $this->notificationRepository->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.type = :type')
            ->andWhere('n.createdAt >= :since')
            ->setParameter('type', 'low_margin_alert')
            ->setParameter('since', $sevenDaysAgo)
            ->getQuery()
            ->getSingleScalarResult();

        $overloadAlerts = $this->notificationRepository->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.type = :type')
            ->andWhere('n.createdAt >= :since')
            ->setParameter('type', 'contributor_overload_alert')
            ->setParameter('since', $sevenDaysAgo)
            ->getQuery()
            ->getSingleScalarResult();

        $paymentAlerts = $this->notificationRepository->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.type = :type')
            ->andWhere('n.createdAt >= :since')
            ->setParameter('type', 'payment_due_alert')
            ->setParameter('since', $sevenDaysAgo)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'budget'   => (int) $budgetAlerts,
            'margin'   => (int) $marginAlerts,
            'overload' => (int) $overloadAlerts,
            'payment'  => (int) $paymentAlerts,
            'total'    => (int) ($budgetAlerts + $marginAlerts + $overloadAlerts + $paymentAlerts),
        ];
    }

    /**
     * Get recent alerts (last 7 days).
     */
    private function getRecentAlerts(): array
    {
        $sevenDaysAgo = (new DateTimeImmutable())->modify('-7 days');

        return $this->notificationRepository->createQueryBuilder('n')
            ->where('n.type IN (:types)')
            ->andWhere('n.createdAt >= :since')
            ->setParameter('types', [
                'project_budget_alert',
                'low_margin_alert',
                'contributor_overload_alert',
                'payment_due_alert',
            ])
            ->setParameter('since', $sevenDaysAgo)
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();
    }
}
