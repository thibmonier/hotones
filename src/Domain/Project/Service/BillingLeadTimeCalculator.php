<?php

declare(strict_types=1);

namespace App\Domain\Project\Service;

use App\Domain\Project\ValueObject\LeadTimeDays;
use DateTimeImmutable;

/**
 * Compute billing lead time percentiles (p50, p75, p95) on a rolling window.
 *
 * Domain Service — pure PHP, sans dépendance Doctrine. Pattern aligné avec
 * {@see DsoCalculator} (US-110 T-110-01).
 *
 * Formule : lead_time_days = emittedAt − signedAt (par paire Quote ↔ Invoice).
 *
 * Percentile algorithm : sort ascending then linear interpolation
 *   index = p × (n − 1)
 * (NIST recommended method — type 7 in R).
 *
 * Devis sans facture sont exclus en amont (Repository) ; le calculator
 * suppose que seuls les records (signedAt, emittedAt) sont fournis.
 */
final readonly class BillingLeadTimeCalculator
{
    /**
     * @param iterable<QuoteInvoiceRecord> $records
     */
    public function calculateRolling(
        iterable $records,
        int $windowDays,
        DateTimeImmutable $now,
    ): BillingLeadTimeStats {
        $windowStart = $now->modify(sprintf('-%d days', $windowDays));

        $leadTimes = [];
        foreach ($records as $record) {
            if ($record->emittedAt < $windowStart) {
                continue;
            }

            $leadTimes[] = $record->leadTimeDays();
        }

        if ($leadTimes === []) {
            return BillingLeadTimeStats::empty();
        }

        sort($leadTimes);

        return new BillingLeadTimeStats(
            p50: LeadTimeDays::fromDays($this->percentile($leadTimes, 0.50)),
            p75: LeadTimeDays::fromDays($this->percentile($leadTimes, 0.75)),
            p95: LeadTimeDays::fromDays($this->percentile($leadTimes, 0.95)),
            count: count($leadTimes),
        );
    }

    /**
     * Linear-interpolation percentile (type 7).
     *
     * @param list<float> $sorted
     * @param float       $fraction 0..1
     */
    private function percentile(array $sorted, float $fraction): float
    {
        $n = count($sorted);

        if ($n === 1) {
            return $sorted[0];
        }

        $index = $fraction * ($n - 1);
        $lower = (int) floor($index);
        $upper = (int) ceil($index);

        if ($lower === $upper) {
            return $sorted[$lower];
        }

        $weight = $index - $lower;

        return $sorted[$lower] + $weight * ($sorted[$upper] - $sorted[$lower]);
    }
}
