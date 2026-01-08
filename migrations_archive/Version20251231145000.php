<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Lot 23 - Migration 10: Add company_id to Batch 8 (Final Tables)
 *
 * This is the FINAL migration, adding company_id to the last 13 tables:
 *
 * Batch 8A: Gamification (4 tables)
 * - badges (no FK - all to default company)
 * - achievements (copy from contributors)
 * - xp_history (copy from contributors)
 * - contributor_progress (copy from contributors)
 *
 * Batch 8B: System & Misc (9 tables)
 * - account_deletion_requests (copy from users)
 * - cookie_consents (copy from users)
 * - contributor_satisfactions (copy from contributors)
 * - nps_surveys (copy from projects)
 * - onboarding_tasks (copy from contributors)
 * - onboarding_templates (copy from profiles)
 * - company_settings (one per company - add UNIQUE constraint on company_id)
 * - scheduler_entries (system-level - all to default company)
 * - lead_captures (marketing data - all to default company)
 *
 * REVERSIBLE: down() removes company_id and restores original schema
 */
final class Version20251231145000 extends AbstractMigration
{
    private const DEFAULT_COMPANY_ID = 1;

    public function getDescription(): string
    {
        return 'Lot 23 - Add company_id to final 13 tables (Batch 8 - Gamification + System)';
    }

    public function up(Schema $schema): void
    {
        // ===================================================================
        // BATCH 8A: GAMIFICATION (4 tables)
        // ===================================================================

        // -----------------------------------------------------------------
        // TABLE 1: badges
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE badges
            ADD company_id INT NULL AFTER id
        SQL);

        // All badges to default company (no FK)
        $this->addSql(<<<'SQL'
            UPDATE badges
            SET company_id = 1
            WHERE company_id IS NULL
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE badges
            MODIFY company_id INT NOT NULL
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX idx_badge_company ON badges (company_id)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE badges
            ADD CONSTRAINT fk_badge_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // -----------------------------------------------------------------
        // TABLE 2: achievements
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE achievements
            ADD company_id INT NULL AFTER id
        SQL);

        // Copy from contributors via contributor_id FK
        $this->addSql(<<<'SQL'
            UPDATE achievements a
            INNER JOIN contributors c ON a.contributor_id = c.id
            SET a.company_id = c.company_id
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE achievements
            MODIFY company_id INT NOT NULL
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX idx_achievement_company ON achievements (company_id)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE achievements
            ADD CONSTRAINT fk_achievement_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // -----------------------------------------------------------------
        // TABLE 3: xp_history
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE xp_history
            ADD company_id INT NULL AFTER id
        SQL);

        // Copy from contributors via contributor_id FK
        $this->addSql(<<<'SQL'
            UPDATE xp_history xh
            INNER JOIN contributors c ON xh.contributor_id = c.id
            SET xh.company_id = c.company_id
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE xp_history
            MODIFY company_id INT NOT NULL
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX idx_xp_history_company ON xp_history (company_id)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE xp_history
            ADD CONSTRAINT fk_xp_history_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // -----------------------------------------------------------------
        // TABLE 4: contributor_progress
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE contributor_progress
            ADD company_id INT NULL AFTER id
        SQL);

        // Copy from contributors via contributor_id FK
        $this->addSql(<<<'SQL'
            UPDATE contributor_progress cp
            INNER JOIN contributors c ON cp.contributor_id = c.id
            SET cp.company_id = c.company_id
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE contributor_progress
            MODIFY company_id INT NOT NULL
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX idx_contributor_progress_company ON contributor_progress (company_id)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE contributor_progress
            ADD CONSTRAINT fk_contributor_progress_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // ===================================================================
        // BATCH 8B: SYSTEM & MISC (9 tables)
        // ===================================================================

        // -----------------------------------------------------------------
        // TABLE 5: account_deletion_requests
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE account_deletion_requests
            ADD company_id INT NULL AFTER id
        SQL);

        // Copy from users via user_id FK
        $this->addSql(<<<'SQL'
            UPDATE account_deletion_requests adr
            INNER JOIN users u ON adr.user_id = u.id
            SET adr.company_id = u.company_id
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE account_deletion_requests
            MODIFY company_id INT NOT NULL
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX idx_account_deletion_request_company ON account_deletion_requests (company_id)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE account_deletion_requests
            ADD CONSTRAINT fk_account_deletion_request_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // -----------------------------------------------------------------
        // TABLE 6: cookie_consents
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE cookie_consents
            ADD company_id INT NULL AFTER id
        SQL);

        // Copy from users via user_id FK
        $this->addSql(<<<'SQL'
            UPDATE cookie_consents cc
            INNER JOIN users u ON cc.user_id = u.id
            SET cc.company_id = u.company_id
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE cookie_consents
            MODIFY company_id INT NOT NULL
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX idx_cookie_consent_company ON cookie_consents (company_id)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE cookie_consents
            ADD CONSTRAINT fk_cookie_consent_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // -----------------------------------------------------------------
        // TABLE 7: contributor_satisfactions
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE contributor_satisfactions
            ADD company_id INT NULL AFTER id
        SQL);

