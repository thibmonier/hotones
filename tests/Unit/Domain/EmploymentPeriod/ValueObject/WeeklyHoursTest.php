<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\EmploymentPeriod\ValueObject;

use App\Domain\EmploymentPeriod\ValueObject\WeeklyHours;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class WeeklyHoursTest extends TestCase
{
    public function testFromFloatPositive(): void
    {
        $h = WeeklyHours::fromFloat(35.0);
        static::assertSame(35.0, $h->getValue());
        static::assertSame('35.00', (string) $h);
    }

    public function testFromDecimalString(): void
    {
        $h = WeeklyHours::fromDecimalString('35.00');
        static::assertSame(35.0, $h->getValue());
    }

    public function testZeroThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/strictly positive/');
        WeeklyHours::fromFloat(0.0);
    }

    public function testNegativeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        WeeklyHours::fromFloat(-1.0);
    }

    public function testExceeds80HoursThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/cannot exceed/');
        WeeklyHours::fromFloat(80.5);
    }

    public function testExactly80HoursAccepted(): void
    {
        $h = WeeklyHours::fromFloat(80.0);
        static::assertSame(80.0, $h->getValue());
    }

    public function testEqualsTrueWithSameValue(): void
    {
        $a = WeeklyHours::fromFloat(35.0);
        $b = WeeklyHours::fromFloat(35.0);
        static::assertTrue($a->equals($b));
    }

    public function testEqualsFalseWithDifferentValue(): void
    {
        $a = WeeklyHours::fromFloat(35.0);
        $b = WeeklyHours::fromFloat(40.0);
        static::assertFalse($a->equals($b));
    }
}
