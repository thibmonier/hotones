<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Project;
use App\Event\ContributorOverloadAlertEvent;
use App\Event\LowMarginAlertEvent;
use App\Event\PaymentDueAlertEvent;
use App\Event\ProjectBudgetAlertEvent;
use App\Repository\ContributorRepository;
use App\Repository\OrderRepository;
use App\Repository\ProjectRepository;
use App\Repository\StaffingMetricsRepository;
use App\Repository\UserRepository;
use App\Service\Workload\DoctrineWorkloadCalculator;
use App\Service\Workload\WorkloadCalculatorInterface;
use DateTime;
use DateTimeImmutable;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AlertDetectionService
{
    private readonly WorkloadCalculatorInterface $workloadCalculator;

    public function __construct(
        private readonly ProjectRepository $projectRepository,
        private readonly OrderRepository $orderRepository,
        private readonly UserRepository $userRepository,
        private readonly ContributorRepository $contributorRepository,
        StaffingMetricsRepository $staffingMetricsRepository,
        private readonly ProfitabilityPredictor $profitabilityPredictor,
        private readonly EventDispatcherInterface $eventDispatcher,
        ?WorkloadCalculatorInterface $workloadCalculator = null,
    ) {
        // Backwards-compatible default: build the Doctrine impl from the
        // injected StaffingMetricsRepository when no calculator is explicitly
        // provided. Tests pass a mock to bypass the real QueryBuilder. The
        // repository itself is no longer kept as a property — only the
        // calculator is needed afterwards.
        $this->workloadCalculator = $workloadCalculator
            ?? new DoctrineWorkloadCalculator($staffingMetricsRepository);
    }

    /**
     * Check all alerts and return statistics.
     */
    public function checkAllAlerts(): array
    {
        return [
            'budget_alerts' => $this->checkBudgetAlerts(),
            'margin_alerts' => $this->checkMarginAlerts(),
            'overload_alerts' => $this->checkWorkloadAlerts(),
            'payment_alerts' => $this->checkPaymentAlerts(),
        ];
    }

    /**
     * Check for budget overrun alerts.
     * Threshold: >80% consumed AND <20% time remaining.
     */
    private function checkBudgetAlerts(): int
    {
        $alertCount = 0;

        // Find active projects
        $projects = $this->projectRepository->findBy(['status' => ['en_cours', 'planifie']]);

        foreach ($projects as $project) {
            // Skip projects without budget data
            $soldHours = $project->calculateBudgetedDays();
            if ($soldHours <= 0) {
                continue;
            }

            $spentHours = (float) $project->getTotalTasksSpentHours();
            $consumedPct = ($spentHours / $soldHours) * 100;
            $globalProgress = (float) $project->getGlobalProgress();
            $timeRemaining = 100 - $globalProgress;

            // Alert threshold: >80% budget consumed AND <20% time remaining
            if ($consumedPct >= 80 && $timeRemaining < 20) {
                $recipients = $this->getBudgetAlertRecipients($project);

                $this->eventDispatcher->dispatch(new ProjectBudgetAlertEvent($project, $consumedPct, $recipients));

                ++$alertCount;
            }
        }

        return $alertCount;
    }

    /**
     * Check for low margin alerts.
     * Thresholds: <10% critical, <20% warning.
     */
    private function checkMarginAlerts(): int
    {
        $alertCount = 0;

        // Find active projects with sufficient progress for prediction
        $projects = $this->projectRepository->findBy(['status' => ['en_cours']]);

        foreach ($projects as $project) {
            $prediction = $this->profitabilityPredictor->predictProfitability($project);

            // Skip if prediction not possible
            if (!$prediction['canPredict']) {
                continue;
            }

            $predictedMargin = $prediction['predictedMargin']['projected'] ?? null;

            if ($predictedMargin === null) {
                continue;
            }

            // Determine severity
            $severity = null;
            if ($predictedMargin < 10) {
                $severity = 'critical';
            } elseif ($predictedMargin < 20) {
                $severity = 'warning';
            }

            if ($severity !== null) {
                $recipients = $this->getMarginAlertRecipients($project);

                $this->eventDispatcher->dispatch(
                    new LowMarginAlertEvent($project, $predictedMargin, $severity, $recipients),
                );

                ++$alertCount;
            }
        }

        return $alertCount;
    }

    /**
     * Check for contributor overload alerts.
     * Threshold: >100% capacity.
     */
    private function checkWorkloadAlerts(): int
    {
        $alertCount = 0;
        $now = new DateTimeImmutable();

        // Check next 3 months
        for ($i = 0; $i < 3; ++$i) {
            $month = $now->modify("+{$i} months")->modify('first day of this month');

            // Get all active contributors
            $contributors = $this->contributorRepository->findActiveContributors();

            foreach ($contributors as $contributor) {
                // Calculate contributor workload for the month
                $workload = $this->workloadCalculator->forContributor($contributor->getId(), $month);

                if ($workload['capacityRate'] > 100) {
                    $recipients = $this->getWorkloadAlertRecipients();

                    $this->eventDispatcher->dispatch(
                        new ContributorOverloadAlertEvent(
                            $contributor,
                            $month,
                            $workload['capacityRate'],
                            $workload['totalDays'],
                            $recipients,
                        ),
                    );

                    ++$alertCount;
                }
            }
        }

        return $alertCount;
    }

    /**
     * Check for payment due alerts.
     * Threshold: <7 days until billing date.
     */
    private function checkPaymentAlerts(): int
    {
        $alertCount = 0;
        $sevenDaysFromNow = new DateTimeImmutable()->modify('+7 days');

        // Find all active orders
        $orders = $this->orderRepository->findBy(['status' => ['signe', 'gagne', 'en_cours']]);

        foreach ($orders as $order) {
            foreach ($order->getPaymentSchedules() as $schedule) {
                // Check if due within 7 days
                // Note: Actual billing status tracking may be elsewhere in the system
                if ($schedule->getBillingDate() <= $sevenDaysFromNow) {
                    $recipients = $this->getPaymentAlertRecipients($order);
                    $daysUntilDue = new DateTime()->diff($schedule->getBillingDate())->days;

                    $this->eventDispatcher->dispatch(
                        new PaymentDueAlertEvent($order, $schedule->getBillingDate(), $daysUntilDue, $recipients),
                    );

                    ++$alertCount;
                }
            }
        }

        return $alertCount;
    }

    /**
     * Get recipients for budget alerts.
     */
    private function getBudgetAlertRecipients(Project $project): array
    {
        $recipients = [];

        // Add project manager
        if ($project->getProjectManager()) {
            $recipients[] = $project->getProjectManager();
        }

        // Add key account manager
        if ($project->getKeyAccountManager()) {
            $recipients[] = $project->getKeyAccountManager();
        }

        // Add all managers
        $managers = $this->userRepository->findByRole('ROLE_MANAGER');
        foreach ($managers as $manager) {
            $recipients[] = $manager;
        }

        return $this->uniqueUsers($recipients);
    }

    /**
     * Get recipients for margin alerts.
     */
    private function getMarginAlertRecipients(Project $project): array
    {
        // Same as budget alerts
        return $this->getBudgetAlertRecipients($project);
    }

    /**
     * Get recipients for workload alerts.
     */
    private function getWorkloadAlertRecipients(): array
    {
        // Only managers
        return $this->userRepository->findByRole('ROLE_MANAGER');
    }

    /**
     * Get recipients for payment alerts.
     */
    private function getPaymentAlertRecipients($order): array
    {
        $recipients = [];
        $project = $order->getProject();

        // Add project manager
        if ($project && $project->getProjectManager()) {
            $recipients[] = $project->getProjectManager();
        }

        // Add key account manager
        if ($project && $project->getKeyAccountManager()) {
            $recipients[] = $project->getKeyAccountManager();
        }

        // Add accounting users (ROLE_COMPTA)
        $accountingUsers = $this->userRepository->findByRole('ROLE_COMPTA');
        foreach ($accountingUsers as $user) {
            $recipients[] = $user;
        }

        return $this->uniqueUsers($recipients);
    }

    /**
     * Remove duplicate users by ID.
     *
     * @param \App\Entity\User[] $users
     *
     * @return \App\Entity\User[]
     */
    private function uniqueUsers(array $users): array
    {
        $uniqueIds = [];
        $result = [];

        foreach ($users as $user) {
            if (!in_array($user->getId(), $uniqueIds, true)) {
                $uniqueIds[] = $user->getId();
                $result[] = $user;
            }
        }

        return $result;
    }
}
