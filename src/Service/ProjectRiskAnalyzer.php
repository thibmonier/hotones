<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Project;
use DateTime;

class ProjectRiskAnalyzer
{
    /**
     * Analyse les risques d'un projet et retourne un score de santé.
     *
     * @return array{
     *     healthScore: int,
     *     riskLevel: string,
     *     risks: array<array{type: string, severity: string, message: string, score: int}>
     * }
     */
    public function analyzeProject(Project $project): array
    {
        $risks = [];

        // 1. Analyse du dépassement budgétaire
        $budgetRisk = $this->analyzeBudgetOverrun($project);
        if ($budgetRisk) {
            $risks[] = $budgetRisk;
        }

        // 2. Analyse des retards de planning
        $scheduleRisk = $this->analyzeScheduleDelay($project);
        if ($scheduleRisk) {
            $risks[] = $scheduleRisk;
        }

        // 3. Analyse de la marge de rentabilité
        $profitabilityRisk = $this->analyzeProfitability($project);
        if ($profitabilityRisk) {
            $risks[] = $profitabilityRisk;
        }

        // 4. Analyse de la saisie des temps
        $timesheetRisk = $this->analyzeTimesheetCompleteness($project);
        if ($timesheetRisk) {
            $risks[] = $timesheetRisk;
        }

        // 5. Analyse de la stagnation
        $stagnationRisk = $this->analyzeStagnation($project);
        if ($stagnationRisk) {
            $risks[] = $stagnationRisk;
        }

        // Calcul du score de santé (100 - somme des pénalités)
        $totalPenalty = array_sum(array_column($risks, 'score'));
        $healthScore  = max(0, 100 - $totalPenalty);

        // Détermination du niveau de risque
        $riskLevel = $this->determineRiskLevel($healthScore);

        return [
            'healthScore' => $healthScore,
            'riskLevel'   => $riskLevel,
            'risks'       => $risks,
        ];
    }

    /**
     * Analyse le dépassement budgétaire.
     */
    private function analyzeBudgetOverrun(Project $project): ?array
    {
        $soldHours  = (float) $project->getTotalTasksSoldHours();
        $spentHours = (float) $project->getTotalTasksSpentHours();

        if ($soldHours == 0) {
            return null; // Pas de budget défini
        }

        $overrunPercentage = (($spentHours - $soldHours) / $soldHours) * 100;

        if ($overrunPercentage > 20) {
            return [
                'type'     => 'budget_overrun',
                'severity' => 'critical',
                'message'  => sprintf(
                    'Dépassement budgétaire critique : %.1f%% (%.1fh / %.1fh vendues)',
                    $overrunPercentage,
                    $spentHours,
                    $soldHours,
                ),
                'score' => 30,
            ];
        }

        if ($overrunPercentage > 10) {
            return [
                'type'     => 'budget_overrun',
                'severity' => 'high',
                'message'  => sprintf(
                    'Dépassement budgétaire élevé : %.1f%% (%.1fh / %.1fh vendues)',
                    $overrunPercentage,
                    $spentHours,
                    $soldHours,
                ),
                'score' => 20,
            ];
        }

        if ($overrunPercentage > 0) {
            return [
                'type'     => 'budget_warning',
                'severity' => 'medium',
                'message'  => sprintf(
                    'Budget dépassé : %.1f%% (%.1fh / %.1fh vendues)',
                    $overrunPercentage,
                    $spentHours,
                    $soldHours,
                ),
                'score' => 10,
            ];
        }

        return null;
    }

