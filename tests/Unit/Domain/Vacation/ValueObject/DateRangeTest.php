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

        static::assertEquals(new DateTimeImmutable('2025-01-10'), $range->getStartDate());
        static::assertEquals(new DateTimeImmutable('2025-01-14'), $range->getEndDate());
    }

    public function testStartDateAfterEndDateThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        DateRange::fromStrings('2025-01-15', '2025-01-10');
    }

    public function testSameDateIsAllowed(): void
    {
        $range = DateRange::fromStrings('2025-01-10', '2025-01-10');

        static::assertSame(1, $range->getNumberOfDays());
    }

    public function testGetNumberOfDays(): void
    {
        $range = DateRange::fromStrings('2025-01-10', '2025-01-14');

        static::assertSame(5, $range->getNumberOfDays());
    }

    public function testGetNumberOfWorkingDays(): void
    {
        // Mon Jan 6 to Fri Jan 10 = 5 working days
        $range = DateRange::fromStrings('2025-01-06', '2025-01-10');

        static::assertSame(5, $range->getNumberOfWorkingDays());
    }

    public function testGetNumberOfWorkingDaysExcludesWeekends(): void
    {
        // Mon Jan 6 to Sun Jan 12 = 7 days, 5 working
        $range = DateRange::fromStrings('2025-01-06', '2025-01-12');

        static::assertSame(5, $range->getNumberOfWorkingDays());
    }

    public function testContainsDate(): void
    {
        $range = DateRange::fromStrings('2025-01-10', '2025-01-14');

        static::assertTrue($range->containsDate(new DateTimeImmutable('2025-01-12')));
        static::assertFalse($range->containsDate(new DateTimeImmutable('2025-01-15')));
    }

    public function testOverlaps(): void
    {
        $range1 = DateRange::fromStrings('2025-01-10', '2025-01-14');
        $range2 = DateRange::fromStrings('2025-01-13', '2025-01-18');
        $range3 = DateRange::fromStrings('2025-01-15', '2025-01-20');

        static::assertTrue($range1->overlaps($range2));
        static::assertFalse($range1->overlaps($range3));
    }

    public function testEquals(): void
    {
        $range1 = DateRange::fromStrings('2025-01-10', '2025-01-14');
        $range2 = DateRange::fromStrings('2025-01-10', '2025-01-14');
        $range3 = DateRange::fromStrings('2025-01-10', '2025-01-15');

        static::assertTrue($range1->equals($range2));
        static::assertFalse($range1->equals($range3));
    }

    public function testWeekendOnlyRangeReportsZeroWorkingDays(): void
    {
        // Saturday 2025-01-11 -> Sunday 2025-01-12
        $range = DateRange::fromStrings('2025-01-11', '2025-01-12');

        static::assertSame(2, $range->getNumberOfDays());
        static::assertSame(0, $range->getNumberOfWorkingDays());
    }

    public function testLeapYearFebruary29IsCountedAsWorkingDay(): void
    {
        // 2024-02-29 was a Thursday
        $range = DateRange::fromStrings('2024-02-29', '2024-02-29');

        static::assertSame(1, $range->getNumberOfDays());
        static::assertSame(1, $range->getNumberOfWorkingDays());
    }

    public function testFullYearWorkingDaysAround260(): void
    {
        // Smoke test on the full 2025 calendar : neither 0 nor > 366,
        // and within ±5 of the 260 working-day baseline (2025 has 261 working days).
        $range = DateRange::fromStrings('2025-01-01', '2025-12-31');
        $working = $range->getNumberOfWorkingDays();

        static::assertGreaterThan(255, $working);
        static::assertLessThan(265, $working);
    }

    public function testOverlapsTouchingBoundariesIsConsideredOverlap(): void
    {
        $a = DateRange::fromStrings('2025-06-01', '2025-06-15');
        $b = DateRange::fromStrings('2025-06-15', '2025-06-30');

        // The current implementation considers a touching boundary as an overlap (same day on both sides).
        static::assertTrue($a->overlaps($b));
        static::assertTrue($b->overlaps($a));
    }

    public function testNonOverlappingDisjointRanges(): void
    {
        $a = DateRange::fromStrings('2025-06-01', '2025-06-10');
        $b = DateRange::fromStrings('2025-06-12', '2025-06-20');

        static::assertFalse($a->overlaps($b));
        static::assertFalse($b->overlaps($a));
    }

    public function testContainsDateOnEdges(): void
    {
        $range = DateRange::fromStrings('2025-06-01', '2025-06-10');

        static::assertTrue($range->containsDate(new DateTimeImmutable('2025-06-01')));
        static::assertTrue($range->containsDate(new DateTimeImmutable('2025-06-10')));
        static::assertFalse($range->containsDate(new DateTimeImmutable('2025-05-31')));
        static::assertFalse($range->containsDate(new DateTimeImmutable('2025-06-11')));
    }
}
