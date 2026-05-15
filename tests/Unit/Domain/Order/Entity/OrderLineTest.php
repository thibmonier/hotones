<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Order\Entity;

use App\Domain\Order\Entity\OrderLine;
use App\Domain\Order\ValueObject\OrderLineId;
use App\Domain\Order\ValueObject\OrderLineType;
use App\Domain\Shared\ValueObject\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * TEST-COVERAGE-008 (sprint-019) — coverage Domain OrderLine entity.
 */
final class OrderLineTest extends TestCase
{
    public function testCreateInitializesAllFields(): void
    {
        $line = OrderLine::create(
            OrderLineId::generate(),
            'Web design service',
            OrderLineType::SERVICE,
            quantity: 2.0,
            unitPriceHt: Money::fromAmount(100.0),
            taxRate: 0.20,
            position: 1,
        );

        static::assertSame('Web design service', $line->getDescription());
        static::assertSame(OrderLineType::SERVICE, $line->getType());
        static::assertSame(2.0, $line->getQuantity());
        static::assertSame(10_000, $line->getUnitPriceHt()->getAmountCents());
        static::assertSame(0.20, $line->getTaxRate());
        static::assertSame(1, $line->getPosition());
        static::assertNull($line->getUnit());
        static::assertNull($line->getUpdatedAt());
    }

    public function testCreateEmptyDescriptionThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/description cannot be empty/');
        OrderLine::create(
            OrderLineId::generate(),
            '   ',
            OrderLineType::SERVICE,
            1.0,
            Money::fromAmount(10.0),
            0.20,
            1,
        );
    }

    public function testCreateZeroQuantityThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/quantity must be positive/');
        OrderLine::create(
            OrderLineId::generate(),
            'X',
            OrderLineType::SERVICE,
            0.0,
            Money::fromAmount(10.0),
            0.20,
            1,
        );
    }

    public function testCreateNegativeQuantityThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        OrderLine::create(
            OrderLineId::generate(),
            'X',
            OrderLineType::SERVICE,
            -1.0,
            Money::fromAmount(10.0),
            0.20,
            1,
        );
    }

    public function testCreateTaxRateAboveOneThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Tax rate must be between/');
        OrderLine::create(
            OrderLineId::generate(),
            'X',
            OrderLineType::SERVICE,
            1.0,
            Money::fromAmount(10.0),
            1.5,
            1,
        );
    }

    public function testCreateNegativeTaxRateThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        OrderLine::create(
            OrderLineId::generate(),
            'X',
            OrderLineType::SERVICE,
            1.0,
            Money::fromAmount(10.0),
            -0.05,
            1,
        );
    }

    public function testCreateWithZeroTaxRateAccepted(): void
    {
        $line = OrderLine::create(
            OrderLineId::generate(),
            'Tax-free',
            OrderLineType::PURCHASE,
            1.0,
            Money::fromAmount(50.0),
            taxRate: 0.0,
            position: 1,
        );

        static::assertSame(0.0, $line->getTaxRate());
        static::assertSame(0, $line->getTaxAmount()->getAmountCents());
    }

    public function testTotalHtCalculation(): void
    {
        // 2 × 100 = 200 EUR
        $line = $this->newServiceLine();
        static::assertSame(20_000, $line->getTotalHt()->getAmountCents());
    }

    public function testTaxAmountCalculation(): void
    {
        // 200 × 20 % = 40 EUR
        $line = $this->newServiceLine();
        static::assertSame(4000, $line->getTaxAmount()->getAmountCents());
    }

    public function testTotalTtcCalculation(): void
    {
        // 200 + 40 = 240 EUR
        $line = $this->newServiceLine();
        static::assertSame(24_000, $line->getTotalTtc()->getAmountCents());
    }

    public function testUpdateMutatesAndStampsUpdatedAt(): void
    {
        $line = $this->newServiceLine();
        $line->update(
            'Updated description',
            OrderLineType::PURCHASE,
            quantity: 5.0,
            unitPriceHt: Money::fromAmount(20.0),
            taxRate: 0.10,
        );

        static::assertSame('Updated description', $line->getDescription());
        static::assertSame(OrderLineType::PURCHASE, $line->getType());
        static::assertSame(5.0, $line->getQuantity());
        static::assertSame(2000, $line->getUnitPriceHt()->getAmountCents());
        static::assertSame(0.10, $line->getTaxRate());
        static::assertNotNull($line->getUpdatedAt());
    }

    public function testUpdateRejectsInvalidDescription(): void
    {
        $line = $this->newServiceLine();

        $this->expectException(InvalidArgumentException::class);
        $line->update('', OrderLineType::SERVICE, 1.0, Money::fromAmount(10.0), 0.20);
    }

    public function testSetUnit(): void
    {
        $line = $this->newServiceLine();
        $line->setUnit('hour');

        static::assertSame('hour', $line->getUnit());
        static::assertNotNull($line->getUpdatedAt());
    }

    public function testSetUnitToNull(): void
    {
        $line = $this->newServiceLine();
        $line->setUnit('day');
        $line->setUnit(null);

        static::assertNull($line->getUnit());
    }

    public function testUpdatePositionMutates(): void
    {
        $line = $this->newServiceLine();
        $line->updatePosition(5);

        static::assertSame(5, $line->getPosition());
        static::assertNotNull($line->getUpdatedAt());
    }

    private function newServiceLine(): OrderLine
    {
        return OrderLine::create(
            OrderLineId::generate(),
            'Default service',
            OrderLineType::SERVICE,
            quantity: 2.0,
            unitPriceHt: Money::fromAmount(100.0),
            taxRate: 0.20,
            position: 1,
        );
    }
}
