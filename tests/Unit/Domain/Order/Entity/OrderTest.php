<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Order\Entity;

use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Order\Entity\Order;
use App\Domain\Order\Event\OrderCreatedEvent;
use App\Domain\Order\Event\OrderStatusChangedEvent;
use App\Domain\Order\Exception\InvalidOrderStatusTransitionException;
use App\Domain\Order\ValueObject\ContractType;
use App\Domain\Order\ValueObject\OrderId;
use App\Domain\Order\ValueObject\OrderStatus;
use App\Domain\Shared\ValueObject\Money;
use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class OrderTest extends TestCase
{
    private function makeOrder(): Order
    {
        return Order::create(
            OrderId::generate(),
            'D202601-001',
            ClientId::generate(),
            ContractType::FIXED_PRICE,
            Money::fromAmount(10000),
        );
    }

    public function testCreateInitializesDefaults(): void
    {
        $order = $this->makeOrder();

        $this->assertSame('D202601-001', $order->getReference());
        $this->assertSame(OrderStatus::DRAFT, $order->getStatus());
        $this->assertSame(ContractType::FIXED_PRICE, $order->getContractType());
        $this->assertSame(10000.0, $order->getAmount()->getAmount());
        $this->assertNull($order->getTitle());
        $this->assertNull($order->getDiscount());
        $this->assertNotNull($order->getCreatedAt());
    }

    public function testCreateRecordsOrderCreatedEvent(): void
    {
        $order = $this->makeOrder();
        $events = $order->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(OrderCreatedEvent::class, $events[0]);
    }

    public function testTransitionDraftToToSign(): void
    {
        $order = $this->makeOrder();
        $order->pullDomainEvents();

        $order->changeStatus(OrderStatus::TO_SIGN);

        $this->assertSame(OrderStatus::TO_SIGN, $order->getStatus());
        $events = $order->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(OrderStatusChangedEvent::class, $events[0]);
    }

    public function testInvalidTransitionDraftToSigned(): void
    {
        $order = $this->makeOrder();

        $this->expectException(InvalidOrderStatusTransitionException::class);
        $order->changeStatus(OrderStatus::SIGNED);
    }

    public function testSignedSetsSignedAtTimestamp(): void
    {
        $order = $this->makeOrder();
        $order->changeStatus(OrderStatus::TO_SIGN);
        $order->changeStatus(OrderStatus::WON);
        $order->changeStatus(OrderStatus::SIGNED);

        $this->assertNotNull($order->getSignedAt());
    }

    public function testCannotUpdateAmountOnClosedOrder(): void
    {
        $order = $this->makeOrder();
        $order->changeStatus(OrderStatus::TO_SIGN);
        $order->changeStatus(OrderStatus::LOST);

        $this->expectException(DomainException::class);
        $order->updateAmount(Money::fromAmount(5000));
    }

    public function testUpdateAmountOnActiveOrder(): void
    {
        $order = $this->makeOrder();
        $order->updateAmount(Money::fromAmount(15000));

        $this->assertSame(15000.0, $order->getAmount()->getAmount());
    }

    public function testApplyDiscount(): void
    {
        $order = $this->makeOrder();
        $order->applyDiscount(Money::fromAmount(1000));

        $this->assertSame(1000.0, $order->getDiscount()->getAmount());
    }

    public function testNetAmountWithDiscount(): void
    {
        $order = $this->makeOrder();
        $order->applyDiscount(Money::fromAmount(1500));

        // Net = amount (10000) - discount (1500) = 8500
        $this->assertSame(8500.0, $order->getNetAmount()->getAmount());
    }

    public function testNetAmountWithoutDiscount(): void
    {
        $order = $this->makeOrder();

        $this->assertSame(10000.0, $order->getNetAmount()->getAmount());
    }

    public function testIsActiveAndIsClosed(): void
    {
        $order = $this->makeOrder();
        $this->assertTrue($order->isActive());
        $this->assertFalse($order->isClosed());

        $order->changeStatus(OrderStatus::TO_SIGN);
        $order->changeStatus(OrderStatus::LOST);

        $this->assertFalse($order->isActive());
        $this->assertTrue($order->isClosed());
    }

    public function testInvalidDateRangeRejected(): void
    {
        $order = $this->makeOrder();

        $this->expectException(InvalidArgumentException::class);
        $order->setDates(new DateTimeImmutable('2026-12-31'), new DateTimeImmutable('2026-01-01'));
    }
}
