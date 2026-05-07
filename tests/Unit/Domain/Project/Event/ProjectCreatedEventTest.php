<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Project\Event;

use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Project\Event\ProjectCreatedEvent;
use App\Domain\Project\ValueObject\ProjectId;
use PHPUnit\Framework\TestCase;

final class ProjectCreatedEventTest extends TestCase
{
    public function testCreate(): void
    {
        $event = ProjectCreatedEvent::create(
            ProjectId::fromLegacyInt(7),
            ClientId::fromLegacyInt(42),
            'My Project',
        );

        $this->assertSame(7, $event->getProjectId()->toLegacyInt());
        $this->assertSame(42, $event->getClientId()->toLegacyInt());
        $this->assertSame('My Project', $event->getName());
        $this->assertNotNull($event->getOccurredOn());
    }
}
