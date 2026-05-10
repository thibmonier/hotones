<?php

declare(strict_types=1);

namespace App\Domain\WorkItem\Event;

use App\Domain\Invoice\ValueObject\InvoiceId;
use App\Domain\Shared\Interface\DomainEventInterface;
use App\Domain\WorkItem\ValueObject\WorkItemId;
use DateTimeImmutable;

/**
 * EPIC-003 Phase 3 (sprint-021 US-101) — émis quand WorkItem passe
 * transition workflow `mark_paid` (billed → paid).
 *
 * Déclenchement automatique cross-aggregate via `InvoicePaidEvent` listener
 * = sprint-022+ (sprint-021 US-101 livre la transition Domain mais le
 * listener `MarkRelatedWorkItemsAsPaidOnInvoicePaid` est différé : nécessite
 * design tracking association Invoice ↔ WorkItem persistente).
 */
final readonly class WorkItemPaidEvent implements DomainEventInterface
{
    private function __construct(
        public WorkItemId $workItemId,
        public InvoiceId $invoiceId,
        public DateTimeImmutable $occurredAt,
    ) {
    }

    public static function create(WorkItemId $workItemId, InvoiceId $invoiceId): self
    {
        return new self($workItemId, $invoiceId, new DateTimeImmutable());
    }

    public function getOccurredOn(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function getAggregateId(): string
    {
        return $this->workItemId->getValue();
    }
}
