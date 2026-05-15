<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Vacation\ValueObject;

use App\Domain\Vacation\ValueObject\DailyHours;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class DailyHoursTest extends TestCase
{
    public function testFullDay(): void
    {
        $hours = DailyHours::fullDay();

        static::assertSame('8.00', $hours->getValue());
        static::assertSame(8.0, $hours->toFloat());
    }

    public function testFromString(): void
    {
        $hours = DailyHours::fromString('4.00');

        static::assertSame('4.00', $hours->getValue());
    }

    public function testNegativeHoursThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        DailyHours::fromString('-1.00');
    }

    public function testExceedingMaxHoursThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        DailyHours::fromString('9.00');
    }

    public function testCalculateTotalHours(): void
    {
        $hours = DailyHours::fromString('8.00');

        static::assertSame('40.00', $hours->calculateTotalHours(5));
    }

    public function testHalfDayCalculation(): void
    {
        $hours = DailyHours::fromString('4.00');

        static::assertSame('20.00', $hours->calculateTotalHours(5));
    }
}
