<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Contributor;
use App\Enum\NotificationType;
use DateTimeImmutable;

class ContributorOverloadAlertEvent extends NotificationEvent
{
    public function __construct(
        private readonly Contributor $contributor,
        private readonly DateTimeImmutable $month,
        private readonly float $capacityRate,
        private readonly float $totalDays,
        array $recipients,
    ) {
        $title = 'Alerte surcharge contributeur';

        $message = sprintf(
            '%s est en surcharge pour %s : %.1f jours assignés (%.0f%% de capacité). Réajustement nécessaire.',
            $contributor->getFullName(),
            $month->format('F Y'),
            $totalDays,
            $capacityRate,
        );

        parent::__construct(
            type: NotificationType::CONTRIBUTOR_OVERLOAD_ALERT,
            title: $title,
            message: $message,
            recipients: $recipients,
            data: [
                'contributor_id'   => $contributor->getId(),
                'contributor_name' => $contributor->getFullName(),
                'month'            => $month->format('Y-m'),
                'capacity_rate'    => $capacityRate,
                'total_days'       => $totalDays,
            ],
            entityType: 'Contributor',
            entityId: $contributor->getId(),
        );
    }

    public function getContributor(): Contributor
    {
        return $this->contributor;
    }

    public function getMonth(): DateTimeImmutable
    {
        return $this->month;
    }

    public function getCapacityRate(): float
    {
        return $this->capacityRate;
    }

    public function getTotalDays(): float
    {
        return $this->totalDays;
    }
}
