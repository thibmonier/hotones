<?php

declare(strict_types=1);

namespace App\Domain\WorkItem\Event;

use App\Domain\Shared\Interface\DomainEventInterface;
use App\Domain\WorkItem\ValueObject\WorkItemId;
use DateTimeImmutable;

/**
 * EPIC-003 Phase 3 (sprint-021 US-099 + US-101) — émis quand WorkItem
 * passe transition workflow `validate` (draft → validated).
 *
 * Déclenché :
 * - US-099 UC RecordWorkItem si author has ROLE_MANAGER ou ROLE_ADMIN (Q3.2)
 * - US-101 manager déclenche manuellement validate sur WorkItem draft
 */
final readonly class WorkItemValidatedEvent implements DomainEventInterface
{
    private function __construct(
        public WorkItemId $workItemId,
        public DateTimeImmutable $occurredAt,
    ) {
    }

    public static function create(WorkItemId $workItemId): self
    {
        return new self($workItemId, new DateTimeImmutable());
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
