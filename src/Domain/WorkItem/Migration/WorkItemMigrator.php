<?php

declare(strict_types=1);

namespace App\Domain\WorkItem\Migration;

use DateTimeImmutable;

/**
 * Migre les legacy timesheets vers le modèle DDD WorkItem (US-113 T-113-02).
 *
 * Domain Service pur — orchestrate snapshot legacy cost + drift detection.
 * Ne fait pas de persistance directe : le caller (Application Layer Command)
 * récupère le résultat et applique les writes.
 *
 * Algorithme par record :
 *   1. Skip si `migrated_at` déjà set (idempotence)
 *   2. Résoudre HourlyRate via HourlyRateProviderInterface
 *      - Si null → flag missingRate, skip
 *   3. Calculer recomputed cost (cents) = rate × hours
 *   4. Si `legacy_cost_cents` est null → premier snapshot, écrit `legacy_cost_cents`
 *   5. Sinon → compare `legacy_cost_cents` vs recomputed
 *      - delta > 1 cent → drift, append au rapport, flag `legacy_cost_drift`
 *
 * Trigger abandon ADR-0013 cas 3 vérifié par {@see WorkItemMigrationResult::shouldTriggerAbandonCase3()}.
 *
 * EPIC-003 Phase 4 sprint-024 US-113 T-113-02.
 */
final readonly class WorkItemMigrator
{
    public const int DRIFT_THRESHOLD_CENTS = 1;

    public function __construct(
        private HourlyRateProviderInterface $hourlyRateProvider,
    ) {
    }

    /**
     * Compute migration outcome for a batch of legacy records (idempotent).
     *
     * Le caller applique ensuite les writes :
     *   - timesheets.legacy_cost_cents = recomputed (si firstTime)
     *   - timesheets.legacy_cost_drift = true (si drift > threshold)
     *   - timesheets.migrated_at = now
     *
     * @param iterable<LegacyTimesheetRecord> $records
     */
    public function migrate(iterable $records, DateTimeImmutable $now): WorkItemMigrationResult
    {
        $migrated = 0;
        $alreadyMigrated = 0;
        $missingRate = 0;
        $drifts = [];
        $totalLegacyCost = 0;
        $totalDrift = 0;

        foreach ($records as $record) {
            if ($record->isAlreadyMigrated()) {
                ++$alreadyMigrated;
                if ($record->legacyCostCents !== null) {
                    $totalLegacyCost += $record->legacyCostCents;
                }

                continue;
            }

            $rateCents = $this->hourlyRateProvider->resolveAt($record->contributorId, $record->workDate);

            if ($rateCents === null) {
                ++$missingRate;

                continue;
            }

            $recomputed = (int) round($rateCents * $record->hours);
            ++$migrated;

            if ($record->legacyCostCents === null) {
                // Premier snapshot : pas de comparaison drift, juste baseline.
                $totalLegacyCost += $recomputed;

                continue;
            }

            $totalLegacyCost += $record->legacyCostCents;
            $deltaCents = abs($recomputed - $record->legacyCostCents);

            if ($deltaCents > self::DRIFT_THRESHOLD_CENTS) {
                $drifts[] = new MigrationDriftDetail(
                    timesheetId: $record->timesheetId,
                    contributorId: $record->contributorId,
                    legacyCostCents: $record->legacyCostCents,
                    recomputedCostCents: $recomputed,
                );
                $totalDrift += $deltaCents;
            }
        }

        return new WorkItemMigrationResult(
            migrated: $migrated,
            alreadyMigrated: $alreadyMigrated,
            missingRate: $missingRate,
            drifts: $drifts,
            totalLegacyCostCents: $totalLegacyCost,
            totalDriftCents: $totalDrift,
        );
    }
}
