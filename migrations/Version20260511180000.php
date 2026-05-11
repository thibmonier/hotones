<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * EPIC-003 Phase 3 (sprint-023 US-107 ADR-0016 Q4.x) — ajout colonnes
 * persistence margin snapshot sur `projects` table.
 *
 * Snapshot calculé par UC `CalculateProjectMargin` (sprint-022 US-104) :
 * - cout_total_cents : somme WorkItem.cost() (entiers centimes)
 * - facture_total_cents : somme Invoice.amountTtc paid (entiers centimes)
 * - marge_calculated_at : timestamp last snapshot
 *
 * Persistence permet :
 * 1. Dashboard queries marge sans recalcul (perf)
 * 2. Historique évolution marge dans le temps (si batch periodic)
 * 3. UI affichage immédiat sans event handler trigger
 *
 * Default NULL pour rows existantes (rétrocompatible — Project sans
 * WorkItem associé = null marge).
 */
final class Version20260511180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'EPIC-003 Phase 3 US-107 — add margin snapshot cols (cout_total_cents + facture_total_cents + marge_calculated_at) to projects table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE projects ADD cout_total_cents INT DEFAULT NULL');
        $this->addSql('ALTER TABLE projects ADD facture_total_cents INT DEFAULT NULL');
        $this->addSql('ALTER TABLE projects ADD marge_calculated_at DATETIME DEFAULT NULL COMMENT "(DC2Type:datetime_immutable)"');
        $this->addSql('CREATE INDEX IDX_PROJECTS_MARGE_CALCULATED_AT ON projects (marge_calculated_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_PROJECTS_MARGE_CALCULATED_AT ON projects');
        $this->addSql('ALTER TABLE projects DROP cout_total_cents');
        $this->addSql('ALTER TABLE projects DROP facture_total_cents');
        $this->addSql('ALTER TABLE projects DROP marge_calculated_at');
    }
}
