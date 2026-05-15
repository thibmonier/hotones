<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Order\Event;

use App\Domain\Order\Event\OrderStatusChangedEvent;
use App\Domain\Order\ValueObject\OrderId;
use App\Domain\Order\ValueObject\OrderStatus;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class OrderStatusChangedEventTest extends TestCase
{
    public function testCreate(): void
    {
        $orderId = OrderId::fromLegacyInt(7);

        $event = new OrderStatusChangedEvent(
            $orderId,
            OrderStatus::DRAFT,
            OrderStatus::TO_SIGN,
            new DateTimeImmutable(),
        );

        static::assertSame(7, $event->getOrderId()->toLegacyInt());
        static::assertSame(OrderStatus::DRAFT, $event->getPreviousStatus());
        static::assertSame(OrderStatus::TO_SIGN, $event->getNewStatus());
        static::assertNotNull($event->getOccurredOn());
    }
}
