<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Client\Event;

use App\Domain\Client\Event\ClientCreatedEvent;
use App\Domain\Client\ValueObject\ClientId;
use PHPUnit\Framework\TestCase;

final class ClientCreatedEventTest extends TestCase
{
    public function testCreate(): void
    {
        $id = ClientId::fromLegacyInt(42);
        $event = ClientCreatedEvent::create($id, 'Acme Corp');

        $this->assertSame(42, $event->getClientId()->toLegacyInt());
        $this->assertNotNull($event->getOccurredOn());
    }
}
