<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Lot 23 - Migration 8: Add company_id to Batch 6 (Analytics)
 *
 * This migration adds company_id to analytics tables (star schema):
 *
 * Dimension tables (dim_*):
 * - dim_time (temporal dimension - all to default company)
 * - dim_contributor (copy from users via user_id, or default)
 * - dim_profile (copy from profiles via profile_id, or default)
 * - dim_project_type (no FK - all to default company)
 *
 * Fact tables (fact_*):
 * - fact_project_metrics (copy from projects OR orders, or default)
 * - fact_staffing_metrics (copy from contributors via contributor_id, or default)
 * - fact_forecast (no FK - all to default company)
 *
 * REVERSIBLE: down() removes company_id from all tables
 */
final class Version20251231125836 extends AbstractMigration
{
    private const DEFAULT_COMPANY_ID = 1;

    public function getDescription(): string
    {
        return 'Lot 23 - Add company_id to analytics tables (fact_*, dim_*)';
    }

    public function up(Schema $schema): void
    {
        // ===================================================================
        // DIMENSION TABLE 1: dim_time
        // ===================================================================

        $this->addSql(<<<'SQL'
            ALTER TABLE dim_time
            ADD company_id INT NULL AFTER id
        SQL);

        $this->addSql(<<<'SQL'
            UPDATE dim_time
            SET company_id = 1
            WHERE company_id IS NULL
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE dim_time
            MODIFY company_id INT NOT NULL
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX idx_dim_time_company ON dim_time (company_id)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE dim_time
            ADD CONSTRAINT fk_dim_time_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // ===================================================================
        // DIMENSION TABLE 2: dim_contributor
        // ===================================================================

        $this->addSql(<<<'SQL'
            ALTER TABLE dim_contributor
            ADD company_id INT NULL AFTER id
        SQL);

        // Copy from users (via user_id FK)
        $this->addSql(<<<'SQL'
            UPDATE dim_contributor dc
            INNER JOIN users u ON dc.user_id = u.id
            SET dc.company_id = u.company_id
            WHERE dc.user_id IS NOT NULL
        SQL);

        // Entries without user_id get default company
        $this->addSql(<<<'SQL'
            UPDATE dim_contributor
            SET company_id = 1
            WHERE company_id IS NULL
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE dim_contributor
            MODIFY company_id INT NOT NULL
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX idx_dim_contributor_company ON dim_contributor (company_id)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE dim_contributor
            ADD CONSTRAINT fk_dim_contributor_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // ===================================================================
        // DIMENSION TABLE 3: dim_profile
        // ===================================================================

        $this->addSql(<<<'SQL'
            ALTER TABLE dim_profile
            ADD company_id INT NULL AFTER id
        SQL);

        // Copy from profiles (via profile_id FK)
        $this->addSql(<<<'SQL'
            UPDATE dim_profile dp
            INNER JOIN profiles p ON dp.profile_id = p.id
            SET dp.company_id = p.company_id
            WHERE dp.profile_id IS NOT NULL
        SQL);

        // Entries without profile_id get default company
        $this->addSql(<<<'SQL'
            UPDATE dim_profile
            SET company_id = 1
            WHERE company_id IS NULL
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE dim_profile
            MODIFY company_id INT NOT NULL
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX idx_dim_profile_company ON dim_profile (company_id)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE dim_profile
            ADD CONSTRAINT fk_dim_profile_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // ===================================================================
        // DIMENSION TABLE 4: dim_project_type
        // ===================================================================

        $this->addSql(<<<'SQL'
            ALTER TABLE dim_project_type
            ADD company_id INT NULL AFTER id
        SQL);

        $this->addSql(<<<'SQL'
            UPDATE dim_project_type
            SET company_id = 1
            WHERE company_id IS NULL
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE dim_project_type
            MODIFY company_id INT NOT NULL
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX idx_dim_project_type_company ON dim_project_type (company_id)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE dim_project_type
            ADD CONSTRAINT fk_dim_project_type_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // ===================================================================
        // FACT TABLE 1: fact_project_metrics
        // ===================================================================

        $this->addSql(<<<'SQL'
            ALTER TABLE fact_project_metrics
            ADD company_id INT NULL AFTER id
        SQL);

        // Copy from projects (via project_id FK)
        $this->addSql(<<<'SQL'
            UPDATE fact_project_metrics fpm
            INNER JOIN projects p ON fpm.project_id = p.id
            SET fpm.company_id = p.company_id
            WHERE fpm.project_id IS NOT NULL
        SQL);

        // If no project, try orders (via order_id FK)
        $this->addSql(<<<'SQL'
            UPDATE fact_project_metrics fpm
            INNER JOIN orders o ON fpm.order_id = o.id
            SET fpm.company_id = o.company_id
            WHERE fpm.project_id IS NULL
              AND fpm.order_id IS NOT NULL
              AND fpm.company_id IS NULL
        SQL);

        // Remaining entries get default company
        $this->addSql(<<<'SQL'
            UPDATE fact_project_metrics
            SET company_id = 1
            WHERE company_id IS NULL
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE fact_project_metrics
            MODIFY company_id INT NOT NULL
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX idx_fact_project_metrics_company ON fact_project_metrics (company_id)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE fact_project_metrics
            ADD CONSTRAINT fk_fact_project_metrics_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // ===================================================================
        // FACT TABLE 2: fact_staffing_metrics
        // ===================================================================

        $this->addSql(<<<'SQL'
            ALTER TABLE fact_staffing_metrics
            ADD company_id INT NULL AFTER id
        SQL);

        // Copy from contributors (via contributor_id FK)
        $this->addSql(<<<'SQL'
            UPDATE fact_staffing_metrics fsm
            INNER JOIN contributors c ON fsm.contributor_id = c.id
            SET fsm.company_id = c.company_id
            WHERE fsm.contributor_id IS NOT NULL
        SQL);

        // Entries without contributor_id get default company
        $this->addSql(<<<'SQL'
            UPDATE fact_staffing_metrics
            SET company_id = 1
            WHERE company_id IS NULL
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE fact_staffing_metrics
            MODIFY company_id INT NOT NULL
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX idx_fact_staffing_metrics_company ON fact_staffing_metrics (company_id)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE fact_staffing_metrics
            ADD CONSTRAINT fk_fact_staffing_metrics_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // ===================================================================
        // FACT TABLE 3: fact_forecast
        // ===================================================================

        $this->addSql(<<<'SQL'
            ALTER TABLE fact_forecast
            ADD company_id INT NULL AFTER id
        SQL);

        $this->addSql(<<<'SQL'
            UPDATE fact_forecast
            SET company_id = 1
            WHERE company_id IS NULL
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE fact_forecast
            MODIFY company_id INT NOT NULL
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX idx_fact_forecast_company ON fact_forecast (company_id)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE fact_forecast
            ADD CONSTRAINT fk_fact_forecast_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // ===================================================================
        // REVERSE FACT TABLE 3: fact_forecast
        // ===================================================================

        $this->addSql(<<<'SQL'
            ALTER TABLE fact_forecast
            DROP FOREIGN KEY fk_fact_forecast_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_fact_forecast_company ON fact_forecast
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE fact_forecast
            DROP COLUMN company_id
        SQL);

        // ===================================================================
        // REVERSE FACT TABLE 2: fact_staffing_metrics
        // ===================================================================

        $this->addSql(<<<'SQL'
            ALTER TABLE fact_staffing_metrics
            DROP FOREIGN KEY fk_fact_staffing_metrics_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_fact_staffing_metrics_company ON fact_staffing_metrics
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE fact_staffing_metrics
            DROP COLUMN company_id
        SQL);

        // ===================================================================
        // REVERSE FACT TABLE 1: fact_project_metrics
        // ===================================================================

        $this->addSql(<<<'SQL'
            ALTER TABLE fact_project_metrics
            DROP FOREIGN KEY fk_fact_project_metrics_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_fact_project_metrics_company ON fact_project_metrics
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE fact_project_metrics
            DROP COLUMN company_id
        SQL);

        // ===================================================================
        // REVERSE DIMENSION TABLE 4: dim_project_type
        // ===================================================================

        $this->addSql(<<<'SQL'
            ALTER TABLE dim_project_type
            DROP FOREIGN KEY fk_dim_project_type_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_dim_project_type_company ON dim_project_type
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE dim_project_type
            DROP COLUMN company_id
        SQL);

        // ===================================================================
        // REVERSE DIMENSION TABLE 3: dim_profile
        // ===================================================================

        $this->addSql(<<<'SQL'
            ALTER TABLE dim_profile
            DROP FOREIGN KEY fk_dim_profile_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_dim_profile_company ON dim_profile
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE dim_profile
            DROP COLUMN company_id
        SQL);

        // ===================================================================
        // REVERSE DIMENSION TABLE 2: dim_contributor
        // ===================================================================

        $this->addSql(<<<'SQL'
            ALTER TABLE dim_contributor
            DROP FOREIGN KEY fk_dim_contributor_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_dim_contributor_company ON dim_contributor
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE dim_contributor
            DROP COLUMN company_id
        SQL);

        // ===================================================================
        // REVERSE DIMENSION TABLE 1: dim_time
        // ===================================================================

        $this->addSql(<<<'SQL'
            ALTER TABLE dim_time
            DROP FOREIGN KEY fk_dim_time_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_dim_time_company ON dim_time
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE dim_time
            DROP COLUMN company_id
        SQL);
    }
}
