<?php

namespace App\Service;

use App\Entity\Contributor;
use App\Entity\Order;
use App\Entity\Project;
use App\Entity\Timesheet;
use DateInterval;
use DatePeriod;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;

class ProfitabilityService
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * Agrégats sur une période pour un ensemble de projets (liste des projets).
     * - CA période = Σ(heures facturables × TJM / 8)
     * - Coût homme période = Σ(heures facturables × CJM / 8)
     * - Achats période = Somme des achats rattachés aux projets (projet + lignes de devis)
     * - Marge brute € = CA - Achats
     * - Marge brute % = (Marge brute / CA) × 100
     * - Marge nette € = CA - Achats - Coût homme
     * - Marge nette % = (Marge nette / CA) × 100
     * - TJR réel = CA / (Σ heures / 8).
     */
    public function calculatePeriodMetricsForProjects(array $projects, DateTimeInterface $startDate, DateTimeInterface $endDate): array
    {
        $timesheetRepo = $this->entityManager->getRepository(Timesheet::class);
        $projectRepo   = $this->entityManager->getRepository(Project::class);

        // IDs des projets concernés
        $projectIds = [];
        foreach ($projects as $p) {
            if ($p instanceof Project && $p->id !== null) {
                $projectIds[] = $p->id;
            }
        }

        if (empty($projectIds)) {
            return [
                'revenue'          => '0',
                'human_cost'       => '0',
                'purchases'        => '0',
                'gross_margin_eur' => '0',
                'gross_margin_pct' => '0',
                'net_margin_eur'   => '0',
                'net_margin_pct'   => '0',
                'real_daily_rate'  => '0',
                'total_hours'      => '0',
                'total_days'       => '0',
                'start_date'       => $startDate,
                'end_date'         => $endDate,
            ];
        }

        // Agrégats SQL: heures, coût humain et revenu
        $agg            = $timesheetRepo->getPeriodAggregatesForProjects($startDate, $endDate, $projectIds);
        $totalHours     = (string) $agg['totalHours'];
        $totalHumanCost = (string) $agg['totalHumanCost'];
        $totalRevenue   = (string) $agg['totalRevenue'];

        // Achats (non datés): achats projet + achats attachés aux lignes
        $totalPurchases = $projectRepo->getTotalPurchasesForProjects($projectIds);

        // Calculs dérivés
        $totalDays   = bcdiv($totalHours, '8', 2);
        $grossMargin = bcsub($totalRevenue, $totalPurchases, 2);
        $netMargin   = bcsub($grossMargin, $totalHumanCost, 2);

        $grossPct = (bccomp($totalRevenue, '0', 2) > 0)
            ? bcmul(bcdiv($grossMargin, $totalRevenue, 4), '100', 2)
            : '0.00';
        $netPct = (bccomp($totalRevenue, '0', 2) > 0)
            ? bcmul(bcdiv($netMargin, $totalRevenue, 4), '100', 2)
            : '0.00';
        $realDailyRate = (bccomp($totalDays, '0', 2) > 0)
            ? bcdiv($totalRevenue, $totalDays, 2)
            : '0.00';

        return [
            'revenue'          => $totalRevenue,
            'human_cost'       => $totalHumanCost,
            'purchases'        => $totalPurchases,
            'gross_margin_eur' => $grossMargin,
            'gross_margin_pct' => $grossPct,
            'net_margin_eur'   => $netMargin,
            'net_margin_pct'   => $netPct,
            'real_daily_rate'  => $realDailyRate,
            'total_hours'      => $totalHours,
            'total_days'       => $totalDays,
            'start_date'       => $startDate,
            'end_date'         => $endDate,
        ];
    }

    /**
     * Construit une timeline de consommation (hebdo ou mensuelle) pour un projet.
     * - budgetLine: ligne horizontale au montant du budget (CA vendu via tâches)
     * - consumed: coût réel cumulé (heures × CJM/8)
     * - forecast: coût prévisionnel cumulé, distribution linéaire du coût estimé.
     */
    public function buildConsumptionTimeline(Project $project, DateTimeInterface $startDate, DateTimeInterface $endDate, string $granularity = 'weekly'): array
    {
        $timesheetRepo = $this->entityManager->getRepository(Timesheet::class);

        // Déterminer pas de temps
        $interval = $granularity === 'monthly' ? new DateInterval('P1M') : new DateInterval('P1W');
        $period   = new DatePeriod($startDate, $interval, (clone $endDate)->modify('+1 day'));

        $labels     = [];
        $budgetLine = [];
        $consumed   = [];
        $forecast   = [];

        $budgetRevenue   = $project->getTotalTasksSoldAmount();
        $estimatedCost   = $project->getTotalTasksEstimatedCost();
        $purchasesAmount = $project->getPurchasesAmount() ?? '0.00';

        // Distribuer linéairement le coût estimé sur la période
        // Nombre de points
        $points = 0;
        foreach ($period as $_) {
            ++$points;
        }
        if ($points === 0) {
            $points = 1;
        }

        // Recalculer le period après comptage (DatePeriod est itérable une fois)
        $period = new DatePeriod($startDate, $interval, (clone $endDate)->modify('+1 day'));

        $cumulativeConsumed = '0';
        $i                  = 0;
        foreach ($period as $dt) {
            ++$i;
            // Label
            $labels[]     = $granularity === 'monthly' ? $dt->format('M Y') : sprintf('S%02d %s', (int) $dt->format('W'), $dt->format('Y'));
            $budgetLine[] = (float) $budgetRevenue;

            // Fenêtre pour ce point
            $windowStart = clone $dt;
            $windowEnd   = clone $dt;
            if ($granularity === 'monthly') {
                $windowEnd->modify('last day of this month');
            } else {
                // semaine ISO: du lundi au dimanche
                $windowEnd->modify('+6 days');
            }
            if ($windowEnd > $endDate) {
                $windowEnd = clone $endDate;
            }

            // Consommé (coût réel sur la fenêtre)
            $timesheets = $timesheetRepo->findForPeriodWithProject($windowStart, $windowEnd, $project);
            $windowCost = '0';
            foreach ($timesheets as $t) {
                if (!$this->shouldCountTimesheet($t)) {
                    continue;
                }
                $dailyCost  = $this->getContributorDailyCost($t->getContributor(), $t->getDate());
                $timeCost   = bcdiv(bcmul((string) $t->getHours(), $dailyCost, 4), '8', 2);
                $windowCost = bcadd($windowCost, $timeCost, 2);
            }
            $cumulativeConsumed = bcadd($cumulativeConsumed, $windowCost, 2);
            $consumed[]         = (float) $cumulativeConsumed;

            // Prévisionnel cumulé (distribution linéaire sur points)
            $forecastCum = bcmul($estimatedCost, bcdiv((string) $i, (string) $points, 4), 2);
            $forecast[]  = (float) $forecastCum;
        }

        return [
            'labels'     => $labels,
            'budgetLine' => array_map(floatval(...), $budgetLine),
            'consumed'   => $consumed,
            'forecast'   => $forecast,
        ];
    }

    /**
     * Données pour donut de répartition du budget (marge, achats, coût homme).
     * Utilise les cibles (estimations) par défaut.
     */
    public function buildBudgetDonut(Project $project): array
    {
        $revenue         = $project->getTotalTasksSoldAmount();
        $estimatedCost   = $project->getTotalTasksEstimatedCost();
        $purchasesAmount = $project->getPurchasesAmount() ?? '0.00';
        $margin          = bcsub(bcsub($revenue, $estimatedCost, 2), $purchasesAmount, 2);

        return [
            'labels' => ['Marge', 'Achats', 'Coût homme'],
            'data'   => [
                max(0, (float) $margin),
                (float) $purchasesAmount,
                (float) $estimatedCost,
            ],
        ];
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
            $totalHours = $this->getTotalProjectHours($project);

            return [
                'revenue'          => '0',
                'cost'             => '0',
                'margin'           => '0',
                'margin_rate'      => '0',
                'sold_days'        => '0',
                'worked_hours'     => rtrim(rtrim($totalHours, '0'), '.'),
                'worked_days'      => bcdiv($totalHours, '8', 2),
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
            'worked_hours'     => rtrim(rtrim($totalHours, '0'), '.'),
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
            if (in_array($order->getStatus(), ['signed', 'won', 'completed'], true)) {
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
                $lineTotal = bcmul((string) $line->getDays(), $line->getTjm() ?? '0', 2);
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
            if (in_array($order->getStatus(), ['signed', 'won', 'completed'], true)) {
                foreach ($order->getSections() as $section) {
                    foreach ($section->getLines() as $line) {
                        $totalDays = bcadd($totalDays, (string) $line->getDays(), 2);
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
                $timeCostPerDay  = bcdiv(bcmul((string) $timesheet->getHours(), $contributorCost, 4), '8', 2);
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
                        $totalCost = bcadd($totalCost, (string) $line->getPurchaseAmount(), 2);
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
        return $task->getCountsForProfitability();
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

        return !($endDate && $date > $endDate);
    }

    /**
     * Calcule les heures totales d'un projet.
     */
    private function getTotalProjectHours(Project $project): string
    {
        $totalHours = '0';
        foreach ($project->getTimesheets() as $timesheet) {
            $totalHours = bcadd($totalHours, (string) $timesheet->getHours(), 2);
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
                $excludedHours = bcadd($excludedHours, (string) $timesheet->getHours(), 2);
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
            $totalRevenue  = bcadd($totalRevenue, (string) $profitability['revenue'], 2);
            $totalCost     = bcadd($totalCost, (string) $profitability['cost'], 2);
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
        if (bccomp((string) $profitability['margin'], '0', 2) < 0) {
            $alerts[] = [
                'type'    => 'danger',
                'title'   => 'Marge négative',
                'message' => 'Le projet présente une marge négative de '.
                           number_format(floatval($profitability['margin']), 2).'€',
            ];
        }

        // Alerte taux de marge faible
        if (bccomp((string) $profitability['margin_rate'], '10', 2) < 0 && bccomp((string) $profitability['margin_rate'], '0', 2) >= 0) {
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
