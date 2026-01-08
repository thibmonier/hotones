<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Lot 23 - Migration 9: Add company_id to Batch 7 (Business Critical Tables)
 *
 * This migration adds company_id to 22 business-critical tables:
 *
 * Batch 7A: Project & Finance (9 tables)
 * - project_events, project_health_score, project_technologies, project_technology_versions
 * - order_tasks, running_timers
 * - invoices (CRITICAL: modify invoice_number unique constraint), invoice_lines
 * - expense_reports
 *
 * Batch 7B: HR & Employment (3 tables)
 * - employment_period_profiles, contributor_profiles, performance_reviews
 *
 * Batch 7C: SaaS & Subscriptions (7 tables)
 * - saas_providers, saas_services, saas_subscriptions
 * - saas_vendors, saas_distribution_providers, saas_subscriptions_v2
 * - billing_markers
 *
 * Batch 7D: Notifications (3 tables)
 * - notifications, notification_preferences
 * - notification_settings (CRITICAL: modify setting_key unique constraint)
 *
 * REVERSIBLE: down() removes company_id and restores original unique constraints
 */
final class Version20251231142500 extends AbstractMigration
{
    private const DEFAULT_COMPANY_ID = 1;

    public function getDescription(): string
    {
        return 'Lot 23 - Add company_id to 22 business-critical tables (Batch 7)';
    }

    public function up(Schema $schema): void
    {
        // ===================================================================
        // BATCH 7A: PROJECT & FINANCE (9 tables)
        // ===================================================================

        // -----------------------------------------------------------------
        // TABLE 1: project_events
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE project_events
            ADD company_id INT NULL AFTER id
        SQL);

        // Copy from projects via project_id FK
        $this->addSql(<<<'SQL'
            UPDATE project_events pe
            INNER JOIN projects p ON pe.project_id = p.id
            SET pe.company_id = p.company_id
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE project_events
            MODIFY company_id INT NOT NULL
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX idx_project_event_company ON project_events (company_id)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE project_events
            ADD CONSTRAINT fk_project_event_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // -----------------------------------------------------------------
        // TABLE 2: project_health_score
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE project_health_score
            ADD company_id INT NULL AFTER id
        SQL);

        // Copy from projects via project_id FK
        $this->addSql(<<<'SQL'
            UPDATE project_health_score phs
            INNER JOIN projects p ON phs.project_id = p.id
            SET phs.company_id = p.company_id
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE project_health_score
            MODIFY company_id INT NOT NULL
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX idx_project_health_score_company ON project_health_score (company_id)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE project_health_score
            ADD CONSTRAINT fk_project_health_score_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // -----------------------------------------------------------------
        // TABLE 3: project_technologies (junction table - no id column)
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE project_technologies
            ADD company_id INT NULL
        SQL);

        // Copy from projects via project_id FK
        $this->addSql(<<<'SQL'
            UPDATE project_technologies pt
            INNER JOIN projects p ON pt.project_id = p.id
            SET pt.company_id = p.company_id
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE project_technologies
            MODIFY company_id INT NOT NULL
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX idx_project_technology_company ON project_technologies (company_id)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE project_technologies
            ADD CONSTRAINT fk_project_technology_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // -----------------------------------------------------------------
        // TABLE 4: project_technology_versions
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE project_technology_versions
            ADD company_id INT NULL AFTER id
        SQL);

        // Copy from projects via project_id FK
        $this->addSql(<<<'SQL'
            UPDATE project_technology_versions ptv
            INNER JOIN projects p ON ptv.project_id = p.id
            SET ptv.company_id = p.company_id
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE project_technology_versions
            MODIFY company_id INT NOT NULL
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX idx_project_technology_version_company ON project_technology_versions (company_id)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE project_technology_versions
            ADD CONSTRAINT fk_project_technology_version_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // -----------------------------------------------------------------
        // TABLE 5: order_tasks
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE order_tasks
            ADD company_id INT NULL AFTER id
        SQL);

        // Copy from orders via order_id FK
        $this->addSql(<<<'SQL'
            UPDATE order_tasks ot
            INNER JOIN orders o ON ot.order_id = o.id
            SET ot.company_id = o.company_id
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE order_tasks
            MODIFY company_id INT NOT NULL
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX idx_order_task_company ON order_tasks (company_id)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE order_tasks
            ADD CONSTRAINT fk_order_task_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // -----------------------------------------------------------------
        // TABLE 6: running_timers
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE running_timers
            ADD company_id INT NULL AFTER id
        SQL);

