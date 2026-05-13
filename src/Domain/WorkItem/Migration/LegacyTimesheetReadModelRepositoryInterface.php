<?php

declare(strict_types=1);

namespace App\Domain\WorkItem\Migration;

use DateTimeImmutable;

/**
 * Repository port for legacy timesheet migration batch reads (US-113 T-113-03).
 *
 * Implémenté côté Infrastructure (Doctrine). Multi-tenant : impl filtre
 * par current company. Iteration via batch pour éviter memory exhaustion
 * sur volumes 2000-10000 timesheets legacy.
 */
interface LegacyTimesheetReadModelRepositoryInterface
{
    /**
     * Compte le nombre de timesheets à traiter (filtre company).
     */
    public function countAll(): int;

    /**
     * Charge un batch trié par id ascendant.
     *
     * @return list<LegacyTimesheetRecord>
     */
    public function findBatch(int $batchSize, int $offset): array;

    /**
     * Applique les writes (non-dry-run mode).
     *
     * Atomique par timesheet. Update :
     *   - legacy_cost_cents (snapshot si premier)
     *   - legacy_cost_drift (true si drift > 1 cent vs snapshot existant)
     *   - migrated_at = now
     *
     * Pas de re-throw si timesheet inexistant (skip silencieux pour idempotence).
     */
    public function applyMigrationWrites(int $timesheetId, int $costCents, bool $drift, DateTimeImmutable $now): void;
}
