<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Vacation\ValueObject;

use App\Domain\Vacation\ValueObject\DateRange;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class DateRangeTest extends TestCase
{
    public function testCreateValidDateRange(): void
    {
        $range = DateRange::fromStrings('2025-01-10', '2025-01-14');

        self::assertEquals(new DateTimeImmutable('2025-01-10'), $range->getStartDate());
        self::assertEquals(new DateTimeImmutable('2025-01-14'), $range->getEndDate());
    }

    public function testStartDateAfterEndDateThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        DateRange::fromStrings('2025-01-15', '2025-01-10');
    }

    public function testSameDateIsAllowed(): void
    {
        $range = DateRange::fromStrings('2025-01-10', '2025-01-10');

        self::assertEquals(1, $range->getNumberOfDays());
    }

    public function testGetNumberOfDays(): void
    {
        $range = DateRange::fromStrings('2025-01-10', '2025-01-14');

        self::assertEquals(5, $range->getNumberOfDays());
    }

    public function testGetNumberOfWorkingDays(): void
    {
        // Mon Jan 6 to Fri Jan 10 = 5 working days
        $range = DateRange::fromStrings('2025-01-06', '2025-01-10');

        self::assertEquals(5, $range->getNumberOfWorkingDays());
    }

    public function testGetNumberOfWorkingDaysExcludesWeekends(): void
    {
        // Mon Jan 6 to Sun Jan 12 = 7 days, 5 working
        $range = DateRange::fromStrings('2025-01-06', '2025-01-12');

        self::assertEquals(5, $range->getNumberOfWorkingDays());
    }

    public function testContainsDate(): void
    {
        $range = DateRange::fromStrings('2025-01-10', '2025-01-14');

        self::assertTrue($range->containsDate(new DateTimeImmutable('2025-01-12')));
        self::assertFalse($range->containsDate(new DateTimeImmutable('2025-01-15')));
    }

    public function testOverlaps(): void
    {
        $range1 = DateRange::fromStrings('2025-01-10', '2025-01-14');
        $range2 = DateRange::fromStrings('2025-01-13', '2025-01-18');
        $range3 = DateRange::fromStrings('2025-01-15', '2025-01-20');

        self::assertTrue($range1->overlaps($range2));
        self::assertFalse($range1->overlaps($range3));
    }

    public function testEquals(): void
    {
        $range1 = DateRange::fromStrings('2025-01-10', '2025-01-14');
        $range2 = DateRange::fromStrings('2025-01-10', '2025-01-14');
        $range3 = DateRange::fromStrings('2025-01-10', '2025-01-15');

        self::assertTrue($range1->equals($range2));
        self::assertFalse($range1->equals($range3));
    }
}
