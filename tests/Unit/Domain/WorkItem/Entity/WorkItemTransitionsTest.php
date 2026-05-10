<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\WorkItem\Entity;

use App\Domain\Contributor\ValueObject\ContributorId;
use App\Domain\Invoice\ValueObject\InvoiceId;
use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\WorkItem\Entity\WorkItem;
use App\Domain\WorkItem\Event\WorkItemBilledEvent;
use App\Domain\WorkItem\Event\WorkItemPaidEvent;
use App\Domain\WorkItem\Event\WorkItemValidatedEvent;
use App\Domain\WorkItem\Exception\WorkItemInvalidTransitionException;
use App\Domain\WorkItem\ValueObject\HourlyRate;
use App\Domain\WorkItem\ValueObject\WorkedHours;
use App\Domain\WorkItem\ValueObject\WorkItemId;
use App\Domain\WorkItem\ValueObject\WorkItemStatus;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class WorkItemTransitionsTest extends TestCase
{
    public function testCreateInitiallyDraft(): void
    {
        $workItem = $this->makeDraft();

        self::assertSame(WorkItemStatus::DRAFT, $workItem->getStatus());
    }

    public function testMarkAsValidatedFromDraft(): void
    {
        $workItem = $this->makeDraft();
        $workItem->pullDomainEvents(); // discard create event

        $workItem->markAsValidated();

        self::assertSame(WorkItemStatus::VALIDATED, $workItem->getStatus());

        $events = $workItem->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(WorkItemValidatedEvent::class, $events[0]);
    }

    public function testMarkAsValidatedIdempotent(): void
    {
        $workItem = $this->makeDraft();
        $workItem->markAsValidated();
        $workItem->pullDomainEvents();

        $workItem->markAsValidated();

        self::assertSame(WorkItemStatus::VALIDATED, $workItem->getStatus());
        self::assertSame([], $workItem->pullDomainEvents());
    }

    public function testMarkAsBilledFromValidated(): void
    {
        $workItem = $this->makeDraft();
        $workItem->markAsValidated();
        $workItem->pullDomainEvents();

        $invoiceId = InvoiceId::fromLegacyInt(7);
        $workItem->markAsBilled($invoiceId);

        self::assertSame(WorkItemStatus::BILLED, $workItem->getStatus());

        $events = $workItem->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(WorkItemBilledEvent::class, $events[0]);
    }

    public function testMarkAsBilledFromDraftThrows(): void
    {
        $workItem = $this->makeDraft();

        $this->expectException(WorkItemInvalidTransitionException::class);
        $workItem->markAsBilled(InvoiceId::fromLegacyInt(7));
    }

    public function testMarkAsBilledIdempotent(): void
    {
        $workItem = $this->makeDraft();
        $workItem->markAsValidated();
        $workItem->markAsBilled(InvoiceId::fromLegacyInt(7));
        $workItem->pullDomainEvents();

        $workItem->markAsBilled(InvoiceId::fromLegacyInt(7));

        self::assertSame(WorkItemStatus::BILLED, $workItem->getStatus());
        self::assertSame([], $workItem->pullDomainEvents());
    }

    public function testMarkAsPaidFromBilled(): void
    {
        $workItem = $this->makeDraft();
        $workItem->markAsValidated();
        $workItem->markAsBilled(InvoiceId::fromLegacyInt(7));
        $workItem->pullDomainEvents();

        $workItem->markAsPaid(InvoiceId::fromLegacyInt(7));

        self::assertSame(WorkItemStatus::PAID, $workItem->getStatus());

        $events = $workItem->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(WorkItemPaidEvent::class, $events[0]);
    }

    public function testMarkAsPaidFromDraftThrows(): void
    {
        $workItem = $this->makeDraft();

        $this->expectException(WorkItemInvalidTransitionException::class);
        $workItem->markAsPaid(InvoiceId::fromLegacyInt(7));
    }

    public function testMarkAsPaidFromValidatedThrows(): void
    {
        $workItem = $this->makeDraft();
        $workItem->markAsValidated();

        $this->expectException(WorkItemInvalidTransitionException::class);
        $workItem->markAsPaid(InvoiceId::fromLegacyInt(7));
    }

    public function testReverseTransitionValidatedBackToDraftThrows(): void
    {
        $workItem = $this->makeDraft();
        $workItem->markAsValidated();

        // Pas de méthode markAsDraft (intentionnel — pas de retour arrière).
        // Vérifier que canTransitionTo refuse.
        self::assertFalse($workItem->getStatus()->canTransitionTo(WorkItemStatus::DRAFT));
    }

    private function makeDraft(): WorkItem
    {
        return WorkItem::create(
            id: WorkItemId::generate(),
            projectId: ProjectId::generate(),
            contributorId: ContributorId::fromLegacyInt(42),
            workedOn: new DateTimeImmutable('2026-05-12'),
            hours: WorkedHours::fromFloat(7.0),
            costRate: HourlyRate::fromAmount(50.0),
            billedRate: HourlyRate::fromAmount(100.0),
        );
    }
}
