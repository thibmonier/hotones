<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Shared\ValueObject;

use App\Domain\Shared\ValueObject\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function testFromCentsAndAmount(): void
    {
        $a = Money::fromCents(12345);
        $this->assertSame(12345, $a->getAmountCents());
        $this->assertSame(123.45, $a->getAmount());
        $this->assertSame('EUR', $a->getCurrency());

        $b = Money::fromAmount(99.99);
        $this->assertSame(9999, $b->getAmountCents());
    }

    public function testZero(): void
    {
        $z = Money::zero();
        $this->assertTrue($z->isZero());
        $this->assertFalse($z->isPositive());
    }

    public function testNegativeAmountRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::fromAmount(-1.0);
    }

    public function testEmptyCurrencyRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::fromCents(100, '');
    }

    public function testAddSameCurrency(): void
    {
        $sum = Money::fromCents(100)->add(Money::fromCents(50));
        $this->assertSame(150, $sum->getAmountCents());
    }

    public function testAddDifferentCurrencyRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency mismatch: EUR vs USD');
        Money::fromCents(100)->add(Money::fromCents(50, 'USD'));
    }

    public function testSubtract(): void
    {
        $diff = Money::fromCents(200)->subtract(Money::fromCents(75));
        $this->assertSame(125, $diff->getAmountCents());
    }

    public function testSubtractNegativeRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('negative');
        Money::fromCents(50)->subtract(Money::fromCents(100));
    }

    public function testMultiply(): void
    {
        $this->assertSame(300, Money::fromCents(100)->multiply(3.0)->getAmountCents());
        $this->assertSame(150, Money::fromCents(100)->multiply(1.5)->getAmountCents());
    }

    public function testMultiplyNegativeRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::fromCents(100)->multiply(-1.0);
    }

    public function testPercentage(): void
    {
        $this->assertSame(2000, Money::fromCents(10000)->percentage(20)->getAmountCents());
    }

    public function testComparators(): void
    {
        $a = Money::fromCents(100);
        $b = Money::fromCents(200);
        $this->assertTrue($b->isGreaterThan($a));
        $this->assertTrue($b->isGreaterThanOrEqual($a));
        $this->assertTrue($a->isLessThan($b));
        $this->assertFalse($a->isGreaterThan($a));
        $this->assertTrue($a->isGreaterThanOrEqual($a));
    }

    public function testEquality(): void
    {
        $a = Money::fromCents(100, 'EUR');
        $b = Money::fromCents(100, 'EUR');
        $c = Money::fromCents(100, 'USD');
        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    public function testFormat(): void
    {
        $this->assertSame('1 234,56 EUR', Money::fromCents(123456)->format());
        $this->assertSame('1 234,56 EUR', (string) Money::fromCents(123456));
    }
}