        // Copy from contributors via contributor_id FK
        $this->addSql(<<<'SQL'
            UPDATE contributor_satisfactions cs
            INNER JOIN contributors c ON cs.contributor_id = c.id
            SET cs.company_id = c.company_id
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE contributor_satisfactions
            MODIFY company_id INT NOT NULL
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX idx_contributor_satisfaction_company ON contributor_satisfactions (company_id)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE contributor_satisfactions
            ADD CONSTRAINT fk_contributor_satisfaction_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // -----------------------------------------------------------------
        // TABLE 8: nps_surveys
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE nps_surveys
            ADD company_id INT NULL AFTER id
        SQL);

        // Copy from projects via project_id FK
        $this->addSql(<<<'SQL'
            UPDATE nps_surveys ns
            INNER JOIN projects p ON ns.project_id = p.id
            SET ns.company_id = p.company_id
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE nps_surveys
            MODIFY company_id INT NOT NULL
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX idx_nps_survey_company ON nps_surveys (company_id)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE nps_surveys
            ADD CONSTRAINT fk_nps_survey_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // -----------------------------------------------------------------
        // TABLE 9: onboarding_tasks
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE onboarding_tasks
            ADD company_id INT NULL AFTER id
        SQL);

        // Copy from contributors via contributor_id FK
        $this->addSql(<<<'SQL'
            UPDATE onboarding_tasks ot
            INNER JOIN contributors c ON ot.contributor_id = c.id
            SET ot.company_id = c.company_id
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE onboarding_tasks
            MODIFY company_id INT NOT NULL
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX idx_onboarding_task_company ON onboarding_tasks (company_id)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE onboarding_tasks
            ADD CONSTRAINT fk_onboarding_task_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // -----------------------------------------------------------------
        // TABLE 10: onboarding_templates
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE onboarding_templates
            ADD company_id INT NULL AFTER id
        SQL);

        // Copy from profiles via profile_id FK
        $this->addSql(<<<'SQL'
            UPDATE onboarding_templates ot
            INNER JOIN profiles p ON ot.profile_id = p.id
            SET ot.company_id = p.company_id
            WHERE ot.profile_id IS NOT NULL
        SQL);

        // Templates without profile get default company
        $this->addSql(<<<'SQL'
            UPDATE onboarding_templates
            SET company_id = 1
            WHERE company_id IS NULL
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE onboarding_templates
            MODIFY company_id INT NOT NULL
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX idx_onboarding_template_company ON onboarding_templates (company_id)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE onboarding_templates
            ADD CONSTRAINT fk_onboarding_template_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // -----------------------------------------------------------------
        // TABLE 11: company_settings
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE company_settings
            ADD company_id INT NULL AFTER id
        SQL);

        // All existing settings to default company
        // Each company should have ONE settings record
        $this->addSql(<<<'SQL'
            UPDATE company_settings
            SET company_id = 1
            WHERE company_id IS NULL
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE company_settings
            MODIFY company_id INT NOT NULL
        SQL);

        // Add UNIQUE constraint to ensure one settings record per company
        $this->addSql(<<<'SQL'
            ALTER TABLE company_settings
            ADD CONSTRAINT company_settings_company_unique
            UNIQUE (company_id)
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX idx_company_settings_company ON company_settings (company_id)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE company_settings
            ADD CONSTRAINT fk_company_settings_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // -----------------------------------------------------------------
        // TABLE 12: scheduler_entries
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE scheduler_entries
            ADD company_id INT NULL AFTER id
        SQL);

        // All scheduler entries to default company (system-level)
        $this->addSql(<<<'SQL'
            UPDATE scheduler_entries
            SET company_id = 1
            WHERE company_id IS NULL
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE scheduler_entries
            MODIFY company_id INT NOT NULL
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX idx_scheduler_entry_company ON scheduler_entries (company_id)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE scheduler_entries
            ADD CONSTRAINT fk_scheduler_entry_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // -----------------------------------------------------------------
        // TABLE 13: lead_captures
        // -----------------------------------------------------------------

        // Check if table exists by querying database directly (schema diff doesn't reflect actual DB state)
        if ($this->connection->createSchemaManager()->tablesExist(['lead_captures'])) {
            $this->addSql(<<<'SQL'
                ALTER TABLE lead_captures
                ADD company_id INT NULL AFTER id
            SQL);

            // All lead captures to default company (marketing data)
            $this->addSql(<<<'SQL'
                UPDATE lead_captures
                SET company_id = 1
                WHERE company_id IS NULL
            SQL);

            $this->addSql(<<<'SQL'
                ALTER TABLE lead_captures
                MODIFY company_id INT NOT NULL
            SQL);

            $this->addSql(<<<'SQL'
                CREATE INDEX idx_lead_capture_company ON lead_captures (company_id)
            SQL);

            $this->addSql(<<<'SQL'
                ALTER TABLE lead_captures
                ADD CONSTRAINT fk_lead_capture_company
                FOREIGN KEY (company_id) REFERENCES companies(id)
                ON DELETE CASCADE
            SQL);
        }
    }

