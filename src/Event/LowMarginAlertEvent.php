<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Project;
use App\Enum\NotificationType;

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
                'project_id'       => $project->getId(),
                'project_name'     => $project->getName(),
                'predicted_margin' => $predictedMargin,
                'severity'         => $severity,
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
