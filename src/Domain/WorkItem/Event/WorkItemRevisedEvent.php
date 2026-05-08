<?php

declare(strict_types=1);

namespace App\Domain\WorkItem\Event;

use App\Domain\Shared\Interface\DomainEventInterface;
use App\Domain\WorkItem\ValueObject\WorkedHours;
use App\Domain\WorkItem\ValueObject\WorkItemId;
use DateTimeImmutable;

/**
 * Émis quand un WorkItem est révisé (heures modifiées) — pour audit + recalcul
 * marge projet downstream Phase 3.
 *
 * Capture old/new hours pour traçabilité (Sentry contexte log).
 *
 * @see ADR-0013 EPIC-003 scope WorkItem & Profitability
 */
final readonly class WorkItemRevisedEvent implements DomainEventInterface
{
    private function __construct(
        public WorkItemId $workItemId,
        public WorkedHours $oldHours,
        public WorkedHours $newHours,
        public DateTimeImmutable $occurredAt,
    ) {
    }

    public static function create(
        WorkItemId $workItemId,
        WorkedHours $oldHours,
        WorkedHours $newHours,
    ): self {
        return new self($workItemId, $oldHours, $newHours, new DateTimeImmutable());
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
