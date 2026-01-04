<?php

declare(strict_types=1);

namespace App\Service\Planning;

use App\Entity\Contributor;
use App\Entity\EmploymentPeriod;
use App\Entity\Planning;
use App\Entity\Project;
use App\Entity\ProjectTask;
use App\Repository\ContributorRepository;
use App\Repository\PlanningRepository;
use App\Repository\VacationRepository;
use DateInterval;
use DatePeriod;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Assistant pour générer des suggestions de planification automatique
 * pour un projet basé sur les profils requis et les disponibilités.
 */
class ProjectPlanningAssistant
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ContributorRepository $contributorRepository,
        private readonly PlanningRepository $planningRepository,
        private readonly VacationRepository $vacationRepository
    ) {
    }

    /**
     * Génère des suggestions de planification pour un projet.
     *
     * @return array{
     *     suggestions: array<array{
     *         task: ProjectTask,
     *         contributor: Contributor,
     *         startDate: DateTime,
     *         endDate: DateTime,
     *         dailyHours: float,
     *         confidence: float,
     *         reasoning: string,
     *         warnings: array<string>
     *     }>,
     *     unassigned: array<ProjectTask>,
     *     statistics: array{
     *         totalTasks: int,
     *         assignedTasks: int,
     *         unassignedTasks: int,
     *         averageConfidence: float
     *     }
     * }
     */
    public function generateSuggestions(Project $project, ?DateTime $preferredStartDate = null): array
    {
        if (!$preferredStartDate) {
            $preferredStartDate = $project->getStartDate() ?? new DateTime('next monday');
        }

        $suggestions = [];
        $unassigned  = [];

        // Récupérer toutes les tâches actives du projet qui nécessitent une affectation
        $tasks = $this->getUnassignedTasks($project);

        foreach ($tasks as $task) {
            $suggestion = $this->suggestAssignment($task, $project, $preferredStartDate);

            if ($suggestion !== null) {
                $suggestions[] = $suggestion;
            } else {
                $unassigned[] = $task;
            }
        }

        // Trier par confiance décroissante
        usort($suggestions, fn ($a, $b) => $b['confidence'] <=> $a['confidence']);

        return [
            'suggestions' => $suggestions,
            'unassigned'  => $unassigned,
            'statistics'  => $this->calculateStatistics($suggestions, $unassigned, $tasks),
        ];
    }

    /**
     * Suggère une affectation pour une tâche donnée.
     */
    private function suggestAssignment(ProjectTask $task, Project $project, DateTime $preferredStartDate): ?array
    {
        $requiredProfile = $task->getRequiredProfile();
        if (!$requiredProfile) {
            // Pas de profil requis, difficile de suggérer
            return null;
        }

        // Estimer la durée de la tâche en jours
        $estimatedDays = $this->estimateTaskDuration($task);
        if ($estimatedDays <= 0) {
            return null;
        }

        // Trouver les contributeurs disponibles avec le bon profil
        $candidates = $this->findCandidates($requiredProfile, $preferredStartDate, $estimatedDays);

        if (empty($candidates)) {
            return null;
        }

        // Sélectionner le meilleur candidat
        $bestCandidate = $this->selectBestCandidate($candidates, $task, $preferredStartDate, $estimatedDays);

        if (!$bestCandidate) {
            return null;
        }

        return [
            'task'        => $task,
            'contributor' => $bestCandidate['contributor'],
            'startDate'   => $bestCandidate['startDate'],
            'endDate'     => $bestCandidate['endDate'],
            'dailyHours'  => $bestCandidate['dailyHours'],
            'confidence'  => $bestCandidate['confidence'],
            'reasoning'   => $bestCandidate['reasoning'],
            'warnings'    => $bestCandidate['warnings'],
        ];
    }

    /**
     * Trouve les candidats disponibles pour un profil donné.
     *
     * @return array<array{
     *     contributor: Contributor,
     *     availability: float,
     *     startDate: DateTime,
     *     endDate: DateTime,
     *     dailyHours: float,
     *     currentLoad: float
     * }>
     */
    private function findCandidates($profile, DateTime $startDate, float $estimatedDays): array
    {
        // Récupérer les contributeurs avec le profil requis
        $contributors = $this->contributorRepository->findActiveContributorsByProfile($profile);

        $candidates = [];
        foreach ($contributors as $contributor) {
            $candidate = $this->evaluateContributorAvailability($contributor, $startDate, $estimatedDays);
            if ($candidate !== null) {
                $candidates[] = $candidate;
            }
        }

        return $candidates;
    }

    /**
     * Évalue la disponibilité d'un contributeur pour une période.
     */
    private function evaluateContributorAvailability(
        Contributor $contributor,
        DateTime $startDate,
        float $estimatedDays
    ): ?array {
        // Calculer la date de fin estimée
        $endDate = $this->calculateEndDate($startDate, $estimatedDays);

        // Vérifier la période d'emploi active
        $employmentPeriod = $this->getActiveEmploymentPeriod($contributor, $startDate);
        if (!$employmentPeriod) {
            return null; // Pas de contrat actif
        }

        $dailyHours = $this->calculateDailyHours($employmentPeriod);

        // Calculer la charge actuelle sur la période
        $currentLoad = $this->calculateCurrentLoad($contributor, $startDate, $endDate);

        // Calculer la disponibilité (0-1)
        $maxDailyHours = $dailyHours;
        $availability  = $maxDailyHours > 0 ? max(0, 1 - ($currentLoad / $maxDailyHours)) : 0;

        // Vérifier les congés
        $hasVacations = $this->hasVacations($contributor, $startDate, $endDate);
        if ($hasVacations) {
            $availability *= 0.5; // Pénalité pour congés
        }

        if ($availability < 0.2) {
            return null; // Trop surchargé
        }

        return [
            'contributor'  => $contributor,
            'availability' => $availability,
            'startDate'    => $startDate,
            'endDate'      => $endDate,
            'dailyHours'   => min($dailyHours, $dailyHours * $availability),
            'currentLoad'  => $currentLoad,
        ];
    }

    /**
     * Sélectionne le meilleur candidat parmi la liste.
     */
    private function selectBestCandidate(
        array $candidates,
        ProjectTask $task,
        DateTime $preferredStartDate,
        float $estimatedDays
    ): ?array {
        if (empty($candidates)) {
            return null;
        }

        // Scorer chaque candidat
        $scoredCandidates = [];
        foreach ($candidates as $candidate) {
            $score              = $this->scoreCandidate($candidate, $task);
            $scoredCandidates[] = array_merge($candidate, [
                'score'      => $score['total'],
                'confidence' => $score['confidence'],
                'reasoning'  => $score['reasoning'],
                'warnings'   => $score['warnings'],
            ]);
        }

        // Trier par score décroissant
        usort($scoredCandidates, fn ($a, $b) => $b['score'] <=> $a['score']);

        return $scoredCandidates[0];
    }

    /**
     * Score un candidat pour une tâche donnée.
     */
    private function scoreCandidate(array $candidate, ProjectTask $task): array
    {
        $score     = 0;
        $reasoning = [];
        $warnings  = [];

        // Disponibilité (40 points max)
        $availabilityScore = $candidate['availability'] * 40;
        $score += $availabilityScore;
        $reasoning[] = sprintf('Disponibilité: %.0f%%', $candidate['availability'] * 100);

        // Charge actuelle (30 points max - inversement proportionnel)
        $loadScore = max(0, 30 * (1 - ($candidate['currentLoad'] / 8)));
        $score += $loadScore;
        if ($candidate['currentLoad'] > 6) {
            $warnings[] = sprintf('Charge actuelle élevée: %.1fh/jour', $candidate['currentLoad']);
        }

        // Expérience avec le projet (10 points)
        $hasWorkedOnProject = $this->hasWorkedOnProject($candidate['contributor'], $task->getProject());
        if ($hasWorkedOnProject) {
            $score += 10;
            $reasoning[] = 'A déjà travaillé sur ce projet';
        }

        // Tâche déjà assignée (20 points)
        if ($task->getAssignedContributor() && $task->getAssignedContributor()->getId() === $candidate['contributor']->getId()) {
            $score += 20;
            $reasoning[] = 'Déjà assigné à cette tâche';
        }

        // Calculer la confiance (0-1)
        $confidence = min(1, $score / 100);

        return [
            'total'      => $score,
            'confidence' => $confidence,
            'reasoning'  => implode(', ', $reasoning),
            'warnings'   => $warnings,
        ];
    }

    /**
     * Récupère les tâches non affectées ou nécessitant une planification.
     *
     * @return array<ProjectTask>
     */
    private function getUnassignedTasks(Project $project): array
    {
        return $this->entityManager->getRepository(ProjectTask::class)
            ->createQueryBuilder('t')
            ->where('t.project = :project')
            ->andWhere('t.active = true')
            ->andWhere('t.status != :completed')
            ->andWhere('t.countsForProfitability = true')
            ->andWhere('t.requiredProfile IS NOT NULL')
            ->setParameter('project', $project)
            ->setParameter('completed', 'completed')
            ->orderBy('t.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Estime la durée de la tâche en jours ouvrés.
     */
    private function estimateTaskDuration(ProjectTask $task): float
    {
        // Utiliser les heures révisées ou vendues
        $hours = $task->getEstimatedHoursRevised() ?? $task->getEstimatedHoursSold() ?? 0;

        // Convertir en jours (1 jour = 7h)
        return $hours > 0 ? ceil($hours / 7) : 0;
    }

    /**
     * Calcule la date de fin en fonction des jours ouvrés.
     */
    private function calculateEndDate(DateTime $startDate, float $days): DateTime
    {
        $endDate    = clone $startDate;
        $daysAdded  = 0;
        $targetDays = ceil($days);

        while ($daysAdded < $targetDays) {
            $dayOfWeek = (int) $endDate->format('N'); // 1=Monday, 7=Sunday
            if ($dayOfWeek < 6) { // Skip weekends
                ++$daysAdded;
            }
            if ($daysAdded < $targetDays) {
                $endDate->modify('+1 day');
            }
        }

        return $endDate;
    }

    /**
     * Récupère la période d'emploi active pour un contributeur.
     */
    private function getActiveEmploymentPeriod(Contributor $contributor, DateTime $date): ?EmploymentPeriod
    {
        return $this->entityManager->getRepository(EmploymentPeriod::class)
            ->createQueryBuilder('ep')
            ->where('ep.contributor = :contributor')
            ->andWhere('ep.startDate <= :date')
            ->andWhere('(ep.endDate IS NULL OR ep.endDate >= :date)')
            ->setParameter('contributor', $contributor)
            ->setParameter('date', $date)
            // TODO : Query uses LIMIT with a fetch-joined collection.
            // This causes LIMIT to apply to SQL rows instead of entities, resulting in partially hydrated collections (silent data loss).
            //
            // Problem: If the main entity has multiple related items, only some will be loaded.
            // For example, if a Pet has 4 pictures and you use setMaxResults(1), only 1 picture will be loaded instead of 4.
            //
            // Solution: Use Doctrine's Paginator which executes 2 queries to properly handle collection joins.
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Calcule les heures journalières pour une période d'emploi.
     */
    private function calculateDailyHours(EmploymentPeriod $period): float
    {
        $weeklyHours = (float) $period->getWeeklyHours();
        $workPct     = (float) $period->getWorkTimePercentage();

        return ($weeklyHours * $workPct / 100) / 5; // 5 jours ouvrés
    }

    /**
     * Calcule la charge actuelle moyenne pour un contributeur sur une période.
     */
    private function calculateCurrentLoad(Contributor $contributor, DateTime $startDate, DateTime $endDate): float
    {
        $plannings = $this->planningRepository->createQueryBuilder('p')
            ->where('p.contributor = :contributor')
            ->andWhere('p.status != :cancelled')
            ->andWhere('p.endDate >= :start')
            ->andWhere('p.startDate <= :end')
            ->setParameter('contributor', $contributor)
            ->setParameter('cancelled', 'cancelled')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->getQuery()
            ->getResult();

        $totalHours = 0;
        $workDays   = 0;

        $period = new DatePeriod($startDate, new DateInterval('P1D'), (clone $endDate)->modify('+1 day'));
        foreach ($period as $date) {
            $dayOfWeek = (int) $date->format('N');
            if ($dayOfWeek < 6) {
                ++$workDays;
                foreach ($plannings as $planning) {
                    if ($date >= $planning->getStartDate() && $date <= $planning->getEndDate()) {
                        $totalHours += (float) $planning->getDailyHours();
                    }
                }
            }
        }

        return $workDays > 0 ? $totalHours / $workDays : 0;
    }

    /**
     * Vérifie si le contributeur a des congés sur la période.
     */
    private function hasVacations(Contributor $contributor, DateTime $startDate, DateTime $endDate): bool
    {
        $vacations = $this->vacationRepository->createQueryBuilder('v')
            ->where('v.contributor = :contributor')
            ->andWhere('v.status = :approved')
            ->andWhere('v.endDate >= :start')
            ->andWhere('v.startDate <= :end')
            ->setParameter('contributor', $contributor)
            ->setParameter('approved', 'approved')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $vacations !== null;
    }

    /**
     * Vérifie si un contributeur a déjà travaillé sur un projet.
     */
    private function hasWorkedOnProject(Contributor $contributor, Project $project): bool
    {
        $count = $this->entityManager->getRepository(Planning::class)
            ->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.contributor = :contributor')
            ->andWhere('p.project = :project')
            ->setParameter('contributor', $contributor)
            ->setParameter('project', $project)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Calcule les statistiques du résultat.
     */
    private function calculateStatistics(array $suggestions, array $unassigned, array $allTasks): array
    {
        $totalTasks      = count($allTasks);
        $assignedTasks   = count($suggestions);
        $unassignedTasks = count($unassigned);

        $avgConfidence = 0;
        if ($assignedTasks > 0) {
            $totalConfidence = array_sum(array_column($suggestions, 'confidence'));
            $avgConfidence   = $totalConfidence / $assignedTasks;
        }

        return [
            'totalTasks'        => $totalTasks,
            'assignedTasks'     => $assignedTasks,
            'unassignedTasks'   => $unassignedTasks,
            'averageConfidence' => round($avgConfidence, 2),
        ];
    }
}
