<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * EPIC-003 Phase 3 (sprint-021 US-101 ADR-0016 Q3.1) — ajout colonne `status`
 * sur table `timesheets` pour persister WorkItemStatus enum 4 états.
 *
 * Default 'draft' pour rows existantes (rétrocompatible — toutes les
 * timesheets historiques sont considérées draft jusqu'à validation
 * manager OU facturation Invoice cross-aggregate listener).
 *
 * Index ajouté pour requêtes par status (listings managers, dashboards).
 */
final class Version20260510170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'EPIC-003 Phase 3 US-101 — add status column to timesheets table (workflow state machine 4 états : draft/validated/billed/paid).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE timesheets ADD status VARCHAR(16) DEFAULT 'draft' NOT NULL");
        $this->addSql('CREATE INDEX IDX_TIMESHEETS_STATUS ON timesheets (status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_TIMESHEETS_STATUS ON timesheets');
        $this->addSql('ALTER TABLE timesheets DROP status');
    }
}
