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

        self::assertSame('Web design service', $line->getDescription());
        self::assertSame(OrderLineType::SERVICE, $line->getType());
        self::assertSame(2.0, $line->getQuantity());
        self::assertSame(10000, $line->getUnitPriceHt()->getAmountCents());
        self::assertSame(0.20, $line->getTaxRate());
        self::assertSame(1, $line->getPosition());
        self::assertNull($line->getUnit());
        self::assertNull($line->getUpdatedAt());
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

        self::assertSame(0.0, $line->getTaxRate());
        self::assertSame(0, $line->getTaxAmount()->getAmountCents());
    }

    public function testTotalHtCalculation(): void
    {
        // 2 × 100 = 200 EUR
        $line = $this->newServiceLine();
        self::assertSame(20000, $line->getTotalHt()->getAmountCents());
    }

    public function testTaxAmountCalculation(): void
    {
        // 200 × 20 % = 40 EUR
        $line = $this->newServiceLine();
        self::assertSame(4000, $line->getTaxAmount()->getAmountCents());
    }

    public function testTotalTtcCalculation(): void
    {
        // 200 + 40 = 240 EUR
        $line = $this->newServiceLine();
        self::assertSame(24000, $line->getTotalTtc()->getAmountCents());
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

        self::assertSame('Updated description', $line->getDescription());
        self::assertSame(OrderLineType::PURCHASE, $line->getType());
        self::assertSame(5.0, $line->getQuantity());
        self::assertSame(2000, $line->getUnitPriceHt()->getAmountCents());
        self::assertSame(0.10, $line->getTaxRate());
        self::assertNotNull($line->getUpdatedAt());
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

        self::assertSame('hour', $line->getUnit());
        self::assertNotNull($line->getUpdatedAt());
    }

    public function testSetUnitToNull(): void
    {
        $line = $this->newServiceLine();
        $line->setUnit('day');
        $line->setUnit(null);

        self::assertNull($line->getUnit());
    }

    public function testUpdatePositionMutates(): void
    {
        $line = $this->newServiceLine();
        $line->updatePosition(5);

        self::assertSame(5, $line->getPosition());
        self::assertNotNull($line->getUpdatedAt());
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
