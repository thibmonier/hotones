<?php

declare(strict_types=1);

namespace App\Domain\Project\Service;

use DateTimeImmutable;

/**
 * Compute margin adoption classification stats across active projects (US-112).
 *
 * Domain Service — pure PHP, sans dépendance Doctrine. Pattern aligné avec
 * {@see DsoCalculator} (US-110 T-110-01) et {@see BillingLeadTimeCalculator}
 * (US-111 T-111-01).
 *
 * Classification :
 *   - Fresh           : margin_calculated_at < 7 jours
 *   - StaleWarning    : 7 ≤ margin_calculated_at < 30 jours
 *   - StaleCritical   : margin_calculated_at NULL ou ≥ 30 jours
 *
 * Indicateur clé du trigger abandon ADR-0013 cas 2 (< 3 utilisations
 * alerte dépassement / mois post prod = gadget).
 */
final readonly class MarginAdoptionCalculator
{
    public const float FRESH_THRESHOLD_DAYS = 7.0;
    public const float STALE_WARNING_THRESHOLD_DAYS = 30.0;

    /**
     * @param iterable<ProjectMarginSnapshotRecord> $records
     */
    public function classify(iterable $records, DateTimeImmutable $now): MarginAdoptionStats
    {
        $fresh = 0;
        $staleWarning = 0;
        $staleCritical = 0;
        $total = 0;

        foreach ($records as $record) {
            ++$total;

            $age = $record->ageInDays($now);

            if ($age === null || $age >= self::STALE_WARNING_THRESHOLD_DAYS) {
                ++$staleCritical;
                continue;
            }

            if ($age >= self::FRESH_THRESHOLD_DAYS) {
                ++$staleWarning;
                continue;
            }

            ++$fresh;
        }

        if ($total === 0) {
            return MarginAdoptionStats::empty();
        }

        return new MarginAdoptionStats(
            freshCount: $fresh,
            staleWarningCount: $staleWarning,
            staleCriticalCount: $staleCritical,
            totalActive: $total,
            freshPercent: round($fresh * 100.0 / $total, 1),
        );
    }
}
