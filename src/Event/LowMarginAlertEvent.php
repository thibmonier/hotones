<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Project;
use App\Enum\NotificationType;

/**
 * @deprecated Sprint-022 US-105 (AT-3.3 ADR-0016) — use
 * `App\Domain\Project\Event\MarginThresholdExceededEvent` instead.
 *
 * `AlertDetectionService` dispatches both events sprint-022 (coexistence
 * pour préserver in-app notifications via NotificationSubscriber).
 *
 * Removal planned sprint-023+ après refactor `NotificationSubscriber`
 * pour consume Domain Events directement (translator Domain → Notification
 * OR new Domain handler crée Notification entity).
 */
class LowMarginAlertEvent extends NotificationEvent
{
    public function __construct(
        private readonly Project $project,
        private readonly float $predictedMargin,
        private readonly string $severity,
        array $recipients,
    ) {
        $title = $severity === 'critical' ? 'Alerte marge critique' : 'Alerte marge faible';

        $message = sprintf(
            'Le projet "%s" présente une marge prédite de %.1f%% (%s). Action requise pour améliorer la rentabilité.',
            $project->getName(),
            $predictedMargin,
            $severity === 'critical' ? 'critique' : 'faible',
        );

        parent::__construct(
            type: NotificationType::LOW_MARGIN_ALERT,
            title: $title,
            message: $message,
            recipients: $recipients,
            data: [
                'project_id' => $project->getId(),
                'project_name' => $project->getName(),
                'predicted_margin' => $predictedMargin,
                'severity' => $severity,
            ],
            entityType: 'Project',
            entityId: $project->getId(),
        );
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function getPredictedMargin(): float
    {
        return $this->predictedMargin;
    }

    public function getSeverity(): string
    {
        return $this->severity;
    }
}
