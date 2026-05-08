<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\WorkItem\Event;

use App\Domain\Contributor\ValueObject\ContributorId;
use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\WorkItem\Event\WorkItemRecordedEvent;
use App\Domain\WorkItem\ValueObject\WorkItemId;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class WorkItemRecordedEventTest extends TestCase
{
    public function testCreate(): void
    {
        $workItemId = WorkItemId::fromLegacyInt(7);
        $projectId = ProjectId::fromLegacyInt(11);
        $contributorId = ContributorId::fromLegacyInt(42);
        $workedOn = new DateTimeImmutable('2026-04-15');

        $event = WorkItemRecordedEvent::create($workItemId, $projectId, $contributorId, $workedOn);

        self::assertTrue($event->workItemId->equals($workItemId));
        self::assertTrue($event->projectId->equals($projectId));
        self::assertTrue($event->contributorId->equals($contributorId));
        self::assertEquals($workedOn, $event->workedOn);
        self::assertSame('legacy:7', $event->getAggregateId());
    }

    public function testOccurredOnRecorded(): void
    {
        $event = WorkItemRecordedEvent::create(
            WorkItemId::fromLegacyInt(1),
            ProjectId::fromLegacyInt(1),
            ContributorId::fromLegacyInt(1),
            new DateTimeImmutable(),
        );

        self::assertInstanceOf(DateTimeImmutable::class, $event->getOccurredOn());
    }
}
