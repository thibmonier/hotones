<?php

declare(strict_types=1);

namespace App\Domain\Project\Service;

/**
 * Aggregated margin adoption stats (US-112).
 *
 * - `freshCount`         : projets margin_calculated_at < 7 j
 * - `staleWarningCount`  : 7 ≤ margin_calculated_at < 30 j
 * - `staleCriticalCount` : margin_calculated_at NULL ou > 30 j
 * - `totalActive`        : nombre total de projets actifs considérés
 * - `freshPercent`       : freshCount / totalActive × 100 (0 si totalActive=0)
 */
final readonly class MarginAdoptionStats
{
    public function __construct(
        public int $freshCount,
        public int $staleWarningCount,
        public int $staleCriticalCount,
        public int $totalActive,
        public float $freshPercent,
    ) {
    }

    public static function empty(): self
    {
        return new self(0, 0, 0, 0, 0.0);
    }
}