    /**
     * Analyse les retards de planning.
     */
    private function analyzeScheduleDelay(Project $project): ?array
    {
        $endDate = $project->getEndDate();
        if (!$endDate) {
            return null; // Pas de date de fin définie
        }

        $now      = new DateTime();
        $progress = (float) $project->getGlobalProgress();

        // Projet terminé
        if ($project->getStatus() === 'completed') {
            return null;
        }

        // Projet en retard (date dépassée et non terminé)
        if ($endDate < $now) {
            $daysLate = $now->diff($endDate)->days;

            return [
                'type'     => 'schedule_delay',
                'severity' => 'critical',
                'message'  => sprintf(
                    'Projet en retard de %d jours (progression : %.0f%%)',
                    $daysLate,
                    $progress,
                ),
                'score' => 25,
            ];
        }

        // Risque de retard (projeté)
        $startDate = $project->getStartDate();
        if ($startDate && $startDate <= $now) {
            $totalDuration = $startDate->diff($endDate)->days;
            $elapsed       = $startDate->diff($now)->days;

            if ($totalDuration > 0) {
                $expectedProgress = ($elapsed / $totalDuration) * 100;
                $progressGap      = $expectedProgress - $progress;

                if ($progressGap > 20) {
                    return [
                        'type'     => 'schedule_risk',
                        'severity' => 'high',
                        'message'  => sprintf(
                            'Risque de retard : progression attendue %.0f%%, réelle %.0f%%',
                            $expectedProgress,
                            $progress,
                        ),
                        'score' => 15,
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Analyse la rentabilité du projet.
     */
    private function analyzeProfitability(Project $project): ?array
    {
        $soldAmount = (float) $project->getTotalSoldAmount();
        if ($soldAmount == 0) {
            return null;
        }

        // Calculer le coût estimé (simplifié : basé sur les heures passées)
        $spentHours    = (float) $project->getTotalTasksSpentHours();
        $estimatedCost = $spentHours * 400; // Coût moyen par jour (estimé)

        $margin = (($soldAmount - $estimatedCost) / $soldAmount) * 100;

        if ($margin < 0) {
            return [
                'type'     => 'negative_margin',
                'severity' => 'critical',
                'message'  => sprintf(
                    'Marge négative : %.1f%% (CA: %.0f€, Coût estimé: %.0f€)',
                    $margin,
                    $soldAmount,
                    $estimatedCost,
                ),
                'score' => 30,
            ];
        }

        if ($margin < 10) {
            return [
                'type'     => 'low_margin',
                'severity' => 'high',
                'message'  => sprintf(
                    'Marge faible : %.1f%% (CA: %.0f€, Coût estimé: %.0f€)',
                    $margin,
                    $soldAmount,
                    $estimatedCost,
                ),
                'score' => 20,
            ];
        }

        if ($margin < 20) {
            return [
                'type'     => 'margin_warning',
                'severity' => 'medium',
                'message'  => sprintf(
                    'Marge sous le seuil optimal : %.1f%% (objectif 20%%+)',
                    $margin,
                ),
                'score' => 10,
            ];
        }

        return null;
    }

    /**
     * Analyse la complétude des timesheets.
     */
    private function analyzeTimesheetCompleteness(Project $project): ?array
    {
        if ($project->getStatus() !== 'in_progress') {
            return null;
        }

        $timesheets = $project->getTimesheets();
        if ($timesheets->count() === 0) {
            return [
                'type'     => 'no_timesheets',
                'severity' => 'high',
                'message'  => 'Aucun temps saisi sur ce projet en cours',
                'score'    => 15,
            ];
        }

        // Vérifier s'il y a eu des saisies récentes (dernières 2 semaines)
        $now         = new DateTime();
        $twoWeeksAgo = (clone $now)->modify('-14 days');

        $recentTimesheets = $timesheets->filter(function ($timesheet) use ($twoWeeksAgo) {
            return $timesheet->getCreatedAt() >= $twoWeeksAgo;
        });

        if ($recentTimesheets->count() === 0) {
            return [
                'type'     => 'missing_timesheets',
                'severity' => 'medium',
                'message'  => 'Aucune saisie de temps depuis plus de 2 semaines',
                'score'    => 10,
            ];
        }

        return null;
    }

    /**
     * Analyse la stagnation du projet.
     */
    private function analyzeStagnation(Project $project): ?array
    {
        $progress = (float) $project->getGlobalProgress();

        // Projet ni terminé ni annulé
        if (!in_array($project->getStatus(), ['in_progress', 'active'], true)) {
            return null;
        }

        // Vérifier si le projet est bloqué (0% ou 100% sans changement de statut)
        if ($progress == 0 && $project->getStartDate() < (new DateTime())->modify('-1 month')) {
            return [
                'type'     => 'not_started',
                'severity' => 'high',
                'message'  => 'Projet démarré il y a plus d\'un mois mais aucune progression',
                'score'    => 20,
            ];
        }

        if ($progress >= 100 && $project->getStatus() !== 'completed') {
            return [
                'type'     => 'completion_pending',
                'severity' => 'low',
                'message'  => 'Projet à 100% mais non marqué comme terminé',
                'score'    => 5,
            ];
        }

        return null;
    }

    /**
     * Détermine le niveau de risque global.
     */
    private function determineRiskLevel(int $healthScore): string
    {
        if ($healthScore >= 80) {
            return 'low';
        }
        if ($healthScore >= 60) {
            return 'medium';
        }
        if ($healthScore >= 40) {
            return 'high';
        }

        return 'critical';
    }

    /**
     * Analyse plusieurs projets et retourne ceux à risque.
     *
     * @param Project[] $projects
     *
     * @return array<array{project: Project, analysis: array}>
     */
    public function analyzeMultipleProjects(array $projects): array
    {
        $atRiskProjects = [];

        foreach ($projects as $project) {
            $analysis = $this->analyzeProject($project);

            // Ne retenir que les projets avec un score < 80 (risque moyen ou élevé)
            if ($analysis['healthScore'] < 80) {
                $atRiskProjects[] = [
                    'project'  => $project,
                    'analysis' => $analysis,
                ];
            }
        }

        // Trier par score de santé croissant (les plus à risque en premier)
        usort($atRiskProjects, function ($a, $b) {
            return $a['analysis']['healthScore'] <=> $b['analysis']['healthScore'];
        });

        return $atRiskProjects;
    }
}