        // Copy from contributors via contributor_id FK (primary)
        $this->addSql(<<<'SQL'
            UPDATE running_timers rt
            INNER JOIN contributors c ON rt.contributor_id = c.id
            SET rt.company_id = c.company_id
            WHERE rt.contributor_id IS NOT NULL
        SQL);

        // Fallback: copy from projects if no contributor
        $this->addSql(<<<'SQL'
            UPDATE running_timers rt
            INNER JOIN projects p ON rt.project_id = p.id
            SET rt.company_id = p.company_id
            WHERE rt.contributor_id IS NULL
              AND rt.project_id IS NOT NULL
              AND rt.company_id IS NULL
        SQL);

        // Remaining entries get default company
        $this->addSql(<<<'SQL'
            UPDATE running_timers
            SET company_id = 1
            WHERE company_id IS NULL
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE running_timers
            MODIFY company_id INT NOT NULL
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX idx_running_timer_company ON running_timers (company_id)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE running_timers
            ADD CONSTRAINT fk_running_timer_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // -----------------------------------------------------------------
        // TABLE 7: invoices
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE invoices
            ADD company_id INT NULL AFTER id
        SQL);

        // Copy from clients via client_id FK (primary)
        $this->addSql(<<<'SQL'
            UPDATE invoices i
            INNER JOIN clients c ON i.client_id = c.id
            SET i.company_id = c.company_id
            WHERE i.client_id IS NOT NULL
        SQL);

        // Fallback: copy from projects if no client
        $this->addSql(<<<'SQL'
            UPDATE invoices i
            INNER JOIN projects p ON i.project_id = p.id
            SET i.company_id = p.company_id
            WHERE i.client_id IS NULL
              AND i.project_id IS NOT NULL
              AND i.company_id IS NULL
        SQL);

        // Remaining entries get default company
        $this->addSql(<<<'SQL'
            UPDATE invoices
            SET company_id = 1
            WHERE company_id IS NULL
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE invoices
            MODIFY company_id INT NOT NULL
        SQL);

        // Drop old unique constraint on invoice_number
        $this->addSql(<<<'SQL'
            ALTER TABLE invoices
            DROP INDEX UNIQ_6A2F2F952DA68207
        SQL);

        // Add composite unique constraint (invoice_number, company_id)
        $this->addSql(<<<'SQL'
            ALTER TABLE invoices
            ADD CONSTRAINT invoice_number_company_unique
            UNIQUE (invoice_number, company_id)
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX idx_invoice_company ON invoices (company_id)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE invoices
            ADD CONSTRAINT fk_invoice_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // -----------------------------------------------------------------
        // TABLE 8: invoice_lines
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE invoice_lines
            ADD company_id INT NULL AFTER id
        SQL);

        // Copy from invoices via invoice_id FK
        $this->addSql(<<<'SQL'
            UPDATE invoice_lines il
            INNER JOIN invoices i ON il.invoice_id = i.id
            SET il.company_id = i.company_id
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE invoice_lines
            MODIFY company_id INT NOT NULL
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX idx_invoice_line_company ON invoice_lines (company_id)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE invoice_lines
            ADD CONSTRAINT fk_invoice_line_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // -----------------------------------------------------------------
        // TABLE 9: expense_reports
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE expense_reports
            ADD company_id INT NULL AFTER id
        SQL);

        // Copy from contributors via contributor_id FK (primary)
        $this->addSql(<<<'SQL'
            UPDATE expense_reports er
            INNER JOIN contributors c ON er.contributor_id = c.id
            SET er.company_id = c.company_id
            WHERE er.contributor_id IS NOT NULL
        SQL);

        // Fallback: copy from projects if no contributor
        $this->addSql(<<<'SQL'
            UPDATE expense_reports er
            INNER JOIN projects p ON er.project_id = p.id
            SET er.company_id = p.company_id
            WHERE er.contributor_id IS NULL
              AND er.project_id IS NOT NULL
              AND er.company_id IS NULL
        SQL);

