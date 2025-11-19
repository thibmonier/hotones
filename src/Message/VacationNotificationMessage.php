<?php

declare(strict_types=1);

namespace App\Message;

class VacationNotificationMessage
{
    public function __construct(
        private readonly int $vacationId,
        private readonly string $type // 'created', 'approved', 'rejected'
    ) {
    }

    public function getVacationId(): int
    {
        return $this->vacationId;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
