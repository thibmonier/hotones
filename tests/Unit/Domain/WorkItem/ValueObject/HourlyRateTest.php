<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\WorkItem\ValueObject;

use App\Domain\Shared\ValueObject\Money;
use App\Domain\WorkItem\ValueObject\HourlyRate;
use App\Domain\WorkItem\ValueObject\WorkedHours;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class HourlyRateTest extends TestCase
{
    public function testFromAmount(): void
    {
        $rate = HourlyRate::fromAmount(50.0);
        self::assertTrue($rate->getAmount()->equals(Money::fromAmount(50.0)));
    }

    public function testFromMoney(): void
    {
        $money = Money::fromAmount(75.0);
        $rate = HourlyRate::fromMoney($money);
        self::assertTrue($rate->getAmount()->equals($money));
    }

    public function testFromDailyRateDividesBy8(): void
    {
        $rate = HourlyRate::fromDailyRate(400.0);
        // 400 / 8 = 50 EUR/h
        self::assertTrue($rate->getAmount()->equals(Money::fromAmount(50.0)));
    }

    public function testFromDailyRateNullThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/null or non-positive/');
        HourlyRate::fromDailyRate(null);
    }

    public function testFromDailyRateZeroThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        HourlyRate::fromDailyRate(0.0);
    }

    public function testFromDailyRateNegativeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        HourlyRate::fromDailyRate(-100.0);
    }

    public function testFromDailyRateDecimalString(): void
    {
        $rate = HourlyRate::fromDailyRateDecimalString('480.00');
        // 480 / 8 = 60 EUR/h
        self::assertTrue($rate->getAmount()->equals(Money::fromAmount(60.0)));
    }

    public function testFromDailyRateDecimalStringNullThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        HourlyRate::fromDailyRateDecimalString(null);
    }

    public function testFromDailyRateDecimalStringEmptyThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        HourlyRate::fromDailyRateDecimalString('   ');
    }

    public function testFromDailyRateDecimalStringNegativeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        HourlyRate::fromDailyRateDecimalString('-50');
    }

    public function testMultiplyByHours(): void
    {
        $rate = HourlyRate::fromAmount(50.0);
        $hours = WorkedHours::fromFloat(8.0);
        $total = $rate->multiply($hours);

        // 50 × 8 = 400 EUR
        self::assertSame(40000, $total->getAmountCents());
    }

    public function testEquals(): void
    {
        $a = HourlyRate::fromAmount(50.0);
        $b = HourlyRate::fromAmount(50.0);
        $c = HourlyRate::fromAmount(60.0);

        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals($c));
    }
}
