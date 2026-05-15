<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Invoice\ValueObject;

use App\Domain\Invoice\ValueObject\TaxRate;
use App\Domain\Shared\ValueObject\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TaxRateTest extends TestCase
{
    public function testFromPercentage(): void
    {
        $rate = TaxRate::fromPercentage(20.0);
        static::assertSame(2000, $rate->getBasisPoints());
        static::assertSame(20.0, $rate->getPercentage());
    }

    public function testFromBasisPoints(): void
    {
        $rate = TaxRate::fromBasisPoints(1000);
        static::assertSame(10.0, $rate->getPercentage());
    }

    public function testStandardFranceIs20Percent(): void
    {
        static::assertSame(20.0, TaxRate::standardFrance()->getPercentage());
    }

    public function testReducedFranceIs10Percent(): void
    {
        static::assertSame(10.0, TaxRate::reducedFrance()->getPercentage());
    }

    public function testSuperReducedFranceIs5Point5Percent(): void
    {
        static::assertSame(5.5, TaxRate::superReducedFrance()->getPercentage());
    }

    public function testZeroRate(): void
    {
        $rate = TaxRate::zero();
        static::assertTrue($rate->isZero());
        static::assertSame(0.0, $rate->getPercentage());
    }

    public function testNonZeroRateIsNotZero(): void
    {
        static::assertFalse(TaxRate::standardFrance()->isZero());
    }

    public function testRejectsNegativeRate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TaxRate::fromBasisPoints(-1);
    }

    public function testRejectsRateAbove100Percent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TaxRate::fromBasisPoints(10_001);
    }

    public function testAccepts100PercentBoundary(): void
    {
        $rate = TaxRate::fromBasisPoints(10_000);
        static::assertSame(100.0, $rate->getPercentage());
    }

    public function testGetMultiplier(): void
    {
        static::assertSame(0.20, TaxRate::standardFrance()->getMultiplier());
        static::assertSame(0.055, TaxRate::superReducedFrance()->getMultiplier());
    }

    public function testCalculateTax(): void
    {
        $rate = TaxRate::standardFrance();
        $tax = $rate->calculateTax(Money::fromCents(10_000));
        static::assertSame(2000, $tax->getAmountCents());
    }

    public function testCalculateTotalWithTax(): void
    {
        $rate = TaxRate::standardFrance();
        $total = $rate->calculateTotalWithTax(Money::fromCents(10_000));
        static::assertSame(12_000, $total->getAmountCents());
    }

    public function testEqualsTrueForSameBasisPoints(): void
    {
        $a = TaxRate::fromPercentage(20.0);
        $b = TaxRate::fromBasisPoints(2000);
        static::assertTrue($a->equals($b));
    }

    public function testEqualsFalseForDifferentRates(): void
    {
        static::assertFalse(TaxRate::standardFrance()->equals(TaxRate::reducedFrance()));
    }

    public function testFormat(): void
    {
        static::assertSame('20.00%', TaxRate::standardFrance()->format());
        static::assertSame('5.50%', TaxRate::superReducedFrance()->format());
    }

    public function testToString(): void
    {
        static::assertSame('10.00%', (string) TaxRate::reducedFrance());
    }

    public function testFromPercentageRoundsToBasisPoints(): void
    {
        $rate = TaxRate::fromPercentage(8.555);
        static::assertSame(856, $rate->getBasisPoints());
    }
}