        // Remaining entries get default company
        $this->addSql(<<<'SQL'
            UPDATE expense_reports
            SET company_id = 1
            WHERE company_id IS NULL
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE expense_reports
            MODIFY company_id INT NOT NULL
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX idx_expense_report_company ON expense_reports (company_id)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE expense_reports
            ADD CONSTRAINT fk_expense_report_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // ===================================================================
        // BATCH 7B: HR & EMPLOYMENT (3 tables)
        // ===================================================================

        // -----------------------------------------------------------------
        // TABLE 10: employment_period_profiles (junction table - no id column)
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE employment_period_profiles
            ADD company_id INT NULL
        SQL);

        // Copy from employment_periods via employment_period_id FK
        $this->addSql(<<<'SQL'
            UPDATE employment_period_profiles epp
            INNER JOIN employment_periods ep ON epp.employment_period_id = ep.id
            SET epp.company_id = ep.company_id
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE employment_period_profiles
            MODIFY company_id INT NOT NULL
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX idx_employment_period_profile_company ON employment_period_profiles (company_id)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE employment_period_profiles
            ADD CONSTRAINT fk_employment_period_profile_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // -----------------------------------------------------------------
        // TABLE 11: contributor_profiles (junction table - no id column)
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE contributor_profiles
            ADD company_id INT NULL
        SQL);

        // Copy from contributors via contributor_id FK
        $this->addSql(<<<'SQL'
            UPDATE contributor_profiles cp
            INNER JOIN contributors c ON cp.contributor_id = c.id
            SET cp.company_id = c.company_id
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE contributor_profiles
            MODIFY company_id INT NOT NULL
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX idx_contributor_profile_company ON contributor_profiles (company_id)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE contributor_profiles
            ADD CONSTRAINT fk_contributor_profile_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // -----------------------------------------------------------------
        // TABLE 12: performance_reviews
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE performance_reviews
            ADD company_id INT NULL AFTER id
        SQL);

        // Copy from contributors via contributor_id FK
        $this->addSql(<<<'SQL'
            UPDATE performance_reviews pr
            INNER JOIN contributors c ON pr.contributor_id = c.id
            SET pr.company_id = c.company_id
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE performance_reviews
            MODIFY company_id INT NOT NULL
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX idx_performance_review_company ON performance_reviews (company_id)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE performance_reviews
            ADD CONSTRAINT fk_performance_review_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // ===================================================================
        // BATCH 7C: SAAS & SUBSCRIPTIONS (7 tables)
        // ===================================================================

        // -----------------------------------------------------------------
        // TABLE 13: saas_providers
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE saas_providers
            ADD company_id INT NULL AFTER id
        SQL);

        // All to default company (no FK)
        $this->addSql(<<<'SQL'
            UPDATE saas_providers
            SET company_id = 1
            WHERE company_id IS NULL
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE saas_providers
            MODIFY company_id INT NOT NULL
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX idx_saas_provider_company ON saas_providers (company_id)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE saas_providers
            ADD CONSTRAINT fk_saas_provider_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // -----------------------------------------------------------------
        // TABLE 14: saas_services
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE saas_services
            ADD company_id INT NULL AFTER id
        SQL);

        // Copy from saas_providers via provider_id FK
        $this->addSql(<<<'SQL'
            UPDATE saas_services ss
            INNER JOIN saas_providers sp ON ss.provider_id = sp.id
            SET ss.company_id = sp.company_id
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE saas_services
            MODIFY company_id INT NOT NULL
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX idx_saas_service_company ON saas_services (company_id)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE saas_services
            ADD CONSTRAINT fk_saas_service_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // -----------------------------------------------------------------
        // TABLE 15: saas_subscriptions
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE saas_subscriptions
            ADD company_id INT NULL AFTER id
        SQL);

        // Copy from saas_services via service_id FK
        $this->addSql(<<<'SQL'
            UPDATE saas_subscriptions ss
            INNER JOIN saas_services s ON ss.service_id = s.id
            SET ss.company_id = s.company_id
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE saas_subscriptions
            MODIFY company_id INT NOT NULL
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX idx_saas_subscription_company ON saas_subscriptions (company_id)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE saas_subscriptions
            ADD CONSTRAINT fk_saas_subscription_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // -----------------------------------------------------------------
        // TABLE 16: saas_vendors
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE saas_vendors
            ADD company_id INT NULL AFTER id
        SQL);

