<?php

declare(strict_types=1);

namespace App\Domain\WorkItem\Event;

use App\Domain\Invoice\ValueObject\InvoiceId;
use App\Domain\Shared\Interface\DomainEventInterface;
use App\Domain\WorkItem\ValueObject\WorkItemId;
use DateTimeImmutable;

/**
 * EPIC-003 Phase 3 (sprint-021 US-101) — émis quand WorkItem passe
 * transition workflow `bill` (validated → billed).
 *
 * Déclenché cross-aggregate par listener `BillRelatedWorkItemsOnInvoiceCreated`
 * consume `InvoiceCreatedEvent` avec workItemIds payload (AT-3.2 ADR-0016).
 */
final readonly class WorkItemBilledEvent implements DomainEventInterface
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
