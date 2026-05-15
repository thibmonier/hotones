<?php

declare(strict_types=1);

namespace App\Application\Project\Query\ConversionRateKpi;

use App\Domain\Project\ValueObject\ConversionTrend;

/**
 * DTO read-model du KPI Taux de conversion (US-115 T-115-04).
 */
final readonly class ConversionRateKpiDto
{
    public function __construct(
        public float $rate30Percent,
        public float $rate90Percent,
        public float $rate365Percent,
        public int $emitted30Count,
        public int $converted30Count,
        public ConversionTrend $trend30,
        public float $warningThresholdPercent,
        public bool $warningTriggered,
    ) {
    }
}
