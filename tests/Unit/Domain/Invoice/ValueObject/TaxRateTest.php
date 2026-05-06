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
        $this->assertSame(2000, $rate->getBasisPoints());
        $this->assertSame(20.0, $rate->getPercentage());
    }

    public function testFromBasisPoints(): void
    {
        $rate = TaxRate::fromBasisPoints(1000);
        $this->assertSame(10.0, $rate->getPercentage());
    }

    public function testStandardFranceIs20Percent(): void
    {
        $this->assertSame(20.0, TaxRate::standardFrance()->getPercentage());
    }

    public function testReducedFranceIs10Percent(): void
    {
        $this->assertSame(10.0, TaxRate::reducedFrance()->getPercentage());
    }

    public function testSuperReducedFranceIs5Point5Percent(): void
    {
        $this->assertSame(5.5, TaxRate::superReducedFrance()->getPercentage());
    }

    public function testZeroRate(): void
    {
        $rate = TaxRate::zero();
        $this->assertTrue($rate->isZero());
        $this->assertSame(0.0, $rate->getPercentage());
    }

    public function testNonZeroRateIsNotZero(): void
    {
        $this->assertFalse(TaxRate::standardFrance()->isZero());
    }

    public function testRejectsNegativeRate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TaxRate::fromBasisPoints(-1);
    }

    public function testRejectsRateAbove100Percent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TaxRate::fromBasisPoints(10001);
    }

    public function testAccepts100PercentBoundary(): void
    {
        $rate = TaxRate::fromBasisPoints(10000);
        $this->assertSame(100.0, $rate->getPercentage());
    }

    public function testGetMultiplier(): void
    {
        $this->assertSame(0.20, TaxRate::standardFrance()->getMultiplier());
        $this->assertSame(0.055, TaxRate::superReducedFrance()->getMultiplier());
    }

    public function testCalculateTax(): void
    {
        $rate = TaxRate::standardFrance();
        $tax = $rate->calculateTax(Money::fromCents(10000));
        $this->assertSame(2000, $tax->getAmountCents());
    }

    public function testCalculateTotalWithTax(): void
    {
        $rate = TaxRate::standardFrance();
        $total = $rate->calculateTotalWithTax(Money::fromCents(10000));
        $this->assertSame(12000, $total->getAmountCents());
    }

    public function testEqualsTrueForSameBasisPoints(): void
    {
        $a = TaxRate::fromPercentage(20.0);
        $b = TaxRate::fromBasisPoints(2000);
        $this->assertTrue($a->equals($b));
    }

    public function testEqualsFalseForDifferentRates(): void
    {
        $this->assertFalse(TaxRate::standardFrance()->equals(TaxRate::reducedFrance()));
    }

    public function testFormat(): void
    {
        $this->assertSame('20.00%', TaxRate::standardFrance()->format());
        $this->assertSame('5.50%', TaxRate::superReducedFrance()->format());
    }

    public function testToString(): void
    {
        $this->assertSame('10.00%', (string) TaxRate::reducedFrance());
    }

    public function testFromPercentageRoundsToBasisPoints(): void
    {
        $rate = TaxRate::fromPercentage(8.555);
        $this->assertSame(856, $rate->getBasisPoints());
    }
}
