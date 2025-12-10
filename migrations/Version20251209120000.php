<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Phase 2 Analytics: FactForecast and ProjectHealthScore tables
 * Also fixes schema alignment for expense_reports and employment_periods
 */
final class Version20251209120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates fact_forecast and project_health_score tables for Phase 2 analytics';
    }

    public function up(Schema $schema): void
    {
        // Create fact_forecast table
        $this->addSql('CREATE TABLE fact_forecast (
            id INT AUTO_INCREMENT NOT NULL,
            period_start DATE NOT NULL,
            period_end DATE NOT NULL,
            scenario VARCHAR(20) NOT NULL,
            predicted_revenue NUMERIC(10, 2) NOT NULL,
            confidence_min NUMERIC(10, 2) DEFAULT NULL,
            confidence_max NUMERIC(10, 2) DEFAULT NULL,
            actual_revenue NUMERIC(10, 2) DEFAULT NULL,
            accuracy NUMERIC(5, 2) DEFAULT NULL,
            metadata JSON DEFAULT NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_period (period_start, period_end),
            INDEX idx_scenario (scenario),
            INDEX idx_created_at (created_at),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Create project_health_score table
        $this->addSql('CREATE TABLE project_health_score (
            id INT AUTO_INCREMENT NOT NULL,
            project_id INT NOT NULL,
            score SMALLINT NOT NULL,
            health_level VARCHAR(20) NOT NULL,
            budget_score SMALLINT NOT NULL,
            timeline_score SMALLINT NOT NULL,
            velocity_score SMALLINT NOT NULL,
            quality_score SMALLINT NOT NULL,
            recommendations JSON DEFAULT NULL,
            details JSON DEFAULT NULL,
            calculated_at DATETIME NOT NULL,
            INDEX IDX_43FDF8F8166D1F9C (project_id),
            INDEX idx_project_date (project_id, calculated_at),
            INDEX idx_health_level (health_level),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Add foreign key constraint for project_health_score
        $this->addSql('ALTER TABLE project_health_score ADD CONSTRAINT FK_43FDF8F8166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE');

        // Fix index names on expense_reports (align with Doctrine expectations)
        $this->addSql('ALTER TABLE expense_reports DROP INDEX IDX_9BFFDAB77A19A357');
        $this->addSql('ALTER TABLE expense_reports ADD INDEX IDX_9C04EC7F7A19A357 (contributor_id)');
        $this->addSql('DROP INDEX idx_expense_status ON expense_reports');
        $this->addSql('DROP INDEX idx_expense_date ON expense_reports');
        $this->addSql('DROP INDEX idx_expense_contributor ON expense_reports');
        $this->addSql('ALTER TABLE expense_reports RENAME INDEX idx_9bffdab7166d1f9c TO IDX_9C04EC7F166D1F9C');
        $this->addSql('ALTER TABLE expense_reports RENAME INDEX idx_9bffdab78d9f6d38 TO IDX_9C04EC7F8D9F6D38');
        $this->addSql('ALTER TABLE expense_reports RENAME INDEX idx_9bffdab7bbe10c72 TO IDX_9C04EC7FB0644AEC');

        // Fix employment_periods column defaults
        $this->addSql('ALTER TABLE employment_periods
            CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT 35 NOT NULL,
            CHANGE work_time_percentage work_time_percentage NUMERIC(5, 2) DEFAULT 100 NOT NULL'
        );
    }

    public function down(Schema $schema): void
    {
        // Drop new tables
        $this->addSql('ALTER TABLE project_health_score DROP FOREIGN KEY FK_43FDF8F8166D1F9C');
        $this->addSql('DROP TABLE project_health_score');
        $this->addSql('DROP TABLE fact_forecast');

        // Revert expense_reports index changes
        $this->addSql('ALTER TABLE expense_reports DROP INDEX IDX_9C04EC7F7A19A357');
        $this->addSql('ALTER TABLE expense_reports ADD INDEX IDX_9BFFDAB77A19A357 (contributor_id)');
        $this->addSql('CREATE INDEX idx_expense_status ON expense_reports (status)');
        $this->addSql('CREATE INDEX idx_expense_date ON expense_reports (expense_date)');
        $this->addSql('CREATE INDEX idx_expense_contributor ON expense_reports (contributor_id)');
        $this->addSql('ALTER TABLE expense_reports RENAME INDEX IDX_9C04EC7F166D1F9C TO idx_9bffdab7166d1f9c');
        $this->addSql('ALTER TABLE expense_reports RENAME INDEX IDX_9C04EC7F8D9F6D38 TO idx_9bffdab78d9f6d38');
        $this->addSql('ALTER TABLE expense_reports RENAME INDEX IDX_9C04EC7FB0644AEC TO idx_9bffdab7bbe10c72');

        // Revert employment_periods defaults
        $this->addSql('ALTER TABLE employment_periods
            CHANGE weekly_hours weekly_hours NUMERIC(5, 2) NOT NULL,
            CHANGE work_time_percentage work_time_percentage NUMERIC(5, 2) NOT NULL'
        );
    }
}
