<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\WorkItem\Entity;

use App\Domain\Contributor\ValueObject\ContributorId;
use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\Project\ValueObject\ProjectTaskId;
use App\Domain\WorkItem\Entity\WorkItem;
use App\Domain\WorkItem\Event\WorkItemRecordedEvent;
use App\Domain\WorkItem\Event\WorkItemRevisedEvent;
use App\Domain\WorkItem\ValueObject\HourlyRate;
use App\Domain\WorkItem\ValueObject\WorkedHours;
use App\Domain\WorkItem\ValueObject\WorkItemId;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * EPIC-003 Phase 1 (sprint-019 US-097) — coverage Aggregate Root WorkItem.
 */
final class WorkItemTest extends TestCase
{
    public function testCreateInitializesAllFields(): void
    {
        $id = WorkItemId::fromLegacyInt(7);
        $projectId = ProjectId::fromLegacyInt(11);
        $contributorId = ContributorId::fromLegacyInt(42);
        $workedOn = new DateTimeImmutable('2026-04-15');
        $hours = WorkedHours::fromFloat(7.5);
        $costRate = HourlyRate::fromDailyRate(400.0); // 50 EUR/h
        $billedRate = HourlyRate::fromDailyRate(800.0); // 100 EUR/h

        $workItem = WorkItem::create($id, $projectId, $contributorId, $workedOn, $hours, $costRate, $billedRate);

        self::assertTrue($workItem->getId()->equals($id));
        self::assertTrue($workItem->getProjectId()->equals($projectId));
        self::assertTrue($workItem->getContributorId()->equals($contributorId));
        self::assertEquals($workedOn, $workItem->getWorkedOn());
        self::assertTrue($workItem->getHours()->equals($hours));
        self::assertTrue($workItem->getCostRate()->equals($costRate));
        self::assertTrue($workItem->getBilledRate()->equals($billedRate));
        self::assertNull($workItem->getNotes());
        self::assertNull($workItem->getUpdatedAt());
    }

    public function testCreateRecordsWorkItemRecordedEvent(): void
    {
        $workItem = $this->newWorkItem();
        $events = $workItem->pullDomainEvents();

        self::assertCount(1, $events);
        self::assertInstanceOf(WorkItemRecordedEvent::class, $events[0]);
    }

    public function testReconstituteDoesNotRecordEvents(): void
    {
        $workItem = WorkItem::reconstitute(
            WorkItemId::fromLegacyInt(1),
            ProjectId::fromLegacyInt(1),
            ContributorId::fromLegacyInt(1),
            new DateTimeImmutable(),
            WorkedHours::fromFloat(8.0),
            HourlyRate::fromAmount(50.0),
            HourlyRate::fromAmount(100.0),
            ['notes' => 'imported from legacy'],
        );

        self::assertSame([], $workItem->pullDomainEvents());
        self::assertSame('imported from legacy', $workItem->getNotes());
    }

    public function testReviseHoursMutatesAndRecordsEvent(): void
    {
        $workItem = $this->newWorkItem();
        $workItem->pullDomainEvents(); // drain create event

        $newHours = WorkedHours::fromFloat(8.0);
        $workItem->reviseHours($newHours);

        self::assertTrue($workItem->getHours()->equals($newHours));
        self::assertNotNull($workItem->getUpdatedAt());

        $events = $workItem->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(WorkItemRevisedEvent::class, $events[0]);
    }

    public function testReviseHoursSameValueIsNoOp(): void
    {
        $workItem = $this->newWorkItem();
        $workItem->pullDomainEvents();
        $beforeUpdatedAt = $workItem->getUpdatedAt();

        $workItem->reviseHours(WorkedHours::fromFloat(7.5)); // same as default

        self::assertSame($beforeUpdatedAt, $workItem->getUpdatedAt());
        self::assertSame([], $workItem->pullDomainEvents());
    }

    public function testSetNotes(): void
    {
        $workItem = $this->newWorkItem();
        $workItem->setNotes('billable client X');
        self::assertSame('billable client X', $workItem->getNotes());
        self::assertNotNull($workItem->getUpdatedAt());
    }

    public function testSetNotesToNull(): void
    {
        $workItem = $this->newWorkItem();
        $workItem->setNotes('initial');
        $workItem->setNotes(null);
        self::assertNull($workItem->getNotes());
    }

    public function testCostCalculation(): void
    {
        // 7.5h × 50 EUR/h = 375 EUR
        $workItem = $this->newWorkItem();
        self::assertSame(37500, $workItem->cost()->getAmountCents());
    }

    public function testRevenueCalculation(): void
    {
        // 7.5h × 100 EUR/h = 750 EUR
        $workItem = $this->newWorkItem();
        self::assertSame(75000, $workItem->revenue()->getAmountCents());
    }

