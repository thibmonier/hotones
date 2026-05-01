<?php

declare(strict_types=1);

namespace App\Domain\Vacation\Event;

use App\Domain\Vacation\ValueObject\VacationId;
use DateTimeImmutable;

final readonly class VacationApproved
{
    public function __construct(
        public VacationId $vacationId,
        public int $approvedByUserId,
        public DateTimeImmutable $occurredOn = new DateTimeImmutable(),
    ) {
    }
}
