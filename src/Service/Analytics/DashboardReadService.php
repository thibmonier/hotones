<?php

declare(strict_types=1);

namespace App\Service\Analytics;

use App\Entity\Analytics\FactProjectMetrics;
use App\Service\MetricsCalculationService as RealTimeMetricsService;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Service de lecture des métriques depuis le modèle en étoile.
 * Lit les données pré-calculées depuis FactProjectMetrics.
 * Fallback vers calcul temps réel si données manquantes.
 * Utilise un cache Redis pour améliorer les performances.
 */
readonly class DashboardReadService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RealTimeMetricsService $realTimeService,
        private LoggerInterface $logger,
        private CacheInterface $analyticsCache
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
        // Dates par défaut pour la clé de cache
        $start = $startDate ?? new DateTime('first day of January this year');
        $end   = $endDate   ?? new DateTime();

        // Clé de cache basée sur les dates et filtres
        $cacheKey = sprintf(
            'analytics_kpis_%s_%s_%s',
            $start->format('Y-m-d'),
            $end->format('Y-m-d'),
            md5(json_encode($filters)),
        );

        return $this->analyticsCache->get($cacheKey, function (ItemInterface $item) use ($startDate, $endDate, $filters) {
            $item->expiresAfter(1800); // 30 minutes

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
        });
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
        // Clé de cache basée sur le nombre de mois et filtres
        $cacheKey = sprintf(
            'analytics_monthly_evolution_%d_%s',
            $months,
            md5(json_encode($filters)),
        );

        return $this->analyticsCache->get($cacheKey, function (ItemInterface $item) use ($months, $filters) {
            $item->expiresAfter(1800); // 30 minutes

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

            $results = $qb->getQuery()->getResult() ?? [];

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
        });
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
            'SUM(f.signedOrderCount) as signedOrders',
            'SUM(f.lostOrderCount) as lostOrders',
            'SUM(f.pendingRevenue) as pendingRevenue',
            'SUM(f.totalWorkedDays) as totalWorkedDays',
            'SUM(f.totalSoldDays) as totalSoldDays',
            'AVG(f.utilizationRate) as avgUtilization',
            'SUM(f.contributorCount) as contributorCount',
        )
            ->from(FactProjectMetrics::class, 'f')
            ->join('f.dimTime', 'dt')
            ->where('dt.date BETWEEN :start AND :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate);

        // Appliquer les filtres
        $this->applyFilters($qb, $filters);

        $result = $qb->getQuery()->getSingleResult();

        // Récupérer les répartitions et top contributeurs
        $byType          = $this->getProjectsByType($startDate, $endDate, $filters);
        $byClientType    = $this->getProjectsByClientType($startDate, $endDate, $filters);
        $byCategory      = $this->getProjectsByCategory($startDate, $endDate, $filters);
        $topContributors = $this->getTopContributors($startDate, $endDate, $filters, 5);
        $workingDays     = $this->calculateWorkingDays($startDate, $endDate);

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
                'total'          => (int) ($result['totalProjects'] ?? 0),
                'active'         => (int) ($result['activeProjects'] ?? 0),
                'completed'      => (int) ($result['completedProjects'] ?? 0),
                'in_period'      => (int) ($result['totalProjects'] ?? 0),
                'by_type'        => $byType,
                'by_client_type' => $byClientType,
                'by_category'    => $byCategory,
            ],
            'orders' => [
                'total'           => (int) ($result['totalOrders'] ?? 0),
                'pending'         => (int) ($result['pendingOrders'] ?? 0),
                'won'             => (int) ($result['wonOrders'] ?? 0),
                'signed'          => (int) ($result['signedOrders'] ?? 0),
                'lost'            => (int) ($result['lostOrders'] ?? 0),
                'conversion_rate' => ($result['totalOrders'] ?? 0) > 0
                    ? round((($result['wonOrders'] ?? 0) / ($result['totalOrders'] ?? 1)) * 100, 2)
                    : 0,
                'pending_revenue' => (float) ($result['pendingRevenue'] ?? 0),
            ],
            'contributors' => [
                'active' => (int) ($result['contributorCount'] ?? 0),
                'top'    => $topContributors,
            ],
            'time' => [
                'total_hours'            => (float) ($result['totalWorkedDays'] ?? 0) * 8, // Conversion jours -> heures
                'total_days'             => (float) ($result['totalWorkedDays'] ?? 0),
                'working_days_in_period' => $workingDays,
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

    /**
     * Récupère la répartition des projets par type (forfait/régie).
     */
    private function getProjectsByType(?DateTimeInterface $startDate, ?DateTimeInterface $endDate, array $filters): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select(
            'dpt.projectType as type',
            'SUM(f.projectCount) as count',
        )
            ->from(FactProjectMetrics::class, 'f')
            ->join('f.dimTime', 'dt')
            ->join('f.dimProjectType', 'dpt')
            ->where('dt.date BETWEEN :start AND :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->groupBy('dpt.projectType');

        $this->applyFilters($qb, $filters);

        $results = $qb->getQuery()->getResult() ?? [];

        // Formater en tableau associatif
        $byType = ['forfait' => 0, 'regie' => 0];
        foreach ($results as $row) {
            $byType[$row['type']] = (int) $row['count'];
        }

        return $byType;
    }

    /**
     * Récupère la répartition des projets par type de client (interne/client).
     */
    private function getProjectsByClientType(?DateTimeInterface $startDate, ?DateTimeInterface $endDate, array $filters): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select(
            'dpt.isInternal',
            'SUM(f.projectCount) as count',
        )
            ->from(FactProjectMetrics::class, 'f')
            ->join('f.dimTime', 'dt')
            ->join('f.dimProjectType', 'dpt')
            ->where('dt.date BETWEEN :start AND :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->groupBy('dpt.isInternal');

        $this->applyFilters($qb, $filters);

        $results = $qb->getQuery()->getResult() ?? [];

        // Formater en tableau associatif
        $byClientType = ['internal' => 0, 'client' => 0];
        foreach ($results as $row) {
            $key                = $row['isInternal'] ? 'internal' : 'client';
            $byClientType[$key] = (int) $row['count'];
        }

        return $byClientType;
    }

    /**
     * Récupère la répartition des projets par catégorie de service.
     */
    private function getProjectsByCategory(?DateTimeInterface $startDate, ?DateTimeInterface $endDate, array $filters): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select(
            'dpt.serviceCategory as category',
            'SUM(f.projectCount) as count',
        )
            ->from(FactProjectMetrics::class, 'f')
            ->join('f.dimTime', 'dt')
            ->join('f.dimProjectType', 'dpt')
            ->where('dt.date BETWEEN :start AND :end')
            ->andWhere('dpt.serviceCategory IS NOT NULL')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->groupBy('dpt.serviceCategory')
            ->orderBy('count', 'DESC');

        $this->applyFilters($qb, $filters);

        $results = $qb->getQuery()->getResult() ?? [];

        // Formater en tableau associatif
        $byCategory = [];
        foreach ($results as $row) {
            $byCategory[$row['category']] = (int) $row['count'];
        }

        return $byCategory;
    }

    /**
     * Récupère les top contributeurs par CA généré.
     *
     * @param int $limit Nombre de contributeurs à retourner (défaut 5)
     */
    private function getTopContributors(?DateTimeInterface $startDate, ?DateTimeInterface $endDate, array $filters, int $limit = 5): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select(
            'dc.id',
            'dc.name',
            'SUM(f.totalRevenue) as totalRevenue',
            'SUM(f.grossMargin) as totalMargin',
            'SUM(f.totalWorkedDays) as totalDays',
        )
            ->from(FactProjectMetrics::class, 'f')
            ->join('f.dimTime', 'dt')
            ->leftJoin('f.dimProjectManager', 'dc')
            ->where('dt.date BETWEEN :start AND :end')
            ->andWhere('dc.id IS NOT NULL')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->groupBy('dc.id', 'dc.name')
            ->orderBy('totalRevenue', 'DESC')
            ->setMaxResults($limit);

        $this->applyFilters($qb, $filters);

        $results = $qb->getQuery()->getResult() ?? [];

        // Formater pour le template
        return array_map(function ($row) {
            return [
                'id'      => $row['id'],
                'name'    => $row['name'],
                'revenue' => (float) $row['totalRevenue'],
                'margin'  => (float) $row['totalMargin'],
                'days'    => (float) $row['totalDays'],
            ];
        }, $results);
    }

    /**
     * Calcule le nombre de jours ouvrés dans une période (hors week-ends).
     */
    private function calculateWorkingDays(DateTimeInterface $startDate, DateTimeInterface $endDate): int
    {
        $start = clone $startDate;
        $end   = clone $endDate;
        $days  = 0;

        while ($start <= $end) {
            // Exclure samedi (6) et dimanche (0)
            $dayOfWeek = (int) $start->format('w');
            if ($dayOfWeek !== 0 && $dayOfWeek !== 6) {
                ++$days;
            }
            $start = $start->modify('+1 day');
        }

        return $days;
    }
}
