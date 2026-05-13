<?php

declare(strict_types=1);

namespace App\Application\Project\Query\MarginAdoptionKpi;

use App\Domain\Project\Service\MarginAdoptionStats;

/**
 * Read-model DTO exposed to the business dashboard widget (US-112 T-112-03).
 *
 * Marge adoption stats + threshold flags.
 */
final readonly class MarginAdoptionKpiDto
{
    public function __construct(
        public MarginAdoptionStats $stats,
        public float $warningThresholdPercent,
        public bool $warningTriggered,
    ) {
    }
}
