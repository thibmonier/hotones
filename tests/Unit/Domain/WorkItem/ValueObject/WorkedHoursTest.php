<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\WorkItem\ValueObject;

use App\Domain\WorkItem\ValueObject\WorkedHours;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class WorkedHoursTest extends TestCase
{
    public function testFromFloatPositive(): void
    {
        $h = WorkedHours::fromFloat(7.5);
        self::assertSame(7.5, $h->getValue());
        self::assertSame('7.50', (string) $h);
    }

    public function testFromDecimalString(): void
    {
        $h = WorkedHours::fromDecimalString('8.00');
        self::assertSame(8.0, $h->getValue());
    }

    public function testZeroThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/strictly positive/');
        WorkedHours::fromFloat(0.0);
    }

    public function testNegativeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        WorkedHours::fromFloat(-1.0);
    }

    public function testExceeds24HoursThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/cannot exceed/');
        WorkedHours::fromFloat(24.5);
    }

    public function testExactly24HoursAccepted(): void
    {
        $h = WorkedHours::fromFloat(24.0);
        self::assertSame(24.0, $h->getValue());
    }

    public function testRoundsTo2DecimalPlaces(): void
    {
        $h = WorkedHours::fromFloat(7.123456);
        self::assertSame(7.12, $h->getValue());
    }

    public function testEquals(): void
    {
        $a = WorkedHours::fromFloat(7.5);
        $b = WorkedHours::fromFloat(7.5);
        $c = WorkedHours::fromFloat(8.0);

        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals($c));
    }
}
