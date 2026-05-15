<?php

declare(strict_types=1);

namespace App\Domain\Project\Service;

/**
 * Aggregate par client du KPI DSO (US-116 T-116-01).
 *
 * Drill-down de {@see \App\Application\Project\Query\DsoKpi\DsoKpiDto}.
 * Domain pure — Repository hydrate cette structure depuis SQL.
 */
final readonly class ClientDsoAggregate
{
    public function __construct(
        public string $clientName,
        public float $dsoAverageDays,
        public int $sampleCount,
    ) {
    }
}
