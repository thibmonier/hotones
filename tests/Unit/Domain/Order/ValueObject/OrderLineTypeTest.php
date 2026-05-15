<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Order\ValueObject;

use App\Domain\Order\ValueObject\OrderLineType;
use PHPUnit\Framework\TestCase;

final class OrderLineTypeTest extends TestCase
{
    public function testServiceLabel(): void
    {
        static::assertSame('Service', OrderLineType::SERVICE->getLabel());
    }

    public function testPurchaseLabel(): void
    {
        static::assertSame('Achat', OrderLineType::PURCHASE->getLabel());
    }

    public function testFixedAmountLabel(): void
    {
        static::assertSame('Montant fixe', OrderLineType::FIXED_AMOUNT->getLabel());
    }

    public function testIsService(): void
    {
        static::assertTrue(OrderLineType::SERVICE->isService());
        static::assertFalse(OrderLineType::PURCHASE->isService());
        static::assertFalse(OrderLineType::FIXED_AMOUNT->isService());
    }

    public function testIsPurchase(): void
    {
        static::assertTrue(OrderLineType::PURCHASE->isPurchase());
        static::assertFalse(OrderLineType::SERVICE->isPurchase());
    }

    public function testIsFixedAmount(): void
    {
        static::assertTrue(OrderLineType::FIXED_AMOUNT->isFixedAmount());
        static::assertFalse(OrderLineType::SERVICE->isFixedAmount());
    }

    public function testFromValueRoundtrip(): void
    {
        static::assertSame(OrderLineType::SERVICE, OrderLineType::from('service'));
        static::assertSame(OrderLineType::PURCHASE, OrderLineType::from('purchase'));
        static::assertSame(OrderLineType::FIXED_AMOUNT, OrderLineType::from('fixed_amount'));
    }
}
