<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Order\Event;

use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Order\Event\OrderCreatedEvent;
use App\Domain\Order\ValueObject\OrderId;
use PHPUnit\Framework\TestCase;

final class OrderCreatedEventTest extends TestCase
{
    public function testCreate(): void
    {
        $event = OrderCreatedEvent::create(
            OrderId::fromLegacyInt(7),
            ClientId::fromLegacyInt(42),
            'D202601-001',
        );

        $this->assertSame(7, $event->getOrderId()->toLegacyInt());
        $this->assertSame(42, $event->getClientId()->toLegacyInt());
        $this->assertSame('D202601-001', $event->getReference());
        $this->assertNotNull($event->getOccurredOn());
    }
}
