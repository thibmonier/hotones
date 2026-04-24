<?php

declare(strict_types=1);

namespace App\Application\Vacation\Notification\Message;

final readonly class VacationNotificationMessage
{
    public function __construct(
        private string $vacationId,
        private string $type, // 'created', 'approved', 'rejected'
    ) {
    }

    public function getVacationId(): string
    {
        return $this->vacationId;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
