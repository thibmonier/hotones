<?php

declare(strict_types=1);

namespace App\Domain\WorkItem\Migration;

use DateTimeImmutable;

/**
 * Read-model DTO for legacy timesheet migration (US-113 T-113-02).
 *
 * Hydraté depuis table `timesheets` legacy. Le `legacyCostCents` représente
 * le snapshot stocké lors d'une migration précédente (null si jamais migré).
 *
 * Domain pure — pas de référence Doctrine.
 */
final readonly class LegacyTimesheetRecord
{
    public function __construct(
        public int $timesheetId,
        public int $contributorId,
        public DateTimeImmutable $workDate,
        public float $hours,
        public ?int $legacyCostCents,
        public ?DateTimeImmutable $migratedAt,
    ) {
    }

    public function isAlreadyMigrated(): bool
    {
        return $this->migratedAt !== null;
    }
}
