<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Order\ValueObject;

use App\Domain\Order\ValueObject\OrderLineType;
use PHPUnit\Framework\TestCase;

final class OrderLineTypeTest extends TestCase
{
    public function testServiceLabel(): void
    {
        $this->assertSame('Service', OrderLineType::SERVICE->getLabel());
    }

    public function testPurchaseLabel(): void
    {
        $this->assertSame('Achat', OrderLineType::PURCHASE->getLabel());
    }

    public function testFixedAmountLabel(): void
    {
        $this->assertSame('Montant fixe', OrderLineType::FIXED_AMOUNT->getLabel());
    }

    public function testIsService(): void
    {
        $this->assertTrue(OrderLineType::SERVICE->isService());
        $this->assertFalse(OrderLineType::PURCHASE->isService());
        $this->assertFalse(OrderLineType::FIXED_AMOUNT->isService());
    }

    public function testIsPurchase(): void
    {
        $this->assertTrue(OrderLineType::PURCHASE->isPurchase());
        $this->assertFalse(OrderLineType::SERVICE->isPurchase());
    }

    public function testIsFixedAmount(): void
    {
        $this->assertTrue(OrderLineType::FIXED_AMOUNT->isFixedAmount());
        $this->assertFalse(OrderLineType::SERVICE->isFixedAmount());
    }

    public function testFromValueRoundtrip(): void
    {
        $this->assertSame(OrderLineType::SERVICE, OrderLineType::from('service'));
        $this->assertSame(OrderLineType::PURCHASE, OrderLineType::from('purchase'));
        $this->assertSame(OrderLineType::FIXED_AMOUNT, OrderLineType::from('fixed_amount'));
    }
}
