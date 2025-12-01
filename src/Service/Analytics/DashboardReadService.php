<?php

declare(strict_types=1);

namespace App\Service\Analytics;

use App\Entity\Analytics\FactProjectMetrics;
use App\Service\MetricsCalculationService as RealTimeMetricsService;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service de lecture des métriques depuis le modèle en étoile.
 * Lit les données pré-calculées depuis FactProjectMetrics.
 * Fallback vers calcul temps réel si données manquantes.
 */
readonly class DashboardReadService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RealTimeMetricsService $realTimeService,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Récupère les KPIs pour une période donnée.
     *
     * @param DateTimeInterface|null $startDate Date de début (null = début de l'année)
     * @param DateTimeInterface|null $endDate   Date de fin (null = aujourd'hui)
     * @param array                  $filters   Filtres additionnels (type, chef projet, commercial, etc.)
     *
     * @return array KPIs agrégés
     */
    public function getKPIs(?DateTimeInterface $startDate = null, ?DateTimeInterface $endDate = null, array $filters = []): array
    {
        // Tentative de lecture depuis le modèle en étoile
        $metrics = $this->readFromStarSchema($startDate, $endDate, $filters);

        // Si pas de données, fallback vers calcul temps réel
        if (empty($metrics['revenue']['total_revenue']) && empty($metrics['revenue']['total_cost'])) {
            $this->logger->warning('Aucune donnée dans le modèle en étoile, fallback vers calcul temps réel', [
                'start' => $startDate?->format('Y-m-d'),
                'end'   => $endDate?->format('Y-m-d'),
            ]);

            return $this->realTimeService->calculateKPIs($startDate, $endDate, $filters);
        }

        return $metrics;
    }

    /**
     * Récupère l'évolution mensuelle sur N mois.
     *
     * @param int   $months  Nombre de mois à récupérer (défaut 12)
     * @param array $filters Filtres additionnels
     *
     * @return array Évolution par mois [['month' => 'Jan 2025', 'revenue' => 10000, ...], ...]
     */
    public function getMonthlyEvolution(int $months = 12, array $filters = []): array
    {
        $endDate   = new DateTime('last day of this month');
        $startDate = (clone $endDate)->modify("-{$months} months")->modify('first day of this month');

        // Lire depuis le modèle en étoile
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select(
            'dt.year',
            'dt.month',
            'dt.monthName',
            'SUM(f.totalRevenue) as totalRevenue',
            'SUM(f.totalCosts) as totalCosts',
            'SUM(f.grossMargin) as grossMargin',
        )
            ->from(FactProjectMetrics::class, 'f')
            ->join('f.dimTime', 'dt')
            ->where('dt.date BETWEEN :start AND :end')
            ->andWhere('f.granularity = :granularity')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->setParameter('granularity', 'monthly')
            ->groupBy('dt.year', 'dt.month', 'dt.monthName')
            ->orderBy('dt.year', 'ASC')
            ->addOrderBy('dt.month', 'ASC');

        // Appliquer les filtres si fournis
        $this->applyFilters($qb, $filters);

        $results = $qb->getQuery()->getResult();

        // Si pas de données, fallback
        if (empty($results)) {
            $this->logger->warning('Pas de données d\'évolution dans le modèle, fallback temps réel');

            return $this->realTimeService->calculateMonthlyEvolution($months, $filters);
        }

        // Formatter les résultats
        return array_map(function ($row) {
            return [
                'month'       => $row['monthName'],
                'month_label' => $row['monthName'], // Pour le graphique Chart.js
                'revenue'     => (float) $row['totalRevenue'],
                'cost'        => (float) $row['totalCosts'], // Singulier pour Chart.js
                'costs'       => (float) $row['totalCosts'], // Pluriel pour compatibilité
                'margin'      => (float) $row['grossMargin'],
            ];
        }, $results);
    }

    /**
     * Lit les KPIs agrégés depuis le modèle en étoile.
     */
    private function readFromStarSchema(?DateTimeInterface $startDate, ?DateTimeInterface $endDate, array $filters): array
    {
        // Dates par défaut
        $startDate ??= new DateTime('first day of January this year');
        $endDate   ??= new DateTime();

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select(
            'SUM(f.totalRevenue) as totalRevenue',
            'SUM(f.totalCosts) as totalCosts',
            'SUM(f.grossMargin) as grossMargin',
            'AVG(f.marginPercentage) as avgMarginPercentage',
            'SUM(f.projectCount) as totalProjects',
            'SUM(f.activeProjectCount) as activeProjects',
            'SUM(f.completedProjectCount) as completedProjects',
            'SUM(f.orderCount) as totalOrders',
            'SUM(f.pendingOrderCount) as pendingOrders',
            'SUM(f.wonOrderCount) as wonOrders',
            'SUM(f.pendingRevenue) as pendingRevenue',
            'SUM(f.totalWorkedDays) as totalWorkedDays',
            'SUM(f.totalSoldDays) as totalSoldDays',
            'AVG(f.utilizationRate) as avgUtilization',
        )
            ->from(FactProjectMetrics::class, 'f')
            ->join('f.dimTime', 'dt')
            ->where('dt.date BETWEEN :start AND :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate);

        // Appliquer les filtres
        $this->applyFilters($qb, $filters);

        $result = $qb->getQuery()->getSingleResult();

        // Retourner la même structure que l'ancien service pour compatibilité template
        return [
            'period' => [
                'start' => $startDate,
                'end'   => $endDate,
            ],
            'revenue' => [
                'total_revenue' => (float) ($result['totalRevenue'] ?? 0),
                'total_cost'    => (float) ($result['totalCosts'] ?? 0),
                'total_margin'  => (float) ($result['grossMargin'] ?? 0),
                'margin_rate'   => (float) ($result['avgMarginPercentage'] ?? 0),
            ],
            'projects' => [
                'total'     => (int) ($result['totalProjects'] ?? 0),
                'active'    => (int) ($result['activeProjects'] ?? 0),
                'completed' => (int) ($result['completedProjects'] ?? 0),
                'in_period' => (int) ($result['totalProjects'] ?? 0),
                'by_type'   => [
                    'forfait' => 0, // TODO: récupérer depuis le modèle si nécessaire
                    'regie'   => 0,
                ],
                'by_client_type' => [
                    'internal' => 0,
                    'client'   => 0,
                ],
                'by_category' => [], // TODO: récupérer depuis le modèle si nécessaire
            ],
            'orders' => [
                'total'           => (int) ($result['totalOrders'] ?? 0),
                'pending'         => (int) ($result['pendingOrders'] ?? 0),
                'won'             => (int) ($result['wonOrders'] ?? 0),
                'signed'          => 0, // TODO: ajouter signedOrderCount dans le modèle
                'lost'            => 0, // TODO: ajouter lostOrderCount dans le modèle
                'conversion_rate' => $result['totalOrders'] > 0
                    ? round(($result['wonOrders'] / $result['totalOrders']) * 100, 2)
                    : 0,
                'pending_revenue' => (float) ($result['pendingRevenue'] ?? 0),
            ],
            'contributors' => [
                'active' => 0, // TODO: ajouter dans le modèle si nécessaire
                'top'    => [], // TODO: ajouter dans le modèle si nécessaire
            ],
            'time' => [
                'total_hours'            => (float) ($result['totalWorkedDays'] ?? 0) * 8, // Conversion jours -> heures
                'total_days'             => (float) ($result['totalWorkedDays'] ?? 0),
                'working_days_in_period' => 0, // TODO: calculer depuis dates période
                'theoretical_capacity'   => (float) ($result['totalSoldDays'] ?? 0) * 8, // Conversion jours -> heures
                'occupation_rate'        => (float) ($result['avgUtilization'] ?? 0),
            ],
        ];
    }

    /**
     * Applique les filtres dynamiques sur le QueryBuilder.
     */
    private function applyFilters($qb, array $filters): void
    {
        // Filtre par type de projet
        if (!empty($filters['projectType'])) {
            $qb->join('f.dimProjectType', 'dpt')
                ->andWhere('dpt.projectType = :projectType')
                ->setParameter('projectType', $filters['projectType']);
        }

        // Filtre par chef de projet
        if (!empty($filters['projectManager'])) {
            $qb->join('f.dimProjectManager', 'dpm')
                ->andWhere('dpm.contributorId = :projectManager')
                ->setParameter('projectManager', $filters['projectManager']);
        }

        // Filtre par commercial
        if (!empty($filters['salesPerson'])) {
            $qb->join('f.dimSalesPerson', 'dsp')
                ->andWhere('dsp.contributorId = :salesPerson')
                ->setParameter('salesPerson', $filters['salesPerson']);
        }

        // Filtre par directeur de projet
        if (!empty($filters['projectDirector'])) {
            $qb->join('f.dimProjectDirector', 'dpd')
                ->andWhere('dpd.contributorId = :projectDirector')
                ->setParameter('projectDirector', $filters['projectDirector']);
        }

        // Filtre par technologie (nécessite join sur project)
        if (!empty($filters['technology'])) {
            $qb->join('f.project', 'p')
                ->join('p.technologies', 't')
                ->andWhere('t.id = :technology')
                ->setParameter('technology', $filters['technology']);
        }

        // Filtre par catégorie de service
        if (!empty($filters['serviceCategory'])) {
            $qb->join('f.project', 'p')
                ->join('p.category', 'sc')
                ->andWhere('sc.id = :serviceCategory')
                ->setParameter('serviceCategory', $filters['serviceCategory']);
        }
    }
}
