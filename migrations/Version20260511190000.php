<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * EPIC-003 Phase 3 (sprint-023 US-108 ADR-0016 Q5.1 D) — ajout col
 * `margin_threshold_percent` sur tables `clients` + `projects`.
 *
 * Configurabilité hiérarchique seuil marge (Q5.1 option D) :
 * 1. Project.margin_threshold_percent (override le plus prioritaire)
 * 2. Client.margin_threshold_percent (override secondaire)
 * 3. Default global env var MARGIN_ALERT_THRESHOLD (hardcoded 10.0 %
 *    fallback handler `RecalculateProjectMarginOnWorkItemRecorded`)
 *
 * Resolution dans UC `CalculateProjectMargin` (US-104 sprint-022) :
 * - Si Project.margin_threshold_percent non-null → use it
 * - Sinon Client.margin_threshold_percent non-null → use it
 * - Sinon $command->thresholdPercent (default handler)
 *
 * Default NULL rétrocompatible : tous Project/Client existants utilisent
 * default global jusqu'à override admin/PO.
 */
final class Version20260511190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'EPIC-003 Phase 3 US-108 — add margin_threshold_percent col (decimal 5,2 nullable) to clients + projects tables (hierarchical resolution).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE clients ADD margin_threshold_percent NUMERIC(5, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE projects ADD margin_threshold_percent NUMERIC(5, 2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE clients DROP margin_threshold_percent');
        $this->addSql('ALTER TABLE projects DROP margin_threshold_percent');
    }
}