        // All to default company (no FK)
        $this->addSql(<<<'SQL'
            UPDATE saas_vendors
            SET company_id = 1
            WHERE company_id IS NULL
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE saas_vendors
            MODIFY company_id INT NOT NULL
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX idx_saas_vendor_company ON saas_vendors (company_id)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE saas_vendors
            ADD CONSTRAINT fk_saas_vendor_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // -----------------------------------------------------------------
        // TABLE 17: saas_distribution_providers
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE saas_distribution_providers
            ADD company_id INT NULL AFTER id
        SQL);

        // All to default company (no FK)
        $this->addSql(<<<'SQL'
            UPDATE saas_distribution_providers
            SET company_id = 1
            WHERE company_id IS NULL
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE saas_distribution_providers
            MODIFY company_id INT NOT NULL
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX idx_saas_distribution_provider_company ON saas_distribution_providers (company_id)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE saas_distribution_providers
            ADD CONSTRAINT fk_saas_distribution_provider_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // -----------------------------------------------------------------
        // TABLE 18: saas_subscriptions_v2
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE saas_subscriptions_v2
            ADD company_id INT NULL AFTER id
        SQL);

        // Copy from saas_vendors via vendor_id FK (primary)
        $this->addSql(<<<'SQL'
            UPDATE saas_subscriptions_v2 ssv2
            INNER JOIN saas_vendors sv ON ssv2.vendor_id = sv.id
            SET ssv2.company_id = sv.company_id
            WHERE ssv2.vendor_id IS NOT NULL
        SQL);

        // Fallback: copy from saas_distribution_providers if no vendor
        $this->addSql(<<<'SQL'
            UPDATE saas_subscriptions_v2 ssv2
            INNER JOIN saas_distribution_providers sdp ON ssv2.provider_id = sdp.id
            SET ssv2.company_id = sdp.company_id
            WHERE ssv2.vendor_id IS NULL
              AND ssv2.provider_id IS NOT NULL
              AND ssv2.company_id IS NULL
        SQL);

        // Remaining entries get default company
        $this->addSql(<<<'SQL'
            UPDATE saas_subscriptions_v2
            SET company_id = 1
            WHERE company_id IS NULL
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE saas_subscriptions_v2
            MODIFY company_id INT NOT NULL
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX idx_saas_subscription_v2_company ON saas_subscriptions_v2 (company_id)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE saas_subscriptions_v2
            ADD CONSTRAINT fk_saas_subscription_v2_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // -----------------------------------------------------------------
        // TABLE 19: billing_markers
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE billing_markers
            ADD company_id INT NULL AFTER id
        SQL);

        // Copy from orders via order_id FK
        $this->addSql(<<<'SQL'
            UPDATE billing_markers bm
            INNER JOIN orders o ON bm.order_id = o.id
            SET bm.company_id = o.company_id
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE billing_markers
            MODIFY company_id INT NOT NULL
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX idx_billing_marker_company ON billing_markers (company_id)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE billing_markers
            ADD CONSTRAINT fk_billing_marker_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // ===================================================================
        // BATCH 7D: NOTIFICATIONS (3 tables)
        // ===================================================================

        // -----------------------------------------------------------------
        // TABLE 20: notifications
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE notifications
            ADD company_id INT NULL AFTER id
        SQL);

        // Copy from users via recipient_id FK
        $this->addSql(<<<'SQL'
            UPDATE notifications n
            INNER JOIN users u ON n.recipient_id = u.id
            SET n.company_id = u.company_id
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE notifications
            MODIFY company_id INT NOT NULL
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX idx_notification_company ON notifications (company_id)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE notifications
            ADD CONSTRAINT fk_notification_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // -----------------------------------------------------------------
        // TABLE 21: notification_preferences
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE notification_preferences
            ADD company_id INT NULL AFTER id
        SQL);

        // Copy from users via user_id FK
        $this->addSql(<<<'SQL'
            UPDATE notification_preferences np
            INNER JOIN users u ON np.user_id = u.id
            SET np.company_id = u.company_id
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE notification_preferences
            MODIFY company_id INT NOT NULL
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX idx_notification_preference_company ON notification_preferences (company_id)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE notification_preferences
            ADD CONSTRAINT fk_notification_preference_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // -----------------------------------------------------------------
        // TABLE 22: notification_settings
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE notification_settings
            ADD company_id INT NULL AFTER id
        SQL);

