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
        $a = Money::fromCents(12_345);
        static::assertSame(12_345, $a->getAmountCents());
        static::assertSame(123.45, $a->getAmount());
        static::assertSame('EUR', $a->getCurrency());

        $b = Money::fromAmount(99.99);
        static::assertSame(9999, $b->getAmountCents());
    }

    public function testZero(): void
    {
        $z = Money::zero();
        static::assertTrue($z->isZero());
        static::assertFalse($z->isPositive());
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
        static::assertSame(150, $sum->getAmountCents());
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
        static::assertSame(125, $diff->getAmountCents());
    }

    public function testSubtractNegativeRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('negative');
        Money::fromCents(50)->subtract(Money::fromCents(100));
    }

    public function testMultiply(): void
    {
        static::assertSame(300, Money::fromCents(100)->multiply(3.0)->getAmountCents());
        static::assertSame(150, Money::fromCents(100)->multiply(1.5)->getAmountCents());
    }

    public function testMultiplyNegativeRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::fromCents(100)->multiply(-1.0);
    }

    public function testPercentage(): void
    {
        static::assertSame(2000, Money::fromCents(10_000)->percentage(20)->getAmountCents());
    }

    public function testComparators(): void
    {
        $a = Money::fromCents(100);
        $b = Money::fromCents(200);
        static::assertTrue($b->isGreaterThan($a));
        static::assertTrue($b->isGreaterThanOrEqual($a));
        static::assertTrue($a->isLessThan($b));
        static::assertFalse($a->isGreaterThan($a));
        static::assertTrue($a->isGreaterThanOrEqual($a));
    }

    public function testEquality(): void
    {
        $a = Money::fromCents(100, 'EUR');
        $b = Money::fromCents(100, 'EUR');
        $c = Money::fromCents(100, 'USD');
        static::assertTrue($a->equals($b));
        static::assertFalse($a->equals($c));
    }

    public function testFormat(): void
    {
        static::assertSame('1 234,56 EUR', Money::fromCents(123_456)->format());
        static::assertSame('1 234,56 EUR', (string) Money::fromCents(123_456));
    }
}
