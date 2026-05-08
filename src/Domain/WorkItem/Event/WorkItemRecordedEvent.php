<?php

declare(strict_types=1);

namespace App\Domain\WorkItem\Event;

use App\Domain\Contributor\ValueObject\ContributorId;
use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\Shared\Interface\DomainEventInterface;
use App\Domain\WorkItem\ValueObject\WorkItemId;
use DateTimeImmutable;

/**
 * Émis quand un WorkItem est créé dans le système (heures déclarées).
 *
 * Phase 3 sprint-022 : consommé par MarginCalculator pour recalculer la marge
 * du projet en temps réel + dispatch MarginThresholdExceededEvent si seuil
 * dépassé.
 *
 * @see ADR-0013 EPIC-003 scope WorkItem & Profitability
 */
final readonly class WorkItemRecordedEvent implements DomainEventInterface
{
    private function __construct(
        public WorkItemId $workItemId,
        public ProjectId $projectId,
        public ContributorId $contributorId,
        public DateTimeImmutable $workedOn,
        public DateTimeImmutable $occurredAt,
    ) {
    }

    public static function create(
        WorkItemId $workItemId,
        ProjectId $projectId,
        ContributorId $contributorId,
        DateTimeImmutable $workedOn,
    ): self {
        return new self(
            $workItemId,
            $projectId,
            $contributorId,
            $workedOn,
            new DateTimeImmutable(),
        );
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
