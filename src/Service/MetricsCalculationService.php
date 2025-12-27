<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\ContributorRepository;
use App\Repository\OrderRepository;
use App\Repository\ProjectRepository;
use App\Repository\TimesheetRepository;
use DateTime;
use DateTimeInterface;

/**
 * Service de calcul des métriques en temps réel.
 *
 * @deprecated Utilisez App\Service\Analytics\DashboardReadService à la place.
 *             Ce service est maintenant utilisé uniquement comme fallback
 *             quand les données du modèle en étoile ne sont pas disponibles.
 */
class MetricsCalculationService
{
    public function __construct(
        private ProjectRepository $projectRepo,
        private OrderRepository $orderRepo,
        private TimesheetRepository $timesheetRepo,
        private ContributorRepository $contributorRepo
    ) {
    }

    /**
     * Calcule tous les KPIs pour une période donnée.
     *
     * @deprecated Utilisez DashboardReadService::getKPIs() à la place.
     *             Cette méthode est conservée uniquement pour le fallback
     *             quand les données pré-calculées ne sont pas disponibles.
     */
    public function calculateKPIs(?DateTimeInterface $startDate = null, ?DateTimeInterface $endDate = null, array $filters = []): array
    {
        $startDate = $startDate ?? new DateTime('first day of this month');
        $endDate   = $endDate   ?? new DateTime('last day of this month');

        return [
            'period' => [
                'start' => $startDate,
                'end'   => $endDate,
            ],
            'revenue'      => $this->calculateRevenue($startDate, $endDate, $filters),
            'projects'     => $this->calculateProjectMetrics($startDate, $endDate, $filters),
            'orders'       => $this->calculateOrderMetrics($startDate, $endDate, $filters),
            'contributors' => $this->calculateContributorMetrics($startDate, $endDate, $filters),
            'time'         => $this->calculateTimeMetrics($startDate, $endDate, $filters),
        ];
    }

    /**
     * Calcule les métriques de revenus et marges.
     */
    private function calculateRevenue(DateTimeInterface $startDate, DateTimeInterface $endDate, array $filters = []): array
    {
        // Projets filtrés (statut 'active' par défaut pour le CA)
        $projects = $this->getFilteredProjects($startDate, $endDate, $filters, 'active');

        $totalRevenue = '0';
        $totalCost    = '0';
        $totalMargin  = '0';

        foreach ($projects as $project) {
            $revenue = $project->getTotalSoldAmount();
            $cost    = $project->getTotalRealCost();

            $totalRevenue = bcadd($totalRevenue, $revenue, 2);
            $totalCost    = bcadd($totalCost, $cost, 2);
        }

        $totalMargin = bcsub($totalRevenue, $totalCost, 2);
        $marginRate  = bccomp($totalRevenue, '0', 2) > 0
            ? bcmul(bcdiv($totalMargin, $totalRevenue, 4), '100', 2)
            : '0.00';

        return [
            'total_revenue' => (float) $totalRevenue,
            'total_cost'    => (float) $totalCost,
            'total_margin'  => (float) $totalMargin,
            'margin_rate'   => (float) $marginRate,
        ];
    }

