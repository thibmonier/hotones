<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Lot 23 - Migration 3: Add company_id to Batch 1 (Contributors)
 *
 * This migration adds company_id to the first batch of entities:
 * - contributors (copy from users.company_id via user_id relation)
 * - employment_periods (copy from contributors.company_id)
 * - profiles (assign all to default company id=1)
 * - contributor_skills (copy from contributors.company_id)
 *
 * REVERSIBLE: down() removes company_id from all tables
 */
final class Version20251231092547 extends AbstractMigration
{
    private const DEFAULT_COMPANY_ID = 1;

    public function getDescription(): string
    {
        return 'Lot 23 - Add company_id to contributors, employment_periods, profiles, contributor_skills';
    }

    public function up(Schema $schema): void
    {
        // ===================================================================
        // TABLE 1: contributors
        // ===================================================================

        // STEP 1: Add company_id column (nullable)
        $this->addSql(<<<'SQL'
            ALTER TABLE contributors
            ADD company_id INT NULL AFTER id
        SQL);

        // STEP 2: Copy company_id from users (via user_id relation)
        // Contributors with user_id get company_id from their user
        $this->addSql(<<<'SQL'
            UPDATE contributors c
            INNER JOIN users u ON c.user_id = u.id
            SET c.company_id = u.company_id
            WHERE c.user_id IS NOT NULL
        SQL);

        // Contributors without user_id get default company
        $this->addSql(<<<'SQL'
            UPDATE contributors
            SET company_id = 1
            WHERE company_id IS NULL
        SQL);

        // STEP 3: Make NOT NULL
        $this->addSql(<<<'SQL'
            ALTER TABLE contributors
            MODIFY company_id INT NOT NULL
        SQL);

        // STEP 4: Add index
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_contributor_company ON contributors (company_id)
        SQL);

        // STEP 5: Add foreign key
        $this->addSql(<<<'SQL'
            ALTER TABLE contributors
            ADD CONSTRAINT fk_contributor_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // ===================================================================
        // TABLE 2: employment_periods
        // ===================================================================

        // STEP 1: Add company_id column (nullable)
        $this->addSql(<<<'SQL'
            ALTER TABLE employment_periods
            ADD company_id INT NULL AFTER id
        SQL);

        // STEP 2: Copy company_id from contributors
        $this->addSql(<<<'SQL'
            UPDATE employment_periods ep
            INNER JOIN contributors c ON ep.contributor_id = c.id
            SET ep.company_id = c.company_id
        SQL);

        // STEP 3: Make NOT NULL
        $this->addSql(<<<'SQL'
            ALTER TABLE employment_periods
            MODIFY company_id INT NOT NULL
        SQL);

        // STEP 4: Add index
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_employment_period_company ON employment_periods (company_id)
        SQL);

        // STEP 5: Add foreign key
        $this->addSql(<<<'SQL'
            ALTER TABLE employment_periods
            ADD CONSTRAINT fk_employment_period_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // ===================================================================
        // TABLE 3: profiles
        // ===================================================================

        // STEP 1: Add company_id column (nullable)
        $this->addSql(<<<'SQL'
            ALTER TABLE profiles
            ADD company_id INT NULL AFTER id
        SQL);

        // STEP 2: Assign all existing profiles to default company
        // Profiles are reference data - all current profiles belong to default company
        $this->addSql(<<<'SQL'
            UPDATE profiles
            SET company_id = 1
            WHERE company_id IS NULL
        SQL);

        // STEP 3: Make NOT NULL
        $this->addSql(<<<'SQL'
            ALTER TABLE profiles
            MODIFY company_id INT NOT NULL
        SQL);

        // STEP 4: Update unique constraint (name → name+company_id)
        $this->addSql(<<<'SQL'
            ALTER TABLE profiles
            DROP INDEX UNIQ_8B3085305E237E06
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE profiles
            ADD CONSTRAINT profile_name_company_unique
            UNIQUE (name, company_id)
        SQL);

        // STEP 5: Add index
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_profile_company ON profiles (company_id)
        SQL);

        // STEP 6: Add foreign key
        $this->addSql(<<<'SQL'
            ALTER TABLE profiles
            ADD CONSTRAINT fk_profile_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // ===================================================================
        // TABLE 4: contributor_skills
        // ===================================================================

        // STEP 1: Add company_id column (nullable)
        $this->addSql(<<<'SQL'
            ALTER TABLE contributor_skills
            ADD company_id INT NULL AFTER id
        SQL);

        // STEP 2: Copy company_id from contributors
        $this->addSql(<<<'SQL'
            UPDATE contributor_skills cs
            INNER JOIN contributors c ON cs.contributor_id = c.id
            SET cs.company_id = c.company_id
        SQL);

        // STEP 3: Make NOT NULL
        $this->addSql(<<<'SQL'
            ALTER TABLE contributor_skills
            MODIFY company_id INT NOT NULL
        SQL);

        // STEP 4: Add index
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_contributor_skill_company ON contributor_skills (company_id)
        SQL);

        // STEP 5: Add foreign key
        $this->addSql(<<<'SQL'
            ALTER TABLE contributor_skills
            ADD CONSTRAINT fk_contributor_skill_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // ===================================================================
        // REVERSE TABLE 4: contributor_skills
        // ===================================================================

        $this->addSql(<<<'SQL'
            ALTER TABLE contributor_skills
            DROP FOREIGN KEY fk_contributor_skill_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_contributor_skill_company ON contributor_skills
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE contributor_skills
            DROP COLUMN company_id
        SQL);

        // ===================================================================
        // REVERSE TABLE 3: profiles
        // ===================================================================

        $this->addSql(<<<'SQL'
            ALTER TABLE profiles
            DROP FOREIGN KEY fk_profile_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_profile_company ON profiles
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE profiles
            DROP INDEX profile_name_company_unique
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE profiles
            ADD CONSTRAINT UNIQ_8B3085305E237E06
            UNIQUE (name)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE profiles
            DROP COLUMN company_id
        SQL);

        // ===================================================================
        // REVERSE TABLE 2: employment_periods
        // ===================================================================

        $this->addSql(<<<'SQL'
            ALTER TABLE employment_periods
            DROP FOREIGN KEY fk_employment_period_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_employment_period_company ON employment_periods
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE employment_periods
            DROP COLUMN company_id
        SQL);

        // ===================================================================
        // REVERSE TABLE 1: contributors
        // ===================================================================

        $this->addSql(<<<'SQL'
            ALTER TABLE contributors
            DROP FOREIGN KEY fk_contributor_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_contributor_company ON contributors
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE contributors
            DROP COLUMN company_id
        SQL);
    }
}
