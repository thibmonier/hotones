<?php

declare(strict_types=1);

namespace App\Application\Project\Query\DsoKpi;

/**
 * Read-model DTO exposed to the business dashboard widget (US-110 T-110-04).
 *
 * Rolling window DSO values + trend indicator + warning flag.
 */
final readonly class DsoKpiDto
{
    public function __construct(
        public float $dso30Days,
        public float $dso90Days,
        public float $dso365Days,
        public DsoTrend $trend30,
        public float $warningThresholdDays,
        public bool $warningTriggered,
    ) {
    }
}