    /**
     * Calcule les métriques projets.
     */
    private function calculateProjectMetrics(DateTimeInterface $startDate, DateTimeInterface $endDate, array $filters = []): array
    {
        // Projets dans la période, avec filtres
        $projectsInPeriod = $this->getFilteredProjects($startDate, $endDate, $filters, null);

        // Compter les statuts
        $activeCount    = 0;
        $completedCount = 0;

        // Répartition par type
        $forfaitCount          = 0;
        $regieCount            = 0;
        $internalCount         = 0;
        $clientCount           = 0;
        $serviceCategoryCounts = [];

        foreach ($projectsInPeriod as $project) {
            // Statuts
            if ($project->getStatus() === 'active') {
                ++$activeCount;
            } elseif ($project->getStatus() === 'completed') {
                ++$completedCount;
            }

            // Types
            if ($project->getProjectType() === 'forfait') {
                ++$forfaitCount;
            } else {
                ++$regieCount;
            }

            if ($project->getIsInternal()) {
                ++$internalCount;
            } else {
                ++$clientCount;
            }

            // Catégorie de service
            $sc = $project->getServiceCategory();
            if ($sc) {
                $name                         = $sc->getName();
                $serviceCategoryCounts[$name] = ($serviceCategoryCounts[$name] ?? 0) + 1;
            }
        }

        // Ordonner les catégories par libellé pour stabilité
        ksort($serviceCategoryCounts);

        return [
            'total'     => count($projectsInPeriod),
            'active'    => $activeCount,
            'completed' => $completedCount,
            'in_period' => count($projectsInPeriod),
            'by_type'   => [
                'forfait' => $forfaitCount,
                'regie'   => $regieCount,
            ],
            'by_client_type' => [
                'internal' => $internalCount,
                'client'   => $clientCount,
            ],
            'by_service_category' => $serviceCategoryCounts,
        ];
    }

