<?php

declare(strict_types=1);

namespace App\Domain\Project\Event;

use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\Shared\Interface\DomainEventInterface;
use App\Domain\Shared\ValueObject\Money;
use DateTimeImmutable;

/**
 * EPIC-003 Phase 5 (sprint-026 US-117 T-117-03) — Domain Event émis à
 * chaque recalcul réussi de la marge d'un projet (snapshot persisté).
 *
 * Distinct de {@see MarginThresholdExceededEvent} qui ne se déclenche que
 * sous seuil. Cet event-ci est systématique : sert à invalider les caches
 * portefeuille (vue agrégée) dès qu'un snapshot projet change.
 *
 * Pure Domain — handlers Application Layer (cache invalidation, Slack
 * alerting portefeuille T-117-05).
 */
final readonly class ProjectMarginRecalculatedEvent implements DomainEventInterface
{
    private DateTimeImmutable $occurredOn;

    public function __construct(
        public ProjectId $projectId,
        public string $projectName,
        public Money $costTotal,
        public Money $invoicedPaidTotal,
        public ?float $marginPercent,
    ) {
        $this->occurredOn = new DateTimeImmutable();
    }

    public static function create(
        ProjectId $projectId,
        string $projectName,
        Money $costTotal,
        Money $invoicedPaidTotal,
        ?float $marginPercent,
    ): self {
        return new self($projectId, $projectName, $costTotal, $invoicedPaidTotal, $marginPercent);
    }

    public function getOccurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function getAggregateId(): string
    {
        return $this->projectId->value();
    }
}
