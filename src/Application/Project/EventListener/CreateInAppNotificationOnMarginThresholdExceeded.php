<?php

declare(strict_types=1);

namespace App\Application\Project\EventListener;

use App\Domain\Project\Event\MarginThresholdExceededEvent;
use App\Entity\Project as FlatProject;
use App\Entity\User;
use App\Enum\NotificationType;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * EPIC-003 Phase 3 (sprint-023 US-106 — AT-3.3 ADR-0016 strangler fig
 * completion) — handler async cross-aggregate Application Layer ACL
 * Domain → Notification.
 *
 * Consume `MarginThresholdExceededEvent` Domain Event + crée notifications
 * in-app via `NotificationService` (substitute legacy `LowMarginAlertEvent`
 * dispatched par `AlertDetectionService` sprint-022 US-105 dual dispatch).
 *
 * Recipients :
 * - Project Manager (PM)
 * - Key Account Manager (KAM)
 * - Tous users ROLE_MANAGER
 *
 * Sprint-023 US-106 = completion AT-3.3 ADR-0016 strangler fig :
 * `LowMarginAlertEvent` legacy supprimé + dual dispatch éliminé
 * AlertDetectionService.
 */
#[AsMessageHandler]
final readonly class CreateInAppNotificationOnMarginThresholdExceeded
{
    public function __construct(
        private NotificationService $notificationService,
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(MarginThresholdExceededEvent $event): void
    {
        $projectIdLegacy = (int) $event->getAggregateId();
        $project = $this->entityManager->find(FlatProject::class, $projectIdLegacy);

        if (!$project instanceof FlatProject) {
            $this->logger->warning('Project not found for margin notification', [
                'project_id_aggregate' => $event->getAggregateId(),
            ]);

            return;
        }

        $recipients = $this->resolveRecipients($project);

        if ($recipients === []) {
            $this->logger->info('No recipients for margin alert notification', [
                'project_id' => $projectIdLegacy,
            ]);

            return;
        }

        $title = sprintf('Alerte marge — %s (%.1f %%)', $event->projectName, $event->marginPercent);
        $message = sprintf(
            'Le projet "%s" présente une marge actuelle de %.1f %% (seuil : %.1f %%). Action recommandée.',
            $event->projectName,
            $event->marginPercent,
            $event->thresholdPercent,
        );

        $created = 0;
        foreach ($recipients as $recipient) {
            $this->notificationService->createNotification(
                recipient: $recipient,
                type: NotificationType::LOW_MARGIN_ALERT,
                title: $title,
                message: $message,
                data: [
                    'project_id' => $projectIdLegacy,
                    'project_name' => $event->projectName,
                    'margin_percent' => $event->marginPercent,
                    'threshold_percent' => $event->thresholdPercent,
                    'is_critical' => $event->isCritical(),
                ],
                entityType: 'Project',
                entityId: $projectIdLegacy,
            );
            ++$created;
        }

        $this->logger->info('Margin alert notifications created', [
            'project_id' => $projectIdLegacy,
            'recipients_count' => count($recipients),
            'notifications_created' => $created,
        ]);
    }

    /**
     * @return list<User>
     */
    private function resolveRecipients(FlatProject $project): array
    {
        $recipients = [];
        $seenIds = [];

        $pm = $project->getProjectManager();
        if ($pm !== null) {
            $recipients[] = $pm;
            $seenIds[] = $pm->getId();
        }

        $kam = $project->getKeyAccountManager();
        if ($kam !== null && !in_array($kam->getId(), $seenIds, true)) {
            $recipients[] = $kam;
            $seenIds[] = $kam->getId();
        }

        foreach ($this->userRepository->findByRole('ROLE_MANAGER') as $manager) {
            if (in_array($manager->getId(), $seenIds, true)) {
                continue;
            }

            $recipients[] = $manager;
            $seenIds[] = $manager->getId();
        }

        return $recipients;
    }
}
