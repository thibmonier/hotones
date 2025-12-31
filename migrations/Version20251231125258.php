<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Lot 23 - Migration 7: Add company_id to Batch 5 (Reference Data)
 *
 * This migration adds company_id to the fifth batch of entities (reference data):
 * - technologies (all to default company)
 * - service_categories (all to default company)
 * - skills (all to default company, modify unique constraint name → (name, company_id))
 *
 * IMPORTANT: skills.name unique constraint changes from global to composite
 *
 * REVERSIBLE: down() removes company_id and restores original unique constraint
 */
final class Version20251231125258 extends AbstractMigration
{
    private const DEFAULT_COMPANY_ID = 1;

    public function getDescription(): string
    {
        return 'Lot 23 - Add company_id to technologies, service_categories, skills (+ modify skills.name unique constraint)';
    }

    public function up(Schema $schema): void
    {
        // ===================================================================
        // TABLE 1: technologies
        // ===================================================================

        // STEP 1: Add company_id column (nullable)
        $this->addSql(<<<'SQL'
            ALTER TABLE technologies
            ADD company_id INT NULL AFTER id
        SQL);

        // STEP 2: Assign all existing technologies to default company
        // Technologies are global reference data
        $this->addSql(<<<'SQL'
            UPDATE technologies
            SET company_id = 1
            WHERE company_id IS NULL
        SQL);

        // STEP 3: Make NOT NULL
        $this->addSql(<<<'SQL'
            ALTER TABLE technologies
            MODIFY company_id INT NOT NULL
        SQL);

        // STEP 4: Add index
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_technology_company ON technologies (company_id)
        SQL);

        // STEP 5: Add foreign key
        $this->addSql(<<<'SQL'
            ALTER TABLE technologies
            ADD CONSTRAINT fk_technology_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // ===================================================================
        // TABLE 2: service_categories
        // ===================================================================

        // STEP 1: Add company_id column (nullable)
        $this->addSql(<<<'SQL'
            ALTER TABLE service_categories
            ADD company_id INT NULL AFTER id
        SQL);

        // STEP 2: Assign all existing service categories to default company
        // Service categories are global reference data
        $this->addSql(<<<'SQL'
            UPDATE service_categories
            SET company_id = 1
            WHERE company_id IS NULL
        SQL);

        // STEP 3: Make NOT NULL
        $this->addSql(<<<'SQL'
            ALTER TABLE service_categories
            MODIFY company_id INT NOT NULL
        SQL);

        // STEP 4: Add index
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_service_category_company ON service_categories (company_id)
        SQL);

        // STEP 5: Add foreign key
        $this->addSql(<<<'SQL'
            ALTER TABLE service_categories
            ADD CONSTRAINT fk_service_category_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // ===================================================================
        // TABLE 3: skills
        // ===================================================================

        // STEP 1: Add company_id column (nullable)
        $this->addSql(<<<'SQL'
            ALTER TABLE skills
            ADD company_id INT NULL AFTER id
        SQL);

        // STEP 2: Assign all existing skills to default company
        // Skills are global reference data
        $this->addSql(<<<'SQL'
            UPDATE skills
            SET company_id = 1
            WHERE company_id IS NULL
        SQL);

        // STEP 3: Make NOT NULL
        $this->addSql(<<<'SQL'
            ALTER TABLE skills
            MODIFY company_id INT NOT NULL
        SQL);

        // STEP 4: Drop old unique constraint on name
        $this->addSql(<<<'SQL'
            ALTER TABLE skills
            DROP INDEX UNIQ_D53116705E237E06
        SQL);

        // STEP 5: Add composite unique constraint (name, company_id)
        $this->addSql(<<<'SQL'
            ALTER TABLE skills
            ADD CONSTRAINT skill_name_company_unique
            UNIQUE (name, company_id)
        SQL);

        // STEP 6: Add index
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_skill_company ON skills (company_id)
        SQL);

        // STEP 7: Add foreign key
        $this->addSql(<<<'SQL'
            ALTER TABLE skills
            ADD CONSTRAINT fk_skill_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // ===================================================================
        // REVERSE TABLE 3: skills
        // ===================================================================

        $this->addSql(<<<'SQL'
            ALTER TABLE skills
            DROP FOREIGN KEY fk_skill_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_skill_company ON skills
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE skills
            DROP INDEX skill_name_company_unique
        SQL);

        // Restore original unique constraint on name
        $this->addSql(<<<'SQL'
            ALTER TABLE skills
            ADD CONSTRAINT UNIQ_D53116705E237E06
            UNIQUE (name)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE skills
            DROP COLUMN company_id
        SQL);

        // ===================================================================
        // REVERSE TABLE 2: service_categories
        // ===================================================================

        $this->addSql(<<<'SQL'
            ALTER TABLE service_categories
            DROP FOREIGN KEY fk_service_category_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_service_category_company ON service_categories
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE service_categories
            DROP COLUMN company_id
        SQL);

        // ===================================================================
        // REVERSE TABLE 1: technologies
        // ===================================================================

        $this->addSql(<<<'SQL'
            ALTER TABLE technologies
            DROP FOREIGN KEY fk_technology_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_technology_company ON technologies
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE technologies
            DROP COLUMN company_id
        SQL);
    }
}
