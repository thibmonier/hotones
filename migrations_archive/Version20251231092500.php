<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Lot 23 - Migration 2: Add company_id to users table
 *
 * This migration:
 * 1. Creates a default "HotOnes" company
 * 2. Adds company_id column to users table
 * 3. Assigns all existing users to the default company
 * 4. Updates unique constraint: email → (email, company_id)
 *
 * REVERSIBLE: down() removes company_id and restores original constraints
 * Note: Default company remains after rollback (harmless)
 */
final class Version20251231092500 extends AbstractMigration
{
    private const DEFAULT_COMPANY_ID = 1;
    private const DEFAULT_COMPANY_SLUG = 'hotones-default';

    public function getDescription(): string
    {
        return 'Lot 23 - Add company_id to users table and create default company';
    }

    public function up(Schema $schema): void
    {
        // ===================================================================
        // STEP 1: Create default company
        // ===================================================================

        // Find first SUPERADMIN user to be owner of default company
        $this->addSql(<<<'SQL'
            SET @owner_id = (
                SELECT id FROM users
                WHERE JSON_CONTAINS(roles, '"ROLE_SUPERADMIN"')
                LIMIT 1
            )
        SQL);

        // Create default company
        $this->addSql(<<<'SQL'
            INSERT INTO companies (
                id,
                name,
                slug,
                owner_id,
                status,
                subscription_tier,
                billing_start_date,
                billing_day_of_month,
                currency,
                settings,
                enabled_features,
                structure_cost_coefficient,
                employer_charges_coefficient,
                annual_paid_leave_days,
                annual_rtt_days,
                created_at,
                updated_at
            ) VALUES (
                1,
                'HotOnes',
                'hotones-default',
                @owner_id,
                'active',
                'enterprise',
                CURDATE(),
                1,
                'EUR',
                '{"timezone":"Europe/Paris","locale":"fr_FR"}',
                '["planning","analytics","business_units","ai_tools","api_access"]',
                1.3500,
                1.4500,
                25,
                10,
                NOW(),
                NOW()
            )
        SQL);

        // ===================================================================
        // STEP 2: Add company_id column to users (nullable first)
        // ===================================================================

        $this->addSql(<<<'SQL'
            ALTER TABLE users
            ADD company_id INT NULL AFTER id
        SQL);

        // ===================================================================
        // STEP 3: Assign all existing users to default company
        // ===================================================================

        $this->addSql(<<<'SQL'
            UPDATE users
            SET company_id = 1
            WHERE company_id IS NULL
        SQL);

        // ===================================================================
        // STEP 4: Make company_id NOT NULL
        // ===================================================================

        $this->addSql(<<<'SQL'
            ALTER TABLE users
            MODIFY company_id INT NOT NULL
        SQL);

        // ===================================================================
        // STEP 5: Update unique constraints (email → email+company_id)
        // ===================================================================

        // Drop old unique constraint on email
        $this->addSql(<<<'SQL'
            ALTER TABLE users
            DROP INDEX UNIQ_1483A5E9E7927C74
        SQL);

        // Add composite unique constraint
        $this->addSql(<<<'SQL'
            ALTER TABLE users
            ADD CONSTRAINT email_company_unique
            UNIQUE (email, company_id)
        SQL);

        // ===================================================================
        // STEP 6: Add foreign key
        // ===================================================================

        $this->addSql(<<<'SQL'
            CREATE INDEX idx_user_company ON users (company_id)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE users
            ADD CONSTRAINT fk_user_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // ===================================================================
        // STEP 1: Drop foreign key
        // ===================================================================

        $this->addSql(<<<'SQL'
            ALTER TABLE users
            DROP FOREIGN KEY fk_user_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_user_company ON users
        SQL);

        // ===================================================================
        // STEP 2: Drop composite unique constraint
        // ===================================================================

        $this->addSql(<<<'SQL'
            ALTER TABLE users
            DROP INDEX email_company_unique
        SQL);

        // ===================================================================
        // STEP 3: Restore original unique constraint on email
        // ===================================================================

        $this->addSql(<<<'SQL'
            ALTER TABLE users
            ADD CONSTRAINT UNIQ_1483A5E9E7927C74
            UNIQUE (email)
        SQL);

        // ===================================================================
        // STEP 4: Drop company_id column
        // ===================================================================

        $this->addSql(<<<'SQL'
            ALTER TABLE users
            DROP COLUMN company_id
        SQL);

        // NOTE: Default company (id=1) remains in database
        // This is intentional and harmless - it can be manually deleted if needed
        // We don't delete it automatically to avoid foreign key issues
    }
}