        // All to default company (global settings)
        $this->addSql(<<<'SQL'
            UPDATE notification_settings
            SET company_id = 1
            WHERE company_id IS NULL
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE notification_settings
            MODIFY company_id INT NOT NULL
        SQL);

        // Drop old unique constraint on setting_key
        $this->addSql(<<<'SQL'
            ALTER TABLE notification_settings
            DROP INDEX UNIQ_B05598605FA1E697
        SQL);

        // Add composite unique constraint (setting_key, company_id)
        $this->addSql(<<<'SQL'
            ALTER TABLE notification_settings
            ADD CONSTRAINT setting_key_company_unique
            UNIQUE (setting_key, company_id)
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX idx_notification_setting_company ON notification_settings (company_id)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE notification_settings
            ADD CONSTRAINT fk_notification_setting_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // ===================================================================
        // REVERSE BATCH 7D: NOTIFICATIONS (3 tables)
        // ===================================================================

        // -----------------------------------------------------------------
        // REVERSE TABLE 22: notification_settings
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE notification_settings
            DROP FOREIGN KEY fk_notification_setting_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_notification_setting_company ON notification_settings
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE notification_settings
            DROP INDEX setting_key_company_unique
        SQL);

        // Restore original unique constraint on setting_key
        $this->addSql(<<<'SQL'
            ALTER TABLE notification_settings
            ADD CONSTRAINT UNIQ_B05598605FA1E697
            UNIQUE (setting_key)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE notification_settings
            DROP COLUMN company_id
        SQL);

