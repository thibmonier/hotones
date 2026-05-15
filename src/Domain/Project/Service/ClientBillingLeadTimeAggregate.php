<?php

declare(strict_types=1);

namespace App\Domain\Project\Service;

/**
 * Aggregate par client du KPI temps de facturation (US-116 T-116-01).
 *
 * Drill-down de {@see \App\Application\Project\Query\BillingLeadTimeKpi\BillingLeadTimeKpiDto}.
 * Domain pure — Repository hydrate cette structure depuis SQL.
 */
final readonly class ClientBillingLeadTimeAggregate
{
    public function __construct(
        public string $clientName,
        public float $leadTimeAverageDays,
        public int $sampleCount,
    ) {
    }
}
