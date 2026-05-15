<?php

declare(strict_types=1);

namespace App\Domain\Project\Service;

/**
 * Aggregate par client du KPI adoption marge (US-119 T-119-01).
 *
 * Drill-down de {@see \App\Application\Project\Query\MarginAdoptionKpi\MarginAdoptionKpiDto}.
 * Domain pure — Repository hydrate cette structure.
 *
 * `freshPercent` = projects classifiés `fresh` (≤ 30j) / totalActive × 100.
 * Projets sans snapshot (`marginCalculatedAt = null`) classés stale_critical.
 */
final readonly class ClientMarginAdoptionAggregate
{
    public function __construct(
        public string $clientName,
        public float $freshPercent,
        public int $totalActive,
        public int $freshCount,
        public int $staleCriticalCount,
    ) {
    }
}