    /**
     * Calcule les métriques devis.
     */
    private function calculateOrderMetrics(DateTimeInterface $startDate, DateTimeInterface $endDate, array $filters = []): array
    {
        // Restreindre aux projets filtrés si des filtres sont fournis
        $projects   = $this->getFilteredProjects($startDate, $endDate, $filters, null);
        $projectIds = array_map(static fn ($p) => $p->getId(), $projects);

        $qb = $this->orderRepo->createQueryBuilder('o')
            ->where('o.createdAt BETWEEN :start AND :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate);

        if (!empty($projectIds)) {
            $qb->join('o.project', 'p')
               ->andWhere('p.id IN (:pids)')
               ->setParameter('pids', $projectIds);
        }

        $ordersInPeriod = $qb->getQuery()->getResult();

        $pending = 0;
        $won     = 0;
        $lost    = 0;
        $signed  = 0;

        foreach ($ordersInPeriod as $order) {
            switch ($order->getStatus()) {
                case 'a_signer':
                    $pending++;
                    break;
                case 'gagne':
                    $won++;
                    break;
                case 'signe':
                    $signed++;
                    break;
                case 'perdu':
                case 'abandonne':
                    $lost++;
                    break;
            }
        }

        // Taux de conversion
        $totalDecided   = $won + $signed + $lost;
        $conversionRate = $totalDecided > 0
            ? round((($won + $signed) / $totalDecided) * 100, 2)
            : 0;

        return [
            'total'           => count($ordersInPeriod),
            'pending'         => $pending,
            'won'             => $won,
            'signed'          => $signed,
            'lost'            => $lost,
            'conversion_rate' => $conversionRate,
        ];
    }

    /**
     * Calcule les métriques contributeurs.
     */
    private function calculateContributorMetrics(DateTimeInterface $startDate, DateTimeInterface $endDate, array $filters = []): array
    {
        $activeContributors = $this->contributorRepo->findBy(['active' => true]);

        // Restreindre aux projets filtrés si applicable
        $projects   = $this->getFilteredProjects($startDate, $endDate, $filters, null);
        $projectIds = array_map(static fn ($p) => $p->getId(), $projects);

        if (!empty($projectIds)) {
            $topContributors = $this->timesheetRepo->getStatsPerContributorForProjects($startDate, $endDate, $projectIds);
        } else {
            $topContributors = $this->timesheetRepo->getStatsPerContributor($startDate, $endDate);
        }

        // Limiter aux 10 premiers
        $topContributors = array_slice($topContributors, 0, 10);

        return [
            'total_active'     => count($activeContributors),
            'top_contributors' => $topContributors,
        ];
    }

    /**
     * Calcule les métriques de temps.
     */
    private function calculateTimeMetrics(DateTimeInterface $startDate, DateTimeInterface $endDate, array $filters = []): array
    {
        // Restreindre les heures aux projets filtrés si applicable
        $projects   = $this->getFilteredProjects($startDate, $endDate, $filters, null);
        $projectIds = array_map(static fn ($p) => $p->getId(), $projects);

        if (!empty($projectIds)) {
            $totalHours = $this->timesheetRepo->getTotalHoursForPeriodAndProjects($startDate, $endDate, $projectIds);
        } else {
            $totalHours = $this->timesheetRepo->getTotalHoursForMonth($startDate, $endDate);
        }

        // Nombre de jours ouvrés dans la période
        $workingDays = $this->calculateWorkingDays($startDate, $endDate);

        // Contributeurs actifs
        $activeContributors = $this->contributorRepo->findBy(['active' => true]);
        $contributorCount   = count($activeContributors);

        // Capacité théorique (nb contributeurs × nb jours × 8h)
        $theoreticalCapacity = $contributorCount * $workingDays * 8;

        // Taux d'occupation
        $occupationRate = $theoreticalCapacity > 0
            ? round(($totalHours / $theoreticalCapacity) * 100, 2)
            : 0;

        return [
            'total_hours'            => $totalHours,
            'total_days'             => round($totalHours / 8, 2),
            'working_days_in_period' => $workingDays,
            'theoretical_capacity'   => $theoreticalCapacity,
            'occupation_rate'        => $occupationRate,
        ];
    }

    /**
     * Calcule le nombre de jours ouvrés entre deux dates (lun-ven).
     */
    private function calculateWorkingDays(DateTimeInterface $startDate, DateTimeInterface $endDate): int
    {
        $count   = 0;
        $current = clone $startDate;
        $end     = clone $endDate;

        while ($current <= $end) {
            $dayOfWeek = (int) $current->format('N'); // 1 (lundi) à 7 (dimanche)
            if ($dayOfWeek <= 5) {
                ++$count;
            }
            $current->modify('+1 day');
        }

        return $count;
    }

    /**
     * Calcule l'évolution mensuelle du CA et des marges pour les graphiques.
     */
    public function calculateMonthlyEvolution(int $months = 12, array $filters = []): array
    {
        $data    = [];
        $endDate = new DateTime('last day of this month');

        for ($i = $months - 1; $i >= 0; --$i) {
            $monthStart = (clone $endDate)->modify("-{$i} months")->modify('first day of this month');
            $monthEnd   = (clone $monthStart)->modify('last day of this month');

            $metrics = $this->calculateKPIs($monthStart, $monthEnd, $filters);

            $data[] = [
                'month'       => $monthStart->format('Y-m'),
                'month_label' => $monthStart->format('M Y'),
                'revenue'     => $metrics['revenue']['total_revenue'],
                'cost'        => $metrics['revenue']['total_cost'],
                'margin'      => $metrics['revenue']['total_margin'],
                'margin_rate' => $metrics['revenue']['margin_rate'],
            ];
        }

        return $data;
    }

    /**
     * Récupère les projets de la période en appliquant les filtres fournis.
     */
    private function getFilteredProjects(DateTimeInterface $startDate, DateTimeInterface $endDate, array $filters = [], ?string $statusOverride = null): array
    {
        $status            = $statusOverride                 ?? ($filters['status'] ?? null);
        $projectType       = $filters['project_type']        ?? null;
        $technologyId      = $filters['technology_id']       ?? null;
        $isInternal        = $filters['is_internal']         ?? null;
        $projectManager    = $filters['project_manager_id']  ?? null;
        $salesPerson       = $filters['sales_person_id']     ?? null;
        $serviceCategoryId = $filters['service_category_id'] ?? null;

        return $this->projectRepo->findBetweenDatesFiltered(
            $startDate,
            $endDate,
            $status,
            $projectType,
            $technologyId,
            'name',
            'ASC',
            null,
            null,
            $isInternal,
            $projectManager,
            $salesPerson,
            $serviceCategoryId,
        );
    }
}
