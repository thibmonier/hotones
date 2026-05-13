<?php

declare(strict_types=1);

namespace App\Application\Project\Query\BillingLeadTimeKpi;

/**
 * Client lent du top 3 (US-111 T-111-04).
 *
 * Aggregation by clientId avec moyenne lead time + sample count.
 */
final readonly class TopSlowClientDto
{
    public function __construct(
        public int $clientId,
        public string $clientName,
        public float $averageLeadTimeDays,
        public int $sampleCount,
    ) {
    }
}
