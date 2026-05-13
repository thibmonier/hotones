<?php

declare(strict_types=1);

namespace App\Application\Project\Query\BillingLeadTimeKpi;

use App\Domain\Project\Service\BillingLeadTimeStats;

/**
 * Read-model DTO for the billing lead time dashboard widget (US-111 T-111-04).
 *
 * - `stats30` / `stats90` / `stats365` : percentiles (p50, p75, p95) sur 3 rolling windows
 * - `topSlowClients` : top 3 clients par lead time moyen sur la fenêtre 30j (max length 3)
 * - `warningThresholdDays` / `warningTriggered` : médiane 30j > 14j default
 */
final readonly class BillingLeadTimeKpiDto
{
    /**
     * @param list<TopSlowClientDto> $topSlowClients
     */
    public function __construct(
        public BillingLeadTimeStats $stats30,
        public BillingLeadTimeStats $stats90,
        public BillingLeadTimeStats $stats365,
        public array $topSlowClients,
        public float $warningThresholdDays,
        public bool $warningTriggered,
    ) {
    }
}
