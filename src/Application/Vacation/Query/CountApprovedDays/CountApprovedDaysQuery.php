<?php

declare(strict_types=1);

namespace App\Application\Vacation\Query\CountApprovedDays;

use DateTimeInterface;

final readonly class CountApprovedDaysQuery
{
    public function __construct(
        public DateTimeInterface $startDate,
        public DateTimeInterface $endDate,
    ) {
    }
}
