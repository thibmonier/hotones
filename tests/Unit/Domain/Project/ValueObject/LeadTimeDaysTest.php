<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Project\ValueObject;

use App\Domain\Project\ValueObject\LeadTimeDays;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class LeadTimeDaysTest extends TestCase
{
    public function testCreatesFromPositiveDays(): void
    {
        $lead = LeadTimeDays::fromDays(15.7);

        static::assertSame(15.7, $lead->getDays());
    }

    public function testRoundsToOneDecimal(): void
    {
        $lead = LeadTimeDays::fromDays(15.712_34);

        static::assertSame(15.7, $lead->getDays());
    }

    public function testZeroReturnsZeroDays(): void
    {
        static::assertSame(0.0, LeadTimeDays::zero()->getDays());
    }

    public function testRejectsNegativeDays(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Lead time cannot be negative');

        LeadTimeDays::fromDays(-1.0);
    }

    public function testEqualsWithinDelta(): void
    {
        static::assertTrue(LeadTimeDays::fromDays(15.0)->equals(LeadTimeDays::fromDays(15.005)));
        static::assertFalse(LeadTimeDays::fromDays(15.0)->equals(LeadTimeDays::fromDays(15.5)));
    }

    public function testStringable(): void
    {
        static::assertSame('15.5', (string) LeadTimeDays::fromDays(15.5));
    }
}
