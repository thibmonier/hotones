<?php

declare(strict_types=1);

namespace App\Domain\Vacation\Event;

use App\Domain\Vacation\ValueObject\VacationId;
use DateTimeImmutable;

final readonly class VacationCancelled
{
    public function __construct(
        public VacationId $vacationId,
        public DateTimeImmutable $occurredOn = new DateTimeImmutable(),
    ) {
    }
}
