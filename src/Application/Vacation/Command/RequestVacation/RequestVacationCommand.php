<?php

declare(strict_types=1);

namespace App\Application\Vacation\Command\RequestVacation;

use DateTimeImmutable;

final readonly class RequestVacationCommand
{
    public function __construct(
        public int $contributorId,
        public DateTimeImmutable $startDate,
        public DateTimeImmutable $endDate,
        public string $type,
        public string $dailyHours = '8.00',
        public ?string $reason = null,
    ) {
    }
}
