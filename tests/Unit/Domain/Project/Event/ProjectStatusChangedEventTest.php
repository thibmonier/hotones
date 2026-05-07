<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Project\Event;

use App\Domain\Project\Event\ProjectStatusChangedEvent;
use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\Project\ValueObject\ProjectStatus;
use PHPUnit\Framework\TestCase;

final class ProjectStatusChangedEventTest extends TestCase
{
    public function testCreate(): void
    {
        $event = ProjectStatusChangedEvent::create(
            ProjectId::fromLegacyInt(7),
            ProjectStatus::DRAFT,
            ProjectStatus::ACTIVE,
        );

        $this->assertSame(7, $event->getProjectId()->toLegacyInt());
        $this->assertSame(ProjectStatus::DRAFT, $event->getPreviousStatus());
        $this->assertSame(ProjectStatus::ACTIVE, $event->getNewStatus());
        $this->assertNotNull($event->getOccurredOn());
    }
}