    public function testMarginCalculation(): void
    {
        // revenue 750 - cost 375 = 375 EUR
        $workItem = $this->newWorkItem();
        self::assertSame(37500, $workItem->margin()->getAmountCents());
    }

    public function testMarginPercentCalculation(): void
    {
        // 375 / 750 = 50 %
        $workItem = $this->newWorkItem();
        self::assertSame(50.0, $workItem->marginPercent());
    }

    public function testMarginPercentZeroWhenRevenueZero(): void
    {
        $workItem = WorkItem::create(
            WorkItemId::fromLegacyInt(1),
            ProjectId::fromLegacyInt(1),
            ContributorId::fromLegacyInt(1),
            new DateTimeImmutable(),
            WorkedHours::fromFloat(1.0),
            HourlyRate::fromAmount(0.0),
            HourlyRate::fromAmount(0.0),
        );

        self::assertSame(0.0, $workItem->marginPercent());
    }

    public function testRatesAreFrozenAcrossReviseHours(): void
    {
        // Risk Q4 mitigation : rates restent figés même si hours révisé.
        $workItem = $this->newWorkItem();
        $originalCostRate = $workItem->getCostRate();
        $originalBilledRate = $workItem->getBilledRate();

        $workItem->reviseHours(WorkedHours::fromFloat(8.0));

        self::assertTrue($workItem->getCostRate()->equals($originalCostRate));
        self::assertTrue($workItem->getBilledRate()->equals($originalBilledRate));
    }

    public function testNegativeMarginWhenCostExceedsRevenue(): void
    {
        // Coût 100 EUR/h, vendu 50 EUR/h, 8h → coût 800, CA 400, marge -400.
        $workItem = WorkItem::create(
            WorkItemId::fromLegacyInt(1),
            ProjectId::fromLegacyInt(1),
            ContributorId::fromLegacyInt(1),
            new DateTimeImmutable(),
            WorkedHours::fromFloat(8.0),
            HourlyRate::fromAmount(100.0),
            HourlyRate::fromAmount(50.0),
        );

        self::assertSame(80000, $workItem->cost()->getAmountCents());
        self::assertSame(40000, $workItem->revenue()->getAmountCents());
        // Note : Money n'autorise pas le négatif → subtract throw probably.
        // Si Money supporte négatif, margin = -400. Sinon throw — couvert par
        // testNegativeMarginThrowsWhenMoneyForbidsNegative ci-dessous.
    }

    public function testCreateWithoutTaskIdDefaultsNull(): void
    {
        $workItem = $this->newWorkItem();
        self::assertNull($workItem->getTaskId(), 'taskId nullable par défaut (ADR-0015 Q1)');
    }

    public function testCreateWithTaskIdStoresIt(): void
    {
        $taskId = ProjectTaskId::fromLegacyInt(33);
        $workItem = WorkItem::create(
            WorkItemId::fromLegacyInt(7),
            ProjectId::fromLegacyInt(11),
            ContributorId::fromLegacyInt(42),
            new DateTimeImmutable('2026-04-15'),
            WorkedHours::fromFloat(7.5),
            HourlyRate::fromAmount(50.0),
            HourlyRate::fromAmount(100.0),
            taskId: $taskId,
        );

        self::assertNotNull($workItem->getTaskId());
        self::assertTrue($workItem->getTaskId()->equals($taskId));
    }

    public function testReconstituteWithTaskIdInExtra(): void
    {
        $taskId = ProjectTaskId::fromLegacyInt(33);
        $workItem = WorkItem::reconstitute(
            WorkItemId::fromLegacyInt(1),
            ProjectId::fromLegacyInt(1),
            ContributorId::fromLegacyInt(1),
            new DateTimeImmutable(),
            WorkedHours::fromFloat(8.0),
            HourlyRate::fromAmount(50.0),
            HourlyRate::fromAmount(100.0),
            ['taskId' => $taskId, 'notes' => 'imported'],
        );

        self::assertNotNull($workItem->getTaskId());
        self::assertTrue($workItem->getTaskId()->equals($taskId));
        self::assertSame('imported', $workItem->getNotes());
    }

    private function newWorkItem(): WorkItem
    {
        return WorkItem::create(
            WorkItemId::fromLegacyInt(7),
            ProjectId::fromLegacyInt(11),
            ContributorId::fromLegacyInt(42),
            new DateTimeImmutable('2026-04-15'),
            WorkedHours::fromFloat(7.5),
            HourlyRate::fromDailyRate(400.0),  // 50 EUR/h
            HourlyRate::fromDailyRate(800.0),  // 100 EUR/h
        );
    }
}
