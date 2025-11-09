<?php

namespace App\Event;

use App\Entity\Project;
use App\Enum\NotificationType;

class ProjectBudgetAlertEvent extends NotificationEvent
{
    public function __construct(
        private readonly Project $project,
        private readonly float $usedPercentage,
        array $recipients
    ) {
        $title   = 'Alerte budget projet';
        $message = sprintf(
            'Le projet "%s" a consommé %.1f%% de son budget. Il reste %.0f jours prévus sur %.0f jours budgétés.',
            $project->getName(),
            $usedPercentage,
            $project->calculateRemainingDays(),
            $project->calculateBudgetedDays(),
        );

        parent::__construct(
            type: NotificationType::PROJECT_BUDGET_ALERT,
            title: $title,
            message: $message,
            recipients: $recipients,
            data: [
                'project_id'      => $project->getId(),
                'project_name'    => $project->getName(),
                'used_percentage' => $usedPercentage,
                'remaining_days'  => $project->calculateRemainingDays(),
                'budgeted_days'   => $project->calculateBudgetedDays(),
            ],
            entityType: 'Project',
            entityId: $project->getId(),
        );
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function getUsedPercentage(): float
    {
        return $this->usedPercentage;
    }
}
