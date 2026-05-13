<?php

declare(strict_types=1);

namespace App\Domain\WorkItem\Migration;

/**
 * Résultat agrégé d'un run migration (US-113 T-113-02).
 *
 * - `migrated`             : nb timesheets migrés ce run (snapshot écrit)
 * - `alreadyMigrated`      : nb skipped (migrated_at != null)
 * - `missingRate`          : nb timesheets sans HourlyRate résoluble
 * - `drifts`               : list<MigrationDriftDetail> — drift > 1 cent
 * - `totalLegacyCostCents` : sum legacy cost sur items déjà migrés
 * - `totalDriftCents`      : sum abs(delta) sur drifts
 *
 * `driftRatio()` retourne ratio drift global vs total cost legacy. Utilisé
 * pour évaluer trigger abandon ADR-0013 cas 3 (drift > 5 %).
 */
final readonly class WorkItemMigrationResult
{
    /**
     * @param list<MigrationDriftDetail> $drifts
     */
    public function __construct(
        public int $migrated,
        public int $alreadyMigrated,
        public int $missingRate,
        public array $drifts,
        public int $totalLegacyCostCents,
        public int $totalDriftCents,
    ) {
    }

    public function totalProcessed(): int
    {
        return $this->migrated + $this->alreadyMigrated + $this->missingRate;
    }

    public function driftCount(): int
    {
        return count($this->drifts);
    }

    public function driftRatio(): float
    {
        if ($this->totalLegacyCostCents === 0) {
            return 0.0;
        }

        return $this->totalDriftCents / $this->totalLegacyCostCents;
    }

    /**
     * Trigger abandon ADR-0013 cas 3 si drift global > 5 % du total cost.
     */
    public function shouldTriggerAbandonCase3(): bool
    {
        return $this->driftRatio() > 0.05;
    }
}
