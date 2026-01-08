<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Contributor;
use App\Entity\EmploymentPeriod;
use App\Entity\OnboardingTask;
use App\Entity\OnboardingTemplate;
use App\Repository\OnboardingTaskRepository;
use App\Repository\OnboardingTemplateRepository;
use App\Security\CompanyContext;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

class OnboardingService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CompanyContext $companyContext,
        private readonly OnboardingTemplateRepository $templateRepository,
        private readonly OnboardingTaskRepository $taskRepository,
    ) {
    }

    /**
     * Create onboarding tasks from template for a contributor.
     *
     * @return int Number of tasks created
     */
    public function createOnboardingFromTemplate(Contributor $contributor, ?EmploymentPeriod $employmentPeriod = null): int
    {
        // Find appropriate template based on contributor's primary profile
        $profiles = $contributor->getProfiles();
        $template = null;

        // Try to find a template for the first profile
        if (!$profiles->isEmpty()) {
            $profile  = $profiles->first();
            $template = $this->templateRepository->findByProfile($profile);
        }

        // Fallback to default template if no profile-specific template found
        if (null === $template) {
            $template = $this->templateRepository->findDefault();
        }

        if (null === $template) {
            return 0; // No template available
        }

        return $this->createTasksFromTemplate($contributor, $template, $employmentPeriod);
    }

    /**
     * Create tasks from a specific template.
     *
     * @return int Number of tasks created
     */
    public function createTasksFromTemplate(
        Contributor $contributor,
        OnboardingTemplate $template,
        ?EmploymentPeriod $employmentPeriod = null
    ): int {
        $tasks   = $template->getTasks();
        $created = 0;

        // Get start date from employment period or use today
        $startDate = $employmentPeriod?->getStartDate() ?? new DateTimeImmutable();
        if ($startDate instanceof DateTime) {
            $startDate = DateTimeImmutable::createFromMutable($startDate);
        }

        foreach ($tasks as $taskDef) {
            $task = new OnboardingTask();
            $task->setCompany($this->companyContext->getCurrentCompany());
            $task->setContributor($contributor);
            $task->setTemplate($template);
            $task->setTitle($taskDef['title'] ?? 'Sans titre');
            $task->setDescription($taskDef['description'] ?? null);
            $task->setOrderNum($taskDef['order'] ?? 0);
            $task->setAssignedTo($taskDef['assigned_to'] ?? 'contributor');
            $task->setType($taskDef['type'] ?? 'action');
            $task->setDaysAfterStart($taskDef['days_after_start'] ?? 0);

            // Calculate due date
            $daysAfter = $taskDef['days_after_start'] ?? 0;
            $dueDate   = $startDate->modify("+{$daysAfter} days");
            $task->setDueDate($dueDate);

            $this->em->persist($task);
            ++$created;
        }

        $this->em->flush();

        return $created;
    }

    /**
     * Calculate onboarding progress for a contributor.
     *
     * @return int Progress percentage (0-100)
     */
    public function calculateProgress(Contributor $contributor): int
    {
        return $this->taskRepository->calculateProgress($contributor);
    }

    /**
     * Get onboarding tasks grouped by week.
     *
     * @return array{week_num: int, week_label: string, tasks: OnboardingTask[]}[]
     */
    public function getTasksByWeek(Contributor $contributor): array
    {
        $tasks = $this->taskRepository->findByContributor($contributor);

        $grouped = [];

        foreach ($tasks as $task) {
            $weekNum = (int) ceil($task->getDaysAfterStart() / 7);
            if (0 === $weekNum) {
                $weekNum = 1;
            }

            if (!isset($grouped[$weekNum])) {
                $grouped[$weekNum] = [
                    'week_num'   => $weekNum,
                    'week_label' => $this->getWeekLabel($weekNum),
                    'tasks'      => [],
                ];
            }

            $grouped[$weekNum]['tasks'][] = $task;
        }

        ksort($grouped);

        return array_values($grouped);
    }

    /**
     * Mark task as completed.
     */
    public function completeTask(OnboardingTask $task, ?string $comments = null): void
    {
        $task->complete($comments);
        $this->em->flush();
    }

    /**
     * Update task status.
     */
    public function updateTaskStatus(OnboardingTask $task, string $status): void
    {
        $task->setStatus($status);
        $this->em->flush();
    }

    /**
     * Get onboarding statistics for a team.
     *
     * @param int[] $contributorIds
     *
     * @return array<array{contributor_id: int, total: int, completed: int, progress: int, overdue: int}>
     */
    public function getTeamStatistics(array $contributorIds = []): array
    {
        return $this->taskRepository->getTeamStatistics($contributorIds);
    }

    /**
     * Get overdue tasks for a contributor.
     *
     * @return OnboardingTask[]
     */
    public function getOverdueTasks(Contributor $contributor): array
    {
        return $this->taskRepository->findOverdueForContributor($contributor);
    }

    /**
     * Get pending tasks for a contributor.
     *
     * @return OnboardingTask[]
     */
    public function getPendingTasks(Contributor $contributor): array
    {
        return $this->taskRepository->findPendingForContributor($contributor);
    }

    /**
     * Check if onboarding is complete for a contributor.
     */
    public function isOnboardingComplete(Contributor $contributor): bool
    {
        return 100 === $this->calculateProgress($contributor);
    }

    /**
     * Get onboarding summary for a contributor.
     *
     * @return array{progress: int, total: int, completed: int, pending: int, overdue: int, is_complete: bool}
     */
    public function getOnboardingSummary(Contributor $contributor): array
    {
        $allTasks     = $this->taskRepository->findByContributor($contributor);
        $overdueTasks = $this->getOverdueTasks($contributor);
        $pendingTasks = $this->getPendingTasks($contributor);

        $total     = count($allTasks);
        $completed = $total - count($pendingTasks);
        $progress  = $this->calculateProgress($contributor);

        return [
            'progress'    => $progress,
            'total'       => $total,
            'completed'   => $completed,
            'pending'     => count($pendingTasks),
            'overdue'     => count($overdueTasks),
            'is_complete' => 100 === $progress,
        ];
    }

    /**
     * Create a custom onboarding template.
     *
     * @param array $tasks Array of task definitions
     */
    public function createTemplate(
        string $name,
        ?string $description,
        ?int $profileId,
        array $tasks
    ): OnboardingTemplate {
        $template = new OnboardingTemplate();
        $template->setCompany($this->companyContext->getCurrentCompany());
        $template->setName($name);
        $template->setDescription($description);

        if (null !== $profileId) {
            $profile = $this->em->getReference(\App\Entity\Profile::class, $profileId);
            $template->setProfile($profile);
        }

        $template->setTasks($tasks);
        $template->setActive(true);

        $this->em->persist($template);
        $this->em->flush();

        return $template;
    }

    /**
     * Get week label for display.
     */
    private function getWeekLabel(int $weekNum): string
    {
        if (1 === $weekNum) {
            return 'Semaine 1 (Jour 1-7)';
        }

        $startDay = (($weekNum - 1) * 7) + 1;
        $endDay   = $weekNum * 7;

        return "Semaine {$weekNum} (Jour {$startDay}-{$endDay})";
    }

    /**
     * Duplicate template for a new profile.
     */
    public function duplicateTemplate(OnboardingTemplate $sourceTemplate, string $newName, ?int $profileId = null): OnboardingTemplate
    {
        $newTemplate = new OnboardingTemplate();
        $newTemplate->setCompany($this->companyContext->getCurrentCompany());
        $newTemplate->setName($newName);
        $newTemplate->setDescription($sourceTemplate->getDescription());
        $newTemplate->setTasks($sourceTemplate->getTasks());
        $newTemplate->setActive(true);

        if (null !== $profileId) {
            $profile = $this->em->getReference(\App\Entity\Profile::class, $profileId);
            $newTemplate->setProfile($profile);
        }

        $this->em->persist($newTemplate);
        $this->em->flush();

        return $newTemplate;
    }
}
