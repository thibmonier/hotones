<?php

declare(strict_types=1);

namespace App\Domain\WorkItem\Migration;

/**
 * Détail d'un drift de migration (US-113 T-113-02).
 *
 * Représente un timesheet dont le cost recalculé diffère du snapshot
 * `legacy_cost_cents` de plus de 1 cent. Listé dans le rapport CSV
 * (T-113-04) et le log migration.
 */
final readonly class MigrationDriftDetail
{
    public function __construct(
        public int $timesheetId,
        public int $contributorId,
        public int $legacyCostCents,
        public int $recomputedCostCents,
    ) {
    }

    public function deltaCents(): int
    {
        return $this->recomputedCostCents - $this->legacyCostCents;
    }

    public function absoluteDeltaCents(): int
    {
        return abs($this->deltaCents());
    }
}
