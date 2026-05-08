<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\WorkItem\Event;

use App\Domain\WorkItem\Event\WorkItemRevisedEvent;
use App\Domain\WorkItem\ValueObject\WorkedHours;
use App\Domain\WorkItem\ValueObject\WorkItemId;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class WorkItemRevisedEventTest extends TestCase
{
    public function testCreateCapturesOldAndNewHours(): void
    {
        $workItemId = WorkItemId::fromLegacyInt(7);
        $oldHours = WorkedHours::fromFloat(7.5);
        $newHours = WorkedHours::fromFloat(8.0);

        $event = WorkItemRevisedEvent::create($workItemId, $oldHours, $newHours);

        self::assertTrue($event->workItemId->equals($workItemId));
        self::assertTrue($event->oldHours->equals($oldHours));
        self::assertTrue($event->newHours->equals($newHours));
        self::assertSame('legacy:7', $event->getAggregateId());
    }

    public function testOccurredOnRecorded(): void
    {
        $event = WorkItemRevisedEvent::create(
            WorkItemId::fromLegacyInt(1),
            WorkedHours::fromFloat(1.0),
            WorkedHours::fromFloat(2.0),
        );

        self::assertInstanceOf(DateTimeImmutable::class, $event->getOccurredOn());
    }
}
