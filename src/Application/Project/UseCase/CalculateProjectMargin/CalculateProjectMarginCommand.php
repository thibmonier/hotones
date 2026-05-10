<?php

declare(strict_types=1);

namespace App\Application\Project\UseCase\CalculateProjectMargin;

/**
 * EPIC-003 Phase 3 (sprint-022 US-104) — Command pour orchestrer le calcul
 * de la marge d'un projet.
 *
 * Caller : handler async `RecalculateProjectMarginOnWorkItemRecorded`
 * (consume `WorkItemRecordedEvent` US-099 sprint-021) OR Controller direct
 * (recalcul on-demand).
 *
 * Threshold default 10 % (ADR-0016 Q5.2). Override via env var
 * `MARGIN_ALERT_THRESHOLD` côté caller.
 */
final readonly class CalculateProjectMarginCommand
{
    public function __construct(
        public string $projectIdLegacy,
        public float $thresholdPercent = 10.0,
    ) {
    }
}
