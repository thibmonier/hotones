<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Order\Entity;

use App\Domain\Order\Entity\OrderSection;
use App\Domain\Order\ValueObject\OrderLineId;
use App\Domain\Order\ValueObject\OrderLineType;
use App\Domain\Order\ValueObject\OrderSectionId;
use App\Domain\Shared\ValueObject\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * TEST-COVERAGE-008 (sprint-019) — coverage Domain OrderSection entity.
 */
final class OrderSectionTest extends TestCase
{
    public function testCreateInitializesAllFields(): void
    {
        $section = OrderSection::create(
            OrderSectionId::generate(),
            'Phase 1 — Design',
            position: 1,
        );

        static::assertSame('Phase 1 — Design', $section->getTitle());
        static::assertSame(1, $section->getPosition());
        static::assertSame([], $section->getLines());
        static::assertSame(0, $section->getLineCount());
        static::assertNull($section->getUpdatedAt());
    }

    public function testCreateEmptyTitleThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/title cannot be empty/');
        OrderSection::create(OrderSectionId::generate(), '   ', 1);
    }

    public function testAddLineGrowsLineCountAndStampsUpdatedAt(): void
    {
        $section = OrderSection::create(OrderSectionId::generate(), 'Section', 1);
        $section->addLine(
            OrderLineId::generate(),
            'Line A',
            OrderLineType::SERVICE,
            quantity: 2.0,
            unitPriceHt: Money::fromAmount(100.0),
            taxRate: 0.20,
        );

        static::assertSame(1, $section->getLineCount());
        static::assertCount(1, $section->getLines());
        static::assertNotNull($section->getUpdatedAt());
    }

    public function testAddMultipleLinesHaveIncrementalPositions(): void
    {
        $section = OrderSection::create(OrderSectionId::generate(), 'Section', 1);
        $section->addLine(OrderLineId::generate(), 'A', OrderLineType::SERVICE, 1.0, Money::fromAmount(10.0), 0.20);
        $section->addLine(OrderLineId::generate(), 'B', OrderLineType::SERVICE, 1.0, Money::fromAmount(20.0), 0.20);
        $section->addLine(OrderLineId::generate(), 'C', OrderLineType::SERVICE, 1.0, Money::fromAmount(30.0), 0.20);

        $lines = $section->getLines();
        static::assertSame(1, $lines[0]->getPosition());
        static::assertSame(2, $lines[1]->getPosition());
        static::assertSame(3, $lines[2]->getPosition());
    }

    public function testUpdateLineMutatesLine(): void
    {
        $section = OrderSection::create(OrderSectionId::generate(), 'Section', 1);
        $lineId = OrderLineId::generate();
        $section->addLine($lineId, 'Initial', OrderLineType::SERVICE, 1.0, Money::fromAmount(100.0), 0.20);

        $section->updateLine($lineId, 'Updated', OrderLineType::PURCHASE, 5.0, Money::fromAmount(50.0), 0.10);

        $line = $section->getLines()[0];
        static::assertSame('Updated', $line->getDescription());
        static::assertSame(OrderLineType::PURCHASE, $line->getType());
        static::assertSame(5.0, $line->getQuantity());
    }

    public function testUpdateLineUnknownIdThrows(): void
    {
        $section = OrderSection::create(OrderSectionId::generate(), 'Section', 1);
        $section->addLine(OrderLineId::generate(), 'A', OrderLineType::SERVICE, 1.0, Money::fromAmount(10.0), 0.20);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/not found in section/');
        $section->updateLine(OrderLineId::generate(), 'X', OrderLineType::SERVICE, 1.0, Money::fromAmount(1.0), 0.20);
    }

    public function testRemoveLineDecreasesCountAndReorders(): void
    {
        $section = OrderSection::create(OrderSectionId::generate(), 'Section', 1);
        $keepA = OrderLineId::generate();
        $remove = OrderLineId::generate();
        $keepC = OrderLineId::generate();
        $section->addLine($keepA, 'A', OrderLineType::SERVICE, 1.0, Money::fromAmount(10.0), 0.20);
        $section->addLine($remove, 'B', OrderLineType::SERVICE, 1.0, Money::fromAmount(20.0), 0.20);
        $section->addLine($keepC, 'C', OrderLineType::SERVICE, 1.0, Money::fromAmount(30.0), 0.20);

        $section->removeLine($remove);

        static::assertSame(2, $section->getLineCount());
        $lines = $section->getLines();
        static::assertSame('A', $lines[0]->getDescription());
        static::assertSame(1, $lines[0]->getPosition());
        static::assertSame('C', $lines[1]->getDescription());
        static::assertSame(2, $lines[1]->getPosition()); // reordered
    }

    public function testRemoveLineUnknownIdThrows(): void
    {
        $section = OrderSection::create(OrderSectionId::generate(), 'Section', 1);
        $this->expectException(InvalidArgumentException::class);
        $section->removeLine(OrderLineId::generate());
    }

    public function testUpdateTitleMutates(): void
    {
        $section = OrderSection::create(OrderSectionId::generate(), 'Old title', 1);
        $section->update('New title');
        static::assertSame('New title', $section->getTitle());
        static::assertNotNull($section->getUpdatedAt());
    }

    public function testUpdateTitleEmptyThrows(): void
    {
        $section = OrderSection::create(OrderSectionId::generate(), 'Title', 1);
        $this->expectException(InvalidArgumentException::class);
        $section->update('');
    }

    public function testUpdatePositionMutates(): void
    {
        $section = OrderSection::create(OrderSectionId::generate(), 'Section', 1);
        $section->updatePosition(5);
        static::assertSame(5, $section->getPosition());
    }

    public function testGetTotalHtSumsLines(): void
    {
        $section = OrderSection::create(OrderSectionId::generate(), 'Section', 1);
        $section->addLine(OrderLineId::generate(), 'A', OrderLineType::SERVICE, 1.0, Money::fromAmount(100.0), 0.20);
        $section->addLine(OrderLineId::generate(), 'B', OrderLineType::SERVICE, 2.0, Money::fromAmount(50.0), 0.20);

        // 100 + (2 × 50) = 200 EUR
        static::assertSame(20_000, $section->getTotalHt()->getAmountCents());
    }

    public function testGetTaxAmountSumsLines(): void
    {
        $section = OrderSection::create(OrderSectionId::generate(), 'Section', 1);
        $section->addLine(OrderLineId::generate(), 'A', OrderLineType::SERVICE, 1.0, Money::fromAmount(100.0), 0.20);
        $section->addLine(OrderLineId::generate(), 'B', OrderLineType::SERVICE, 1.0, Money::fromAmount(50.0), 0.10);

        // (100 × 0.20) + (50 × 0.10) = 25 EUR
        static::assertSame(2500, $section->getTaxAmount()->getAmountCents());
    }

    public function testGetTotalTtc(): void
    {
        $section = OrderSection::create(OrderSectionId::generate(), 'Section', 1);
        $section->addLine(OrderLineId::generate(), 'A', OrderLineType::SERVICE, 1.0, Money::fromAmount(100.0), 0.20);

        // HT 100 + TVA 20 = TTC 120
        static::assertSame(12_000, $section->getTotalTtc()->getAmountCents());
    }

    public function testEmptySectionTotalsAreZero(): void
    {
        $section = OrderSection::create(OrderSectionId::generate(), 'Empty', 1);
        static::assertSame(0, $section->getTotalHt()->getAmountCents());
        static::assertSame(0, $section->getTaxAmount()->getAmountCents());
        static::assertSame(0, $section->getTotalTtc()->getAmountCents());
    }
}
