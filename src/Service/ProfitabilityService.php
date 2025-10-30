<?php

namespace App\Service;

use App\Entity\Contributor;
use App\Entity\Order;
use App\Entity\Project;
use App\Entity\Timesheet;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;

class ProfitabilityService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Calcule la rentabilité d'un projet
     * Formules utilisées :
     * - Coût réel = Σ(heures_passées × CJM_intervenant / 8) + achats
     * - CA = Σ(jours_vendus × TJM_vente) pour tous les devis
     * - Marge brute = CA - Coût_réel
     * - Taux de marge = (Marge / CA) × 100.
     */
    public function calculateProjectProfitability(Project $project): array
    {
        // Si c'est un projet interne, pas de calcul de rentabilité
        if ($project->getIsInternal()) {
            return [
                'revenue'          => '0',
                'cost'             => '0',
                'margin'           => '0',
                'margin_rate'      => '0',
                'sold_days'        => '0',
                'worked_hours'     => $this->getTotalProjectHours($project),
                'worked_days'      => bcdiv($this->getTotalProjectHours($project), '8', 2),
                'is_internal'      => true,
                'excluded_hours'   => $this->getExcludedHours($project),
                'orders_count'     => count($project->getOrders()),
                'purchases_amount' => $project->getPurchasesAmount() ?? '0',
            ];
        }

        // Calcul du CA total (somme de tous les devis)
        $revenue = $this->calculateProjectRevenue($project);

        // Calcul du coût réel (temps passés + achats)
        $cost = $this->calculateProjectCost($project);

        // Calcul de la marge
        $margin = bcsub($revenue, $cost, 2);

        // Calcul du taux de marge
        $marginRate = '0';
        if (bccomp($revenue, '0', 2) > 0) {
            $marginRate = bcmul(bcdiv($margin, $revenue, 4), '100', 2);
        }

        // Statistiques complémentaires
        $totalHours    = $this->getTotalProjectHours($project);
        $excludedHours = $this->getExcludedHours($project);
        $billableHours = bcsub($totalHours, $excludedHours, 2);
        $soldDays      = $this->calculateSoldDays($project);

        return [
            'revenue'          => $revenue,
            'cost'             => $cost,
            'margin'           => $margin,
            'margin_rate'      => $marginRate,
            'sold_days'        => $soldDays,
            'worked_hours'     => $totalHours,
            'worked_days'      => bcdiv($totalHours, '8', 2),
            'billable_hours'   => $billableHours,
            'billable_days'    => bcdiv($billableHours, '8', 2),
            'is_internal'      => false,
            'excluded_hours'   => $excludedHours,
            'orders_count'     => count($project->getOrders()),
            'purchases_amount' => $project->getPurchasesAmount() ?? '0',
        ];
    }

    /**
     * Calcule le chiffre d'affaires total du projet (somme de tous les devis).
     */
    private function calculateProjectRevenue(Project $project): string
    {
        $totalRevenue = '0';

        foreach ($project->getOrders() as $order) {
            // Ne compter que les devis signés/gagnés
            if (in_array($order->getStatus(), ['signed', 'won', 'completed'])) {
                $orderTotal   = $this->calculateOrderTotal($order);
                $totalRevenue = bcadd($totalRevenue, $orderTotal, 2);
            }
        }

        return $totalRevenue;
    }

    /**
     * Calcule le total d'un devis avec contingence.
     */
    private function calculateOrderTotal(Order $order): string
    {
        $total = '0';

        foreach ($order->getSections() as $section) {
            foreach ($section->getLines() as $line) {
                $lineTotal = bcmul($line->getDays(), $line->getTjm() ?? '0', 2);
                $total     = bcadd($total, $lineTotal, 2);
            }
        }

        // Appliquer la contingence si définie
        if ($order->getContingencyPercentage()) {
            $contingency = bcmul($total, bcdiv($order->getContingencyPercentage(), '100', 4), 2);
            $total       = bcsub($total, $contingency, 2);
        }

        return $total;
    }

    /**
     * Calcule les jours vendus total du projet.
     */
    private function calculateSoldDays(Project $project): string
    {
        $totalDays = '0';

        foreach ($project->getOrders() as $order) {
            if (in_array($order->getStatus(), ['signed', 'won', 'completed'])) {
                foreach ($order->getSections() as $section) {
                    foreach ($section->getLines() as $line) {
                        $totalDays = bcadd($totalDays, $line->getDays(), 2);
                    }
                }
            }
        }

        return $totalDays;
    }

    /**
     * Calcule le coût réel d'un projet
     * Formule : Σ(heures_passées × CJM_intervenant / 8) + achats.
     */
    private function calculateProjectCost(Project $project): string
    {
        $totalCost = '0';

        // Coût des temps passés (hors AVV et non-vendu)
        foreach ($project->getTimesheets() as $timesheet) {
            if ($this->shouldCountTimesheet($timesheet)) {
                $contributorCost = $this->getContributorDailyCost($timesheet->getContributor(), $timesheet->getDate());
                $timeCostPerDay  = bcdiv(bcmul($timesheet->getHours(), $contributorCost, 4), '8', 2);
                $totalCost       = bcadd($totalCost, $timeCostPerDay, 2);
            }
        }

        // Ajouter les achats du projet
        if ($project->getPurchasesAmount()) {
            $totalCost = bcadd($totalCost, $project->getPurchasesAmount(), 2);
        }

        // Ajouter les achats des devis
        foreach ($project->getOrders() as $order) {
            foreach ($order->getSections() as $section) {
                foreach ($section->getLines() as $line) {
                    if ($line->getPurchaseAmount()) {
                        $totalCost = bcadd($totalCost, $line->getPurchaseAmount(), 2);
                    }
                }
            }
        }

        return $totalCost;
    }

    /**
     * Détermine si un timesheet doit être compté dans la rentabilité.
     */
    private function shouldCountTimesheet(Timesheet $timesheet): bool
    {
        $task = $timesheet->getTask();

        // Si pas de tâche associée, on compte par défaut
        if (!$task) {
            return true;
        }

        // Si c'est une tâche qui ne compte pas pour la rentabilité (AVV, non-vendu)
        if (!$task->getCountsForProfitability()) {
            return false;
        }

        return true;
    }

    /**
     * Calcule le coût journalier d'un contributeur à une date donnée.
     */
    private function getContributorDailyCost(Contributor $contributor, DateTimeInterface $date): string
    {
        // Récupérer la période d'emploi active à cette date
        foreach ($contributor->getEmploymentPeriods() as $period) {
            if ($this->isPeriodActiveAt($period, $date)) {
                $cjm = $period->getCjm() ?? $contributor->getCjm() ?? '0';
                // Ajuster selon le pourcentage de temps de travail
                $workTimePercentage = $period->getWorkTimePercentage() ?? 100;

                return bcmul($cjm, bcdiv($workTimePercentage, '100', 4), 2);
            }
        }

        // Si pas de période trouvée, utiliser le CJM du contributeur
        return $contributor->getCjm() ?? '0';
    }

    /**
     * Vérifie si une période d'emploi est active à une date donnée.
     */
    private function isPeriodActiveAt($period, DateTimeInterface $date): bool
    {
        $startDate = $period->getStartDate();
        $endDate   = $period->getEndDate();

        if (!$startDate) {
            return false;
        }

        if ($date < $startDate) {
            return false;
        }

        if ($endDate && $date > $endDate) {
            return false;
        }

        return true;
    }

    /**
     * Calcule les heures totales d'un projet.
     */
    private function getTotalProjectHours(Project $project): string
    {
        $totalHours = '0';
        foreach ($project->getTimesheets() as $timesheet) {
            $totalHours = bcadd($totalHours, $timesheet->getHours(), 2);
        }

        return $totalHours;
    }

    /**
     * Calcule les heures exclues des calculs (AVV + non-vendu).
     */
    private function getExcludedHours(Project $project): string
    {
        $excludedHours = '0';
        foreach ($project->getTimesheets() as $timesheet) {
            if (!$this->shouldCountTimesheet($timesheet)) {
                $excludedHours = bcadd($excludedHours, $timesheet->getHours(), 2);
            }
        }

        return $excludedHours;
    }

    /**
     * Calcule les KPIs globaux de rentabilité (exclut projets internes).
     */
    public function calculateGlobalKPIs(array $projects): array
    {
        $totalRevenue          = '0';
        $totalCost             = '0';
        $totalExternalProjects = 0;
        $totalInternalProjects = 0;

        foreach ($projects as $project) {
            if ($project->getIsInternal()) {
                ++$totalInternalProjects;
                continue;
            }

            $profitability = $this->calculateProjectProfitability($project);
            $totalRevenue  = bcadd($totalRevenue, $profitability['revenue'], 2);
            $totalCost     = bcadd($totalCost, $profitability['cost'], 2);
            ++$totalExternalProjects;
        }

        $totalMargin      = bcsub($totalRevenue, $totalCost, 2);
        $globalMarginRate = '0';
        if (bccomp($totalRevenue, '0', 2) > 0) {
            $globalMarginRate = bcmul(bcdiv($totalMargin, $totalRevenue, 4), '100', 2);
        }

        return [
            'total_revenue'           => $totalRevenue,
            'total_cost'              => $totalCost,
            'total_margin'            => $totalMargin,
            'global_margin_rate'      => $globalMarginRate,
            'external_projects_count' => $totalExternalProjects,
            'internal_projects_count' => $totalInternalProjects,
        ];
    }

    /**
     * Calcule la performance d'un contributeur sur une période.
     */
    public function calculateContributorPerformance(Contributor $contributor, DateTimeInterface $startDate, DateTimeInterface $endDate): array
    {
        $timesheets = $this->entityManager->getRepository(Timesheet::class)
            ->findByContributorAndDateRange($contributor, $startDate, $endDate);

        $totalHours     = '0';
        $billableHours  = '0';
        $totalRevenue   = '0';
        $totalCost      = '0';
        $projectsWorked = [];

        foreach ($timesheets as $timesheet) {
            $hours      = $timesheet->getHours();
            $totalHours = bcadd($totalHours, $hours, 2);

            if ($this->shouldCountTimesheet($timesheet)) {
                $billableHours = bcadd($billableHours, $hours, 2);

                // Calculer le coût pour ce temps
                $dailyCost = $this->getContributorDailyCost($contributor, $timesheet->getDate());
                $timeCost  = bcdiv(bcmul($hours, $dailyCost, 4), '8', 2);
                $totalCost = bcadd($totalCost, $timeCost, 2);

                // Estimer le revenu généré (TJM moyen * heures / 8)
                $avgTjm       = $this->getAverageTjmForContributor($contributor, $timesheet->getDate());
                $timeRevenue  = bcdiv(bcmul($hours, $avgTjm, 4), '8', 2);
                $totalRevenue = bcadd($totalRevenue, $timeRevenue, 2);
            }

            $projectsWorked[$timesheet->getProject()->getId()] = $timesheet->getProject()->getName();
        }

        $margin     = bcsub($totalRevenue, $totalCost, 2);
        $marginRate = bccomp($totalRevenue, '0', 2) > 0
            ? bcmul(bcdiv($margin, $totalRevenue, 4), '100', 2)
            : '0';

        return [
            'total_hours'        => $totalHours,
            'billable_hours'     => $billableHours,
            'non_billable_hours' => bcsub($totalHours, $billableHours, 2),
            'billability_rate'   => bccomp($totalHours, '0', 2) > 0
                ? bcmul(bcdiv($billableHours, $totalHours, 4), '100', 2)
                : '0',
            'estimated_revenue' => $totalRevenue,
            'total_cost'        => $totalCost,
            'margin'            => $margin,
            'margin_rate'       => $marginRate,
            'projects_count'    => count($projectsWorked),
            'projects_worked'   => array_values($projectsWorked),
        ];
    }

    /**
     * Calcule le TJM moyen d'un contributeur selon ses profils.
     */
    private function getAverageTjmForContributor(Contributor $contributor, DateTimeInterface $date): string
    {
        // Récupérer le TJM de la période d'emploi active
        foreach ($contributor->getEmploymentPeriods() as $period) {
            if ($this->isPeriodActiveAt($period, $date) && $period->getTjm()) {
                return $period->getTjm();
            }
        }

        // Sinon, utiliser le TJM du contributeur
        return $contributor->getTjm() ?? '0';
    }

    /**
     * Compare les prévisions (vendu) vs réalisé pour un projet.
     */
    public function compareProjectForecastVsRealized(Project $project): array
    {
        $profitability = $this->calculateProjectProfitability($project);

        $soldDays     = floatval($profitability['sold_days']);
        $workedDays   = floatval($profitability['worked_days']);
        $billableDays = floatval($profitability['billable_days']);

        $daysOverrun       = $billableDays - $soldDays;
        $overrunPercentage = $soldDays > 0 ? ($daysOverrun / $soldDays) * 100 : 0;

        return [
            'sold_days'          => $profitability['sold_days'],
            'worked_days'        => $profitability['worked_days'],
            'billable_days'      => $profitability['billable_days'],
            'days_overrun'       => number_format($daysOverrun, 2),
            'overrun_percentage' => number_format($overrunPercentage, 1),
            'is_overrun'         => $daysOverrun  > 0,
            'efficiency_rate'    => $billableDays > 0
                ? number_format(($soldDays / $billableDays) * 100, 1)
                : '0',
        ];
    }

    /**
     * Génère des alertes basées sur la rentabilité.
     */
    public function generateProfitabilityAlerts(Project $project): array
    {
        $profitability = $this->calculateProjectProfitability($project);
        $comparison    = $this->compareProjectForecastVsRealized($project);
        $alerts        = [];

        // Alerte marge négative
        if (bccomp($profitability['margin'], '0', 2) < 0) {
            $alerts[] = [
                'type'    => 'danger',
                'title'   => 'Marge négative',
                'message' => 'Le projet présente une marge négative de '.
                           number_format(floatval($profitability['margin']), 2).'€',
            ];
        }

        // Alerte taux de marge faible
        if (bccomp($profitability['margin_rate'], '10', 2) < 0 && bccomp($profitability['margin_rate'], '0', 2) >= 0) {
            $alerts[] = [
                'type'    => 'warning',
                'title'   => 'Taux de marge faible',
                'message' => 'Le taux de marge est de seulement '.
                           number_format(floatval($profitability['margin_rate']), 1).'%',
            ];
        }

        // Alerte dépassement budgétaire
        if (floatval($comparison['overrun_percentage']) > 10) {
            $alerts[] = [
                'type'    => 'warning',
                'title'   => 'Dépassement budgétaire',
                'message' => 'Le projet dépasse de '.$comparison['overrun_percentage'].
                           '% le budget initial ('.$comparison['days_overrun'].' jours)',
            ];
        }

        return $alerts;
    }

    /**
     * Formate les résultats de rentabilité pour affichage.
     */
    public function formatProfitabilityForDisplay(array $profitability): array
    {
        return [
            'revenue'        => number_format(floatval($profitability['revenue']), 2, ',', ' ').' €',
            'cost'           => number_format(floatval($profitability['cost']), 2, ',', ' ').' €',
            'margin'         => number_format(floatval($profitability['margin']), 2, ',', ' ').' €',
            'margin_rate'    => number_format(floatval($profitability['margin_rate']), 1, ',', ' ').' %',
            'sold_days'      => number_format(floatval($profitability['sold_days']), 1, ',', ' ').' j',
            'worked_days'    => number_format(floatval($profitability['worked_days']), 1, ',', ' ').' j',
            'billable_days'  => number_format(floatval($profitability['billable_days']), 1, ',', ' ').' j',
            'excluded_hours' => number_format(floatval($profitability['excluded_hours']), 1, ',', ' ').' h',
            'is_internal'    => $profitability['is_internal'],
        ];
    }
}
