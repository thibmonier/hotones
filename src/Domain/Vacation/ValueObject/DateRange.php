<?php

declare(strict_types=1);

namespace App\Domain\Vacation\ValueObject;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

final readonly class DateRange
{
    private function __construct(
        private DateTimeImmutable $startDate,
        private DateTimeImmutable $endDate,
    ) {
        if ($startDate > $endDate) {
            throw new InvalidArgumentException('Start date must be before or equal to end date');
        }
    }

    public static function create(DateTimeImmutable $startDate, DateTimeImmutable $endDate): self
    {
        return new self($startDate, $endDate);
    }

    public static function fromStrings(string $startDate, string $endDate): self
    {
        return new self(
            new DateTimeImmutable($startDate),
            new DateTimeImmutable($endDate),
        );
    }

    public function getStartDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    public function getEndDate(): DateTimeImmutable
    {
        return $this->endDate;
    }

    public function getNumberOfDays(): int
    {
        return $this->startDate->diff($this->endDate)->days + 1;
    }

    public function getNumberOfWorkingDays(): int
    {
        $current = $this->startDate;
        $days = 0;

        while ($current <= $this->endDate) {
            $dayOfWeek = (int) $current->format('w');
            if ($dayOfWeek !== 0 && $dayOfWeek !== 6) {
                ++$days;
            }
            $current = $current->modify('+1 day');
        }

        return $days;
    }

    public function containsDate(DateTimeInterface $date): bool
    {
        return $date >= $this->startDate && $date <= $this->endDate;
    }

    public function overlaps(self $other): bool
    {
        return $this->startDate <= $other->endDate && $other->startDate <= $this->endDate;
    }

    public function equals(self $other): bool
    {
        return $this->startDate == $other->startDate
            && $this->endDate == $other->endDate;
    }
}
