<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Invoice\Entity;

use App\Domain\Invoice\Entity\InvoiceLine;
use App\Domain\Invoice\ValueObject\InvoiceLineId;
use App\Domain\Invoice\ValueObject\TaxRate;
use App\Domain\Shared\ValueObject\Money;
use PHPUnit\Framework\TestCase;

final class InvoiceLineTest extends TestCase
{
    public function testCreateInitializesFields(): void
    {
        $line = $this->makeLine();

        self::assertSame('Consultation', $line->getDescription());
        self::assertSame(10.0, $line->getQuantity());
    }

    public function testGetTotalHtIsQuantityTimesUnitPrice(): void
    {
        $line = InvoiceLine::create(
            id: InvoiceLineId::generate(),
            description: 'Days',
            quantity: 5.0,
            unitPriceHt: Money::fromAmount(800.00),
            taxRate: TaxRate::standardFrance(),
            position: 1,
        );

        // 5 × 800 = 4000 €
        self::assertSame(4000.0, $line->getTotalHt()->getAmount());
    }

    public function testGetTaxAmountAppliesTaxRate(): void
    {
        $line = InvoiceLine::create(
            id: InvoiceLineId::generate(),
            description: 'Days',
            quantity: 1.0,
            unitPriceHt: Money::fromAmount(100.00),
            taxRate: TaxRate::fromPercentage(20.0),
            position: 1,
        );

        // 100 × 20 % = 20 €
        self::assertSame(20.0, $line->getTaxAmount()->getAmount());
    }

    public function testGetTotalTtcIsHtPlusTax(): void
    {
        $line = InvoiceLine::create(
            id: InvoiceLineId::generate(),
            description: 'Days',
            quantity: 1.0,
            unitPriceHt: Money::fromAmount(100.00),
            taxRate: TaxRate::fromPercentage(20.0),
            position: 1,
        );

        // 100 + 20 = 120 €
        self::assertSame(120.0, $line->getTotalTtc()->getAmount());
    }

    public function testUpdateChangesFields(): void
    {
        $line = $this->makeLine();

        $line->update('New description', 20.0, Money::fromAmount(50.00), TaxRate::standardFrance());

        self::assertSame('New description', $line->getDescription());
        self::assertSame(20.0, $line->getQuantity());
    }

    public function testUpdatePositionChangesPosition(): void
    {
        $line = $this->makeLine();

        $line->updatePosition(5);

        self::assertSame(5, $line->getPosition());
    }

    public function testSetUnitAcceptsNullable(): void
    {
        $line = $this->makeLine();

        $line->setUnit('hours');
        self::assertSame('hours', $line->getUnit());

        $line->setUnit(null);
        self::assertNull($line->getUnit());
    }

    private function makeLine(): InvoiceLine
    {
        return InvoiceLine::create(
            id: InvoiceLineId::generate(),
            description: 'Consultation',
            quantity: 10.0,
            unitPriceHt: Money::fromAmount(100.00),
            taxRate: TaxRate::standardFrance(),
            position: 1,
        );
    }
}
