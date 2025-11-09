<?php

namespace App\Event;

use App\Entity\Contributor;
use App\Enum\NotificationType;

class TimesheetPendingValidationEvent extends NotificationEvent
{
    public function __construct(
        private readonly Contributor $contributor,
        private readonly int $pendingCount,
        private readonly float $totalHours,
        array $recipients
    ) {
        $title   = 'Temps en attente de validation';
        $message = sprintf(
            '%s a %d entrée(s) de temps en attente de validation, pour un total de %.1f heures.',
            $contributor->getFullName(),
            $pendingCount,
            $totalHours,
        );

        parent::__construct(
            type: NotificationType::TIMESHEET_PENDING_VALIDATION,
            title: $title,
            message: $message,
            recipients: $recipients,
            data: [
                'contributor_id'   => $contributor->getId(),
                'contributor_name' => $contributor->getFullName(),
                'pending_count'    => $pendingCount,
                'total_hours'      => $totalHours,
            ],
            entityType: 'Timesheet',
            entityId: null, // Pas d'entité spécifique car multiple
        );
    }

    public function getContributor(): Contributor
    {
        return $this->contributor;
    }

    public function getPendingCount(): int
    {
        return $this->pendingCount;
    }

    public function getTotalHours(): float
    {
        return $this->totalHours;
    }
}
