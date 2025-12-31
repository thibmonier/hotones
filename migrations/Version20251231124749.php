<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Lot 23 - Migration 6: Add company_id to Batch 4 (Timesheets & Planning)
 *
 * This migration adds company_id to the fourth batch of entities:
 * - timesheets (copy from contributors)
 * - vacations (copy from contributors)
 * - planning (copy from contributors)
 *
 * REVERSIBLE: down() removes company_id from all tables
 */
final class Version20251231124749 extends AbstractMigration
{
    private const DEFAULT_COMPANY_ID = 1;

    public function getDescription(): string
    {
        return 'Lot 23 - Add company_id to timesheets, vacations, planning';
    }

    public function up(Schema $schema): void
    {
        // ===================================================================
        // TABLE 1: timesheets
        // ===================================================================

        // STEP 1: Add company_id column (nullable)
        $this->addSql(<<<'SQL'
            ALTER TABLE timesheets
            ADD company_id INT NULL AFTER id
        SQL);

        // STEP 2: Copy company_id from contributors
        $this->addSql(<<<'SQL'
            UPDATE timesheets t
            INNER JOIN contributors c ON t.contributor_id = c.id
            SET t.company_id = c.company_id
        SQL);

        // STEP 3: Make NOT NULL
        $this->addSql(<<<'SQL'
            ALTER TABLE timesheets
            MODIFY company_id INT NOT NULL
        SQL);

        // STEP 4: Add index
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_timesheet_company ON timesheets (company_id)
        SQL);

        // STEP 5: Add foreign key
        $this->addSql(<<<'SQL'
            ALTER TABLE timesheets
            ADD CONSTRAINT fk_timesheet_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // ===================================================================
        // TABLE 2: vacations
        // ===================================================================

        // STEP 1: Add company_id column (nullable)
        $this->addSql(<<<'SQL'
            ALTER TABLE vacations
            ADD company_id INT NULL AFTER id
        SQL);

        // STEP 2: Copy company_id from contributors
        $this->addSql(<<<'SQL'
            UPDATE vacations v
            INNER JOIN contributors c ON v.contributor_id = c.id
            SET v.company_id = c.company_id
        SQL);

        // STEP 3: Make NOT NULL
        $this->addSql(<<<'SQL'
            ALTER TABLE vacations
            MODIFY company_id INT NOT NULL
        SQL);

        // STEP 4: Add index
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_vacation_company ON vacations (company_id)
        SQL);

        // STEP 5: Add foreign key
        $this->addSql(<<<'SQL'
            ALTER TABLE vacations
            ADD CONSTRAINT fk_vacation_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // ===================================================================
        // TABLE 3: planning
        // ===================================================================

        // STEP 1: Add company_id column (nullable)
        $this->addSql(<<<'SQL'
            ALTER TABLE planning
            ADD company_id INT NULL AFTER id
        SQL);

        // STEP 2: Copy company_id from contributors
        $this->addSql(<<<'SQL'
            UPDATE planning p
            INNER JOIN contributors c ON p.contributor_id = c.id
            SET p.company_id = c.company_id
        SQL);

        // STEP 3: Make NOT NULL
        $this->addSql(<<<'SQL'
            ALTER TABLE planning
            MODIFY company_id INT NOT NULL
        SQL);

        // STEP 4: Add index
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_planning_company ON planning (company_id)
        SQL);

        // STEP 5: Add foreign key
        $this->addSql(<<<'SQL'
            ALTER TABLE planning
            ADD CONSTRAINT fk_planning_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // ===================================================================
        // REVERSE TABLE 3: planning
        // ===================================================================

        $this->addSql(<<<'SQL'
            ALTER TABLE planning
            DROP FOREIGN KEY fk_planning_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_planning_company ON planning
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE planning
            DROP COLUMN company_id
        SQL);

        // ===================================================================
        // REVERSE TABLE 2: vacations
        // ===================================================================

        $this->addSql(<<<'SQL'
            ALTER TABLE vacations
            DROP FOREIGN KEY fk_vacation_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_vacation_company ON vacations
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE vacations
            DROP COLUMN company_id
        SQL);

        // ===================================================================
        // REVERSE TABLE 1: timesheets
        // ===================================================================

        $this->addSql(<<<'SQL'
            ALTER TABLE timesheets
            DROP FOREIGN KEY fk_timesheet_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_timesheet_company ON timesheets
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE timesheets
            DROP COLUMN company_id
        SQL);
    }
}
