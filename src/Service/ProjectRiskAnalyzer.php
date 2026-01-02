<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Project;
use App\Entity\ProjectHealthScore;
use App\Security\CompanyContext;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

class ProjectRiskAnalyzer
{
    private const WEIGHT_BUDGET   = 0.40;    // 40%
    private const WEIGHT_TIMELINE = 0.30;  // 30%
    private const WEIGHT_VELOCITY = 0.20;  // 20%
    private const WEIGHT_QUALITY  = 0.10;   // 10%

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CompanyContext $companyContext
    ) {
    }

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

        if ($soldHours === 0.0) {
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
        if ($soldAmount === 0.0) {
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
            return $timesheet->getDate() >= $twoWeeksAgo;
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
        if ($progress === 0.0 && $project->getStartDate() < (new DateTime())->modify('-1 month')) {
            return [
                'type'     => 'not_started',
                'severity' => 'high',
                'message'  => 'Projet démarré il y a plus d\'un mois mais aucune progression',
                'score'    => 20,
            ];
        }

        if ($progress >= 100) {
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

    /**
     * Calculate and persist health score for a project.
     */
    public function calculateHealthScore(Project $project): ProjectHealthScore
    {
        // Calculate individual component scores (0-100)
        $budgetScore   = $this->calculateBudgetScore($project);
        $timelineScore = $this->calculateTimelineScore($project);
        $velocityScore = $this->calculateVelocityScore($project);
        $qualityScore  = $this->calculateQualityScore($project);

        // Calculate weighted overall score
        $overallScore = (int) round(
            ($budgetScore * self::WEIGHT_BUDGET)
            + ($timelineScore * self::WEIGHT_TIMELINE)
            + ($velocityScore * self::WEIGHT_VELOCITY)
            + ($qualityScore * self::WEIGHT_QUALITY),
        );

        // Determine health level
        $healthLevel = $this->determineHealthLevel($overallScore);

        // Generate recommendations
        $recommendations = $this->generateRecommendations($project, [
            'budget'   => $budgetScore,
            'timeline' => $timelineScore,
            'velocity' => $velocityScore,
            'quality'  => $qualityScore,
        ]);

        // Create and persist health score
        $healthScore = new ProjectHealthScore();
        $healthScore->setCompany($this->companyContext->getCurrentCompany());
        $healthScore->setProject($project);
        $healthScore->setScore($overallScore);
        $healthScore->setHealthLevel($healthLevel);
        $healthScore->setBudgetScore($budgetScore);
        $healthScore->setTimelineScore($timelineScore);
        $healthScore->setVelocityScore($velocityScore);
        $healthScore->setQualityScore($qualityScore);
        $healthScore->setRecommendations($recommendations);
        $healthScore->setDetails([
            'analysis_date'    => date('Y-m-d H:i:s'),
            'project_status'   => $project->getStatus(),
            'project_progress' => $project->getGlobalProgress(),
        ]);

        $this->em->persist($healthScore);
        $this->em->flush();

        return $healthScore;
    }

    /**
     * Calculate budget health score (0-100).
     */
    private function calculateBudgetScore(Project $project): int
    {
        $soldHours  = (float) $project->getTotalTasksSoldHours();
        $spentHours = (float) $project->getTotalTasksSpentHours();

        if ($soldHours === 0.0) {
            return 100; // No budget defined, neutral score
        }

        $overrunPercentage = (($spentHours - $soldHours) / $soldHours) * 100;

        // Score decreases with overrun
        if ($overrunPercentage <= 0) {
            return 100; // Under budget
        }
        if ($overrunPercentage <= 10) {
            return 80;  // Slight overrun
        }
        if ($overrunPercentage <= 20) {
            return 60;  // Moderate overrun
        }
        if ($overrunPercentage <= 30) {
            return 40;  // Significant overrun
        }

        return 20; // Critical overrun
    }

    /**
     * Calculate timeline health score (0-100).
     */
    private function calculateTimelineScore(Project $project): int
    {
        $endDate = $project->getEndDate();
        if (!$endDate) {
            return 100; // No deadline, neutral score
        }

        $now      = new DateTime();
        $progress = (float) $project->getGlobalProgress();

        // Project completed
        if ($project->getStatus() === 'completed') {
            return 100;
        }

        // Project overdue
        if ($endDate < $now) {
            $daysLate = $now->diff($endDate)->days;
            if ($daysLate > 30) {
                return 20;
            }
            if ($daysLate > 14) {
                return 40;
            }

            return 60;
        }

        // Project on track - compare expected vs actual progress
        $startDate = $project->getStartDate();
        if ($startDate && $startDate <= $now) {
            $totalDuration = $startDate->diff($endDate)->days;
            $elapsed       = $startDate->diff($now)->days;

            if ($totalDuration > 0) {
                $expectedProgress = ($elapsed / $totalDuration) * 100;
                $progressGap      = $expectedProgress - $progress;

                if ($progressGap <= 5) {
                    return 100; // On track
                }
                if ($progressGap <= 15) {
                    return 80;  // Slightly behind
                }
                if ($progressGap <= 30) {
                    return 60;  // Behind schedule
                }
                if ($progressGap <= 50) {
                    return 40;  // Significantly behind
                }

                return 20; // Critical delay
            }
        }

        return 100; // Not started yet
    }

    /**
     * Calculate velocity health score (0-100).
     */
    private function calculateVelocityScore(Project $project): int
    {
        // Check recent timesheet activity
        $timesheets = $project->getTimesheets();
        if ($timesheets->count() === 0 && $project->getStatus() === 'in_progress') {
            return 30; // No activity on active project
        }

        $now         = new DateTime();
        $twoWeeksAgo = (clone $now)->modify('-14 days');

        $recentTimesheets = $timesheets->filter(function ($timesheet) use ($twoWeeksAgo) {
            return $timesheet->getDate() >= $twoWeeksAgo;
        });

        if ($project->getStatus() === 'in_progress' && $recentTimesheets->count() === 0) {
            return 50; // No recent activity
        }

        // Check progress velocity (has progress been made?)
        $progress = (float) $project->getGlobalProgress();
        if ($progress === 0.0 && $project->getStartDate() < (new DateTime())->modify('-1 month')) {
            return 40; // Started but no progress
        }

        if ($progress >= 100 && $project->getStatus() !== 'completed') {
            return 70; // Completed but not closed
        }

        return 100; // Good velocity
    }

    /**
     * Calculate quality health score (0-100).
     */
    private function calculateQualityScore(Project $project): int
    {
        // Quality indicators: margin, timesheet completeness, task completion rate
        $score = 100;

        // Check margin
        $soldAmount = (float) $project->getTotalSoldAmount();
        if ($soldAmount > 0) {
            $spentHours    = (float) $project->getTotalTasksSpentHours();
            $estimatedCost = $spentHours                                    * 400;
            $margin        = (($soldAmount - $estimatedCost) / $soldAmount) * 100;

            if ($margin < 0) {
                $score -= 30;
            } elseif ($margin < 10) {
                $score -= 20;
            } elseif ($margin < 20) {
                $score -= 10;
            }
        }

        return max(0, $score);
    }

    /**
     * Determine health level based on overall score.
     */
    private function determineHealthLevel(int $score): string
    {
        if ($score > 80) {
            return 'healthy';
        }
        if ($score >= 50) {
            return 'warning';
        }

        return 'critical';
    }

    /**
     * Generate actionable recommendations based on component scores.
     *
     * @param array<string, int> $componentScores
     *
     * @return string[]
     */
    private function generateRecommendations(Project $project, array $componentScores): array
    {
        $recommendations = [];

        // Budget recommendations
        if ($componentScores['budget'] < 70) {
            $soldHours         = (float) $project->getTotalTasksSoldHours();
            $spentHours        = (float) $project->getTotalTasksSpentHours();
            $recommendations[] = sprintf(
                'Budget dépassé (%.1fh / %.1fh) : revoyez le périmètre ou négociez un avenant',
                $spentHours,
                $soldHours,
            );
        }

        // Timeline recommendations
        if ($componentScores['timeline'] < 70) {
            $recommendations[] = 'Projet en retard : organisez un point d\'avancement avec le client';
            $recommendations[] = 'Identifiez les tâches bloquantes et priorisez-les';
        }

        // Velocity recommendations
        if ($componentScores['velocity'] < 70) {
            $recommendations[] = 'Activité faible : vérifiez que les timesheets sont à jour';
            $recommendations[] = 'Contactez l\'équipe pour identifier les blocages éventuels';
        }

        // Quality recommendations
        if ($componentScores['quality'] < 70) {
            $recommendations[] = 'Marge faible : surveillez le temps passé et optimisez les processus';
        }

        return $recommendations;
    }

    /**
     * Calculate health scores for all active projects.
     *
     * @return ProjectHealthScore[]
     */
    public function analyzeAllActiveProjects(): array
    {
        $projects = $this->em->getRepository(Project::class)->findBy([
            'status' => ['in_progress', 'active'],
        ]);

        $healthScores = [];
        foreach ($projects as $project) {
            $healthScores[] = $this->calculateHealthScore($project);
        }

        return $healthScores;
    }
}