        // -----------------------------------------------------------------
        // REVERSE TABLE 21: notification_preferences
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE notification_preferences
            DROP FOREIGN KEY fk_notification_preference_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_notification_preference_company ON notification_preferences
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE notification_preferences
            DROP COLUMN company_id
        SQL);

        // -----------------------------------------------------------------
        // REVERSE TABLE 20: notifications
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE notifications
            DROP FOREIGN KEY fk_notification_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_notification_company ON notifications
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE notifications
            DROP COLUMN company_id
        SQL);

        // ===================================================================
        // REVERSE BATCH 7C: SAAS & SUBSCRIPTIONS (7 tables)
        // ===================================================================

        // -----------------------------------------------------------------
        // REVERSE TABLE 19: billing_markers
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE billing_markers
            DROP FOREIGN KEY fk_billing_marker_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_billing_marker_company ON billing_markers
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE billing_markers
            DROP COLUMN company_id
        SQL);

        // -----------------------------------------------------------------
        // REVERSE TABLE 18: saas_subscriptions_v2
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE saas_subscriptions_v2
            DROP FOREIGN KEY fk_saas_subscription_v2_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_saas_subscription_v2_company ON saas_subscriptions_v2
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE saas_subscriptions_v2
            DROP COLUMN company_id
        SQL);

        // -----------------------------------------------------------------
        // REVERSE TABLE 17: saas_distribution_providers
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE saas_distribution_providers
            DROP FOREIGN KEY fk_saas_distribution_provider_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_saas_distribution_provider_company ON saas_distribution_providers
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE saas_distribution_providers
            DROP COLUMN company_id
        SQL);

        // -----------------------------------------------------------------
        // REVERSE TABLE 16: saas_vendors
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE saas_vendors
            DROP FOREIGN KEY fk_saas_vendor_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_saas_vendor_company ON saas_vendors
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE saas_vendors
            DROP COLUMN company_id
        SQL);

        // -----------------------------------------------------------------
        // REVERSE TABLE 15: saas_subscriptions
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE saas_subscriptions
            DROP FOREIGN KEY fk_saas_subscription_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_saas_subscription_company ON saas_subscriptions
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE saas_subscriptions
            DROP COLUMN company_id
        SQL);

        // -----------------------------------------------------------------
        // REVERSE TABLE 14: saas_services
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE saas_services
            DROP FOREIGN KEY fk_saas_service_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_saas_service_company ON saas_services
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE saas_services
            DROP COLUMN company_id
        SQL);

        // -----------------------------------------------------------------
        // REVERSE TABLE 13: saas_providers
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE saas_providers
            DROP FOREIGN KEY fk_saas_provider_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_saas_provider_company ON saas_providers
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE saas_providers
            DROP COLUMN company_id
        SQL);

        // ===================================================================
        // REVERSE BATCH 7B: HR & EMPLOYMENT (3 tables)
        // ===================================================================

        // -----------------------------------------------------------------
        // REVERSE TABLE 12: performance_reviews
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE performance_reviews
            DROP FOREIGN KEY fk_performance_review_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_performance_review_company ON performance_reviews
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE performance_reviews
            DROP COLUMN company_id
        SQL);

        // -----------------------------------------------------------------
        // REVERSE TABLE 11: contributor_profiles
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE contributor_profiles
            DROP FOREIGN KEY fk_contributor_profile_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_contributor_profile_company ON contributor_profiles
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE contributor_profiles
            DROP COLUMN company_id
        SQL);

        // -----------------------------------------------------------------
        // REVERSE TABLE 10: employment_period_profiles
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE employment_period_profiles
            DROP FOREIGN KEY fk_employment_period_profile_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_employment_period_profile_company ON employment_period_profiles
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE employment_period_profiles
            DROP COLUMN company_id
        SQL);

        // ===================================================================
        // REVERSE BATCH 7A: PROJECT & FINANCE (9 tables)
        // ===================================================================

        // -----------------------------------------------------------------
        // REVERSE TABLE 9: expense_reports
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE expense_reports
            DROP FOREIGN KEY fk_expense_report_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_expense_report_company ON expense_reports
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE expense_reports
            DROP COLUMN company_id
        SQL);

        // -----------------------------------------------------------------
        // REVERSE TABLE 8: invoice_lines
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE invoice_lines
            DROP FOREIGN KEY fk_invoice_line_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_invoice_line_company ON invoice_lines
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE invoice_lines
            DROP COLUMN company_id
        SQL);

        // -----------------------------------------------------------------
        // REVERSE TABLE 7: invoices
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE invoices
            DROP FOREIGN KEY fk_invoice_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_invoice_company ON invoices
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE invoices
            DROP INDEX invoice_number_company_unique
        SQL);

        // Restore original unique constraint on invoice_number
        $this->addSql(<<<'SQL'
            ALTER TABLE invoices
            ADD CONSTRAINT UNIQ_6A2F2F952DA68207
            UNIQUE (invoice_number)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE invoices
            DROP COLUMN company_id
        SQL);

        // -----------------------------------------------------------------
        // REVERSE TABLE 6: running_timers
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE running_timers
            DROP FOREIGN KEY fk_running_timer_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_running_timer_company ON running_timers
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE running_timers
            DROP COLUMN company_id
        SQL);

        // -----------------------------------------------------------------
        // REVERSE TABLE 5: order_tasks
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE order_tasks
            DROP FOREIGN KEY fk_order_task_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_order_task_company ON order_tasks
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE order_tasks
            DROP COLUMN company_id
        SQL);

        // -----------------------------------------------------------------
        // REVERSE TABLE 4: project_technology_versions
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE project_technology_versions
            DROP FOREIGN KEY fk_project_technology_version_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_project_technology_version_company ON project_technology_versions
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE project_technology_versions
            DROP COLUMN company_id
        SQL);

        // -----------------------------------------------------------------
        // REVERSE TABLE 3: project_technologies
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE project_technologies
            DROP FOREIGN KEY fk_project_technology_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_project_technology_company ON project_technologies
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE project_technologies
            DROP COLUMN company_id
        SQL);

        // -----------------------------------------------------------------
        // REVERSE TABLE 2: project_health_score
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE project_health_score
            DROP FOREIGN KEY fk_project_health_score_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_project_health_score_company ON project_health_score
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE project_health_score
            DROP COLUMN company_id
        SQL);

        // -----------------------------------------------------------------
        // REVERSE TABLE 1: project_events
        // -----------------------------------------------------------------

        $this->addSql(<<<'SQL'
            ALTER TABLE project_events
            DROP FOREIGN KEY fk_project_event_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_project_event_company ON project_events
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE project_events
            DROP COLUMN company_id
        SQL);
    }
}