    public function down(Schema $schema): void
    {
        // ===================================================================
        // REVERSE BATCH 8B: SYSTEM & MISC (9 tables)
        // ===================================================================

        // -----------------------------------------------------------------
        // REVERSE TABLE 13: lead_captures
        // -----------------------------------------------------------------

        // Check if table exists by querying database directly (schema diff doesn't reflect actual DB state)
        if ($this->connection->createSchemaManager()->tablesExist(['lead_captures'])) {
            $this->addSql(<<<'SQL'
                ALTER TABLE lead_captures
                DROP FOREIGN KEY fk_lead_capture_company
            SQL);

            $this->addSql(<<<'SQL'
                DROP INDEX idx_lead_capture_company ON lead_captures
            SQL);

            $this->addSql(<<<'SQL'
                ALTER TABLE lead_captures
                DROP COLUMN company_id
            SQL);
        }

        // -----------------------------------------------------------------
        // REVERSE TABLE 12: scheduler_entries
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE scheduler_entries
            DROP FOREIGN KEY fk_scheduler_entry_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_scheduler_entry_company ON scheduler_entries
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE scheduler_entries
            DROP COLUMN company_id
        SQL);

        // -----------------------------------------------------------------
        // REVERSE TABLE 11: company_settings
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE company_settings
            DROP FOREIGN KEY fk_company_settings_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_company_settings_company ON company_settings
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE company_settings
            DROP INDEX company_settings_company_unique
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE company_settings
            DROP COLUMN company_id
        SQL);

        // -----------------------------------------------------------------
        // REVERSE TABLE 10: onboarding_templates
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE onboarding_templates
            DROP FOREIGN KEY fk_onboarding_template_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_onboarding_template_company ON onboarding_templates
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE onboarding_templates
            DROP COLUMN company_id
        SQL);

        // -----------------------------------------------------------------
        // REVERSE TABLE 9: onboarding_tasks
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE onboarding_tasks
            DROP FOREIGN KEY fk_onboarding_task_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_onboarding_task_company ON onboarding_tasks
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE onboarding_tasks
            DROP COLUMN company_id
        SQL);

        // -----------------------------------------------------------------
        // REVERSE TABLE 8: nps_surveys
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE nps_surveys
            DROP FOREIGN KEY fk_nps_survey_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_nps_survey_company ON nps_surveys
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE nps_surveys
            DROP COLUMN company_id
        SQL);

        // -----------------------------------------------------------------
        // REVERSE TABLE 7: contributor_satisfactions
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE contributor_satisfactions
            DROP FOREIGN KEY fk_contributor_satisfaction_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_contributor_satisfaction_company ON contributor_satisfactions
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE contributor_satisfactions
            DROP COLUMN company_id
        SQL);

        // -----------------------------------------------------------------
        // REVERSE TABLE 6: cookie_consents
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE cookie_consents
            DROP FOREIGN KEY fk_cookie_consent_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_cookie_consent_company ON cookie_consents
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE cookie_consents
            DROP COLUMN company_id
        SQL);

        // -----------------------------------------------------------------
        // REVERSE TABLE 5: account_deletion_requests
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE account_deletion_requests
            DROP FOREIGN KEY fk_account_deletion_request_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_account_deletion_request_company ON account_deletion_requests
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE account_deletion_requests
            DROP COLUMN company_id
        SQL);

        // ===================================================================
        // REVERSE BATCH 8A: GAMIFICATION (4 tables)
        // ===================================================================

        // -----------------------------------------------------------------
        // REVERSE TABLE 4: contributor_progress
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE contributor_progress
            DROP FOREIGN KEY fk_contributor_progress_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_contributor_progress_company ON contributor_progress
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE contributor_progress
            DROP COLUMN company_id
        SQL);

        // -----------------------------------------------------------------
        // REVERSE TABLE 3: xp_history
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE xp_history
            DROP FOREIGN KEY fk_xp_history_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_xp_history_company ON xp_history
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE xp_history
            DROP COLUMN company_id
        SQL);

        // -----------------------------------------------------------------
        // REVERSE TABLE 2: achievements
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE achievements
            DROP FOREIGN KEY fk_achievement_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_achievement_company ON achievements
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE achievements
            DROP COLUMN company_id
        SQL);

        // -----------------------------------------------------------------
        // REVERSE TABLE 1: badges
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE badges
            DROP FOREIGN KEY fk_badge_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_badge_company ON badges
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE badges
            DROP COLUMN company_id
        SQL);
    }
}
