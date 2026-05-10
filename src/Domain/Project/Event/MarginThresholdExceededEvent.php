<?php

declare(strict_types=1);

namespace App\Domain\Project\Event;

use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\Shared\Interface\DomainEventInterface;
use App\Domain\Shared\ValueObject\Money;
use DateTimeImmutable;

/**
 * EPIC-003 Phase 3 (sprint-021 US-103) — Domain Event émis quand la marge
 * d'un projet passe sous le seuil configuré (default 10 % via env var
 * `MARGIN_ALERT_THRESHOLD`).
 *
 * Pure Domain (pas de coupling Application/Infrastructure). Handler Application
 * Layer `SendMarginAlertOnThresholdExceeded` consume + dispatch alerte Slack
 * via `SlackAlertingService` (US-094 sprint-017).
 *
 * Coexiste avec legacy `App\Event\LowMarginAlertEvent` (AT-3.3 ADR-0016 strangler
 * fig — refactor `AlertDetectionService` sprint-022+ marquera legacy
 * `@deprecated` puis suppression).
 */
final readonly class MarginThresholdExceededEvent implements DomainEventInterface
{
    private DateTimeImmutable $occurredOn;

    public function __construct(
        public ProjectId $projectId,
        public string $projectName,
        public Money $costTotal,
        public Money $invoicedPaidTotal,
        public float $marginPercent,
        public float $thresholdPercent,
    ) {
        $this->occurredOn = new DateTimeImmutable();
    }

    public static function create(
        ProjectId $projectId,
        string $projectName,
        Money $costTotal,
        Money $invoicedPaidTotal,
        float $marginPercent,
        float $thresholdPercent,
    ): self {
        return new self($projectId, $projectName, $costTotal, $invoicedPaidTotal, $marginPercent, $thresholdPercent);
    }

    public function getOccurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function getAggregateId(): string
    {
        return $this->projectId->value();
    }

    public function isCritical(): bool
    {
        return $this->marginPercent < ($this->thresholdPercent / 2.0);
    }
}
