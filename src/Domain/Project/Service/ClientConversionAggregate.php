<?php

declare(strict_types=1);

namespace App\Domain\Project\Service;

/**
 * Aggregate par client du KPI taux de conversion (US-119 T-119-01).
 *
 * Drill-down de {@see \App\Application\Project\Query\ConversionRateKpi\ConversionRateKpiDto}.
 * Domain pure — Repository hydrate cette structure depuis SQL + agrégation PHP.
 *
 * `ratePercent` = converted / (converted + failed) × 100 sur la fenêtre.
 * Standby / termine / a_signer exclus du dénominateur (cohérent calculator).
 */
final readonly class ClientConversionAggregate
{
    public function __construct(
        public string $clientName,
        public float $ratePercent,
        public int $emittedCount,
        public int $convertedCount,
    ) {
    }
}
