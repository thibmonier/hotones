<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * EPIC-003 Phase 4 sprint-024 US-113 T-113-01.
 *
 * Add tracking columns to `timesheets` table for legacy WorkItem.cost
 * migration / drift detection :
 *
 * - `migrated_at`        datetime nullable — timestamp last migration run (idempotence)
 * - `legacy_cost_drift`  bool default false — flagged if recalculated cost differs > 1 cent
 * - `legacy_cost_cents`  int nullable — snapshot of legacy cost in cents (rollback safety)
 *
 * Migration idempotente via INFORMATION_SCHEMA checks (cf. Version20260109115915
 * AI image fields pattern). Sécurise replay sur DB déjà partiellement migrée.
 */
final class Version20260513090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'US-113 T-113-01 — Add migrated_at + legacy_cost_drift + legacy_cost_cents to timesheets';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE timesheets
              ADD COLUMN IF NOT EXISTS migrated_at DATETIME NULL DEFAULT NULL,
              ADD COLUMN IF NOT EXISTS legacy_cost_drift TINYINT(1) NOT NULL DEFAULT 0,
              ADD COLUMN IF NOT EXISTS legacy_cost_cents INT NULL DEFAULT NULL
            SQL);

        $this->addSql(
            'CREATE INDEX IF NOT EXISTS idx_timesheet_migrated_at ON timesheets (migrated_at)',
        );

        $this->addSql(
            'CREATE INDEX IF NOT EXISTS idx_timesheet_legacy_cost_drift ON timesheets (legacy_cost_drift)',
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_timesheet_legacy_cost_drift ON timesheets');
        $this->addSql('DROP INDEX IF EXISTS idx_timesheet_migrated_at ON timesheets');

        $this->addSql(<<<'SQL'
            ALTER TABLE timesheets
              DROP COLUMN IF EXISTS legacy_cost_cents,
              DROP COLUMN IF EXISTS legacy_cost_drift,
              DROP COLUMN IF EXISTS migrated_at
            SQL);
    }
}
