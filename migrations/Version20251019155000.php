<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration simplifiée pour créer les tables analytics
 */
final class Version20251019155000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée les tables analytics avec requêtes simplifiées';
    }

    public function up(Schema $schema): void
    {
        // Create dim_time table
        $this->addSql('CREATE TABLE dim_time (id INT AUTO_INCREMENT NOT NULL, year_value INT NOT NULL, month_value INT DEFAULT NULL, quarter_value INT DEFAULT NULL, week_value INT DEFAULT NULL, date_value DATE DEFAULT NULL, is_current TINYINT NOT NULL, composite_key VARCHAR(50) NOT NULL, UNIQUE INDEX UNIQ_DE4FBD6613775659 (composite_key), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Create dimension tables
        $this->addSql('CREATE TABLE dim_contributor (id INT AUTO_INCREMENT NOT NULL, name_value VARCHAR(180) NOT NULL, role_value VARCHAR(50) NOT NULL, is_active TINYINT NOT NULL, composite_key VARCHAR(250) NOT NULL, user_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_8BC20A2C13775659 (composite_key), INDEX IDX_8BC20A2CA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE dim_profile (id INT AUTO_INCREMENT NOT NULL, name_value VARCHAR(100) NOT NULL, is_productive TINYINT NOT NULL, is_active TINYINT NOT NULL, composite_key VARCHAR(150) NOT NULL, profile_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_9B69890413775659 (composite_key), INDEX IDX_9B698904CCFA12B8 (profile_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE dim_project_type (id INT AUTO_INCREMENT NOT NULL, project_type VARCHAR(20) NOT NULL, service_category VARCHAR(50) DEFAULT NULL, status_value VARCHAR(20) NOT NULL, is_internal TINYINT NOT NULL, composite_key VARCHAR(150) NOT NULL, UNIQUE INDEX UNIQ_EEC09CB113775659 (composite_key), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Create fact_project_metrics table (without unique index - will be added in later migration)
        $this->addSql('CREATE TABLE fact_project_metrics (id INT AUTO_INCREMENT NOT NULL, project_count INT NOT NULL, active_project_count INT NOT NULL, completed_project_count INT NOT NULL, order_count INT NOT NULL, pending_order_count INT NOT NULL, won_order_count INT NOT NULL, contributor_count INT NOT NULL, total_revenue NUMERIC(15, 2) NOT NULL, total_costs NUMERIC(15, 2) NOT NULL, gross_margin NUMERIC(15, 2) NOT NULL, margin_percentage NUMERIC(5, 2) NOT NULL, pending_revenue NUMERIC(15, 2) NOT NULL, average_order_value NUMERIC(15, 2) NOT NULL, total_sold_days NUMERIC(10, 2) NOT NULL, total_worked_days NUMERIC(10, 2) NOT NULL, utilization_rate NUMERIC(5, 2) NOT NULL, calculated_at DATETIME NOT NULL, granularity VARCHAR(50) NOT NULL, dim_time_id INT NOT NULL, dim_project_type_id INT NOT NULL, dim_project_manager_id INT DEFAULT NULL, dim_sales_person_id INT DEFAULT NULL, dim_project_director_id INT DEFAULT NULL, project_id INT DEFAULT NULL, order_id INT DEFAULT NULL, INDEX IDX_27991A9444D4FE30 (dim_time_id), INDEX IDX_27991A94E44D565F (dim_project_type_id), INDEX IDX_27991A94EC5B4665 (dim_project_manager_id), INDEX IDX_27991A94AA2A35A7 (dim_sales_person_id), INDEX IDX_27991A9460687321 (dim_project_director_id), INDEX IDX_27991A94166D1F9C (project_id), INDEX IDX_27991A948D9F6D38 (order_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Create fact_staffing_metrics table
        $this->addSql('CREATE TABLE fact_staffing_metrics (id INT AUTO_INCREMENT NOT NULL, available_days NUMERIC(10, 2) NOT NULL, worked_days NUMERIC(10, 2) NOT NULL, staffed_days NUMERIC(10, 2) NOT NULL, vacation_days NUMERIC(10, 2) NOT NULL, planned_days NUMERIC(10, 2) NOT NULL, staffing_rate NUMERIC(5, 2) NOT NULL, tace NUMERIC(5, 2) NOT NULL, calculated_at DATETIME NOT NULL, granularity VARCHAR(50) NOT NULL, contributor_count INT NOT NULL, dim_time_id INT NOT NULL, dim_profile_id INT DEFAULT NULL, contributor_id INT DEFAULT NULL, INDEX IDX_F58C0D4844D4FE30 (dim_time_id), INDEX IDX_F58C0D48BF679789 (dim_profile_id), INDEX IDX_F58C0D487A19A357 (contributor_id), UNIQUE INDEX unique_staffing_metrics (dim_time_id, dim_profile_id, contributor_id, granularity), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Add foreign key constraints
        $this->addSql('ALTER TABLE dim_contributor ADD CONSTRAINT FK_8BC20A2CA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE dim_profile ADD CONSTRAINT FK_9B698904CCFA12B8 FOREIGN KEY (profile_id) REFERENCES profiles (id)');

        $this->addSql('ALTER TABLE fact_project_metrics ADD CONSTRAINT FK_27991A9444D4FE30 FOREIGN KEY (dim_time_id) REFERENCES dim_time (id)');
        $this->addSql('ALTER TABLE fact_project_metrics ADD CONSTRAINT FK_27991A94E44D565F FOREIGN KEY (dim_project_type_id) REFERENCES dim_project_type (id)');
        $this->addSql('ALTER TABLE fact_project_metrics ADD CONSTRAINT FK_27991A94EC5B4665 FOREIGN KEY (dim_project_manager_id) REFERENCES dim_contributor (id)');
        $this->addSql('ALTER TABLE fact_project_metrics ADD CONSTRAINT FK_27991A94AA2A35A7 FOREIGN KEY (dim_sales_person_id) REFERENCES dim_contributor (id)');
        $this->addSql('ALTER TABLE fact_project_metrics ADD CONSTRAINT FK_27991A9460687321 FOREIGN KEY (dim_project_director_id) REFERENCES dim_contributor (id)');
        $this->addSql('ALTER TABLE fact_project_metrics ADD CONSTRAINT FK_27991A94166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id)');
        $this->addSql('ALTER TABLE fact_project_metrics ADD CONSTRAINT FK_27991A948D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id)');

        $this->addSql('ALTER TABLE fact_staffing_metrics ADD CONSTRAINT FK_F58C0D4844D4FE30 FOREIGN KEY (dim_time_id) REFERENCES dim_time (id)');
        $this->addSql('ALTER TABLE fact_staffing_metrics ADD CONSTRAINT FK_F58C0D48BF679789 FOREIGN KEY (dim_profile_id) REFERENCES dim_profile (id)');
        $this->addSql('ALTER TABLE fact_staffing_metrics ADD CONSTRAINT FK_F58C0D487A19A357 FOREIGN KEY (contributor_id) REFERENCES contributors (id)');
    }

    public function down(Schema $schema): void
    {
        // Drop tables in reverse order (respecting foreign key constraints)
        $this->addSql('ALTER TABLE fact_staffing_metrics DROP FOREIGN KEY FK_F58C0D4844D4FE30');
        $this->addSql('ALTER TABLE fact_staffing_metrics DROP FOREIGN KEY FK_F58C0D48BF679789');
        $this->addSql('ALTER TABLE fact_staffing_metrics DROP FOREIGN KEY FK_F58C0D487A19A357');

        $this->addSql('ALTER TABLE fact_project_metrics DROP FOREIGN KEY FK_27991A9444D4FE30');
        $this->addSql('ALTER TABLE fact_project_metrics DROP FOREIGN KEY FK_27991A94E44D565F');
        $this->addSql('ALTER TABLE fact_project_metrics DROP FOREIGN KEY FK_27991A94EC5B4665');
        $this->addSql('ALTER TABLE fact_project_metrics DROP FOREIGN KEY FK_27991A94AA2A35A7');
        $this->addSql('ALTER TABLE fact_project_metrics DROP FOREIGN KEY FK_27991A9460687321');
        $this->addSql('ALTER TABLE fact_project_metrics DROP FOREIGN KEY FK_27991A94166D1F9C');
        $this->addSql('ALTER TABLE fact_project_metrics DROP FOREIGN KEY FK_27991A948D9F6D38');

        $this->addSql('ALTER TABLE dim_contributor DROP FOREIGN KEY FK_8BC20A2CA76ED395');
        $this->addSql('ALTER TABLE dim_profile DROP FOREIGN KEY FK_9B698904CCFA12B8');

        $this->addSql('DROP TABLE fact_staffing_metrics');
        $this->addSql('DROP TABLE fact_project_metrics');
        $this->addSql('DROP TABLE dim_project_type');
        $this->addSql('DROP TABLE dim_profile');
        $this->addSql('DROP TABLE dim_contributor');
        $this->addSql('DROP TABLE dim_time');
    }
}