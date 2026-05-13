<?php

declare(strict_types=1);

namespace App\Domain\Project\Service;

use App\Domain\Project\ValueObject\LeadTimeDays;

/**
 * Aggregated billing lead time statistics over a rolling window (US-111).
 *
 * - `p50` : median lead time
 * - `p75` : 75ᵗʰ percentile (queue slow start)
 * - `p95` : 95ᵗʰ percentile (worst-case lead time)
 * - `count` : sample size used in computation
 */
final readonly class BillingLeadTimeStats
{
    public function __construct(
        public LeadTimeDays $p50,
        public LeadTimeDays $p75,
        public LeadTimeDays $p95,
        public int $count,
    ) {
    }

    public static function empty(): self
    {
        return new self(
            p50: LeadTimeDays::zero(),
            p75: LeadTimeDays::zero(),
            p95: LeadTimeDays::zero(),
            count: 0,
        );
    }
}
