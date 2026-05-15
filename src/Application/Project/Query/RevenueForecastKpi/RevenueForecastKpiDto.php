<?php

declare(strict_types=1);

namespace App\Application\Project\Query\RevenueForecastKpi;

/**
 * DTO read-model du KPI Revenue forecast (US-114 T-114-04).
 */
final readonly class RevenueForecastKpiDto
{
    public function __construct(
        public float $forecast30Euros,
        public float $forecast90Euros,
        public float $confirmedEuros,
        public float $weightedQuotesEuros,
        public float $probabilityCoefficient,
        public float $warningThresholdEuros,
        public bool $warningTriggered,
    ) {
    }
}
