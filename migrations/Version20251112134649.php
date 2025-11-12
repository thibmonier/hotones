<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251112134649 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE dim_profile (id INT AUTO_INCREMENT NOT NULL, profile_id INT DEFAULT NULL, name_value VARCHAR(100) NOT NULL, is_productive TINYINT(1) NOT NULL, is_active TINYINT(1) NOT NULL, composite_key VARCHAR(150) NOT NULL, UNIQUE INDEX UNIQ_9B69890413775659 (composite_key), INDEX IDX_9B698904CCFA12B8 (profile_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE fact_staffing_metrics (id INT AUTO_INCREMENT NOT NULL, dim_time_id INT NOT NULL, dim_profile_id INT DEFAULT NULL, contributor_id INT DEFAULT NULL, available_days NUMERIC(10, 2) NOT NULL, worked_days NUMERIC(10, 2) NOT NULL, staffed_days NUMERIC(10, 2) NOT NULL, vacation_days NUMERIC(10, 2) NOT NULL, planned_days NUMERIC(10, 2) NOT NULL, staffing_rate NUMERIC(5, 2) NOT NULL, tace NUMERIC(5, 2) NOT NULL, calculated_at DATETIME NOT NULL, granularity VARCHAR(50) NOT NULL, contributor_count INT NOT NULL, INDEX IDX_F58C0D4844D4FE30 (dim_time_id), INDEX IDX_F58C0D48BF679789 (dim_profile_id), INDEX IDX_F58C0D487A19A357 (contributor_id), UNIQUE INDEX unique_staffing_metrics (dim_time_id, dim_profile_id, contributor_id, granularity), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE dim_profile ADD CONSTRAINT FK_9B698904CCFA12B8 FOREIGN KEY (profile_id) REFERENCES profiles (id)');
        $this->addSql('ALTER TABLE fact_staffing_metrics ADD CONSTRAINT FK_F58C0D4844D4FE30 FOREIGN KEY (dim_time_id) REFERENCES dim_time (id)');
        $this->addSql('ALTER TABLE fact_staffing_metrics ADD CONSTRAINT FK_F58C0D48BF679789 FOREIGN KEY (dim_profile_id) REFERENCES dim_profile (id)');
        $this->addSql('ALTER TABLE fact_staffing_metrics ADD CONSTRAINT FK_F58C0D487A19A357 FOREIGN KEY (contributor_id) REFERENCES contributors (id)');
        $this->addSql('ALTER TABLE employment_periods CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT \'35\' NOT NULL, CHANGE work_time_percentage work_time_percentage NUMERIC(5, 2) DEFAULT \'100\' NOT NULL');
        $this->addSql('ALTER TABLE notification_preferences RENAME INDEX idx_857aebaea76ed395 TO IDX_3CAA95B4A76ED395');
        $this->addSql('ALTER TABLE notification_settings RENAME INDEX uniq_b27ba8419646419b TO UNIQ_B05598605FA1E697');
        $this->addSql('DROP INDEX IDX_6000B0D38B8E8428 ON notifications');
        $this->addSql('DROP INDEX IDX_6000B0D37C69D773 ON notifications');
        $this->addSql('DROP INDEX IDX_6000B0D3C54C8C93 ON notifications');
        $this->addSql('ALTER TABLE project_tasks RENAME INDEX idx_da05c8b3cc4b3e TO IDX_430D6C09BB01DC09');
        $this->addSql('ALTER TABLE project_technology_versions RENAME INDEX idx_ptv_project TO IDX_19C1B1E166D1F9C');
        $this->addSql('ALTER TABLE project_technology_versions RENAME INDEX idx_ptv_tech TO IDX_19C1B1E4235D463');
        $this->addSql('DROP INDEX IDX_RT_CONTRIBUTOR_ACTIVE ON running_timers');
        $this->addSql('ALTER TABLE running_timers RENAME INDEX idx_rt_contributor TO IDX_5F84C1EC7A19A357');
        $this->addSql('ALTER TABLE running_timers RENAME INDEX idx_rt_project TO IDX_5F84C1EC166D1F9C');
        $this->addSql('ALTER TABLE running_timers RENAME INDEX idx_rt_task TO IDX_5F84C1EC8DB60186');
        $this->addSql('ALTER TABLE running_timers RENAME INDEX idx_rt_sub_task TO IDX_5F84C1ECF26E5D72');
        $this->addSql('DROP INDEX idx_timesheet_project_date ON timesheets');
        $this->addSql('DROP INDEX idx_timesheet_contributor_date ON timesheets');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dim_profile DROP FOREIGN KEY FK_9B698904CCFA12B8');
        $this->addSql('ALTER TABLE fact_staffing_metrics DROP FOREIGN KEY FK_F58C0D4844D4FE30');
        $this->addSql('ALTER TABLE fact_staffing_metrics DROP FOREIGN KEY FK_F58C0D48BF679789');
        $this->addSql('ALTER TABLE fact_staffing_metrics DROP FOREIGN KEY FK_F58C0D487A19A357');
        $this->addSql('DROP TABLE dim_profile');
        $this->addSql('DROP TABLE fact_staffing_metrics');
        $this->addSql('ALTER TABLE project_technology_versions RENAME INDEX idx_19c1b1e166d1f9c TO IDX_PTV_PROJECT');
        $this->addSql('ALTER TABLE project_technology_versions RENAME INDEX idx_19c1b1e4235d463 TO IDX_PTV_TECH');
        $this->addSql('ALTER TABLE project_tasks RENAME INDEX idx_430d6c09bb01dc09 TO IDX_DA05C8B3CC4B3E');
        $this->addSql('CREATE INDEX IDX_RT_CONTRIBUTOR_ACTIVE ON running_timers (contributor_id, stopped_at)');
        $this->addSql('ALTER TABLE running_timers RENAME INDEX idx_5f84c1ecf26e5d72 TO IDX_RT_SUB_TASK');
        $this->addSql('ALTER TABLE running_timers RENAME INDEX idx_5f84c1ec7a19a357 TO IDX_RT_CONTRIBUTOR');
        $this->addSql('ALTER TABLE running_timers RENAME INDEX idx_5f84c1ec166d1f9c TO IDX_RT_PROJECT');
        $this->addSql('ALTER TABLE running_timers RENAME INDEX idx_5f84c1ec8db60186 TO IDX_RT_TASK');
        $this->addSql('CREATE INDEX IDX_6000B0D38B8E8428 ON notifications (created_at)');
        $this->addSql('CREATE INDEX IDX_6000B0D37C69D773 ON notifications (read_at)');
        $this->addSql('CREATE INDEX IDX_6000B0D3C54C8C93 ON notifications (type)');
        $this->addSql('CREATE INDEX idx_timesheet_project_date ON timesheets (project_id, date)');
        $this->addSql('CREATE INDEX idx_timesheet_contributor_date ON timesheets (contributor_id, date)');
        $this->addSql('ALTER TABLE notification_preferences RENAME INDEX idx_3caa95b4a76ed395 TO IDX_857AEBAEA76ED395');
        $this->addSql('ALTER TABLE employment_periods CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT \'35.00\' NOT NULL, CHANGE work_time_percentage work_time_percentage NUMERIC(5, 2) DEFAULT \'100.00\' NOT NULL');
        $this->addSql('ALTER TABLE notification_settings RENAME INDEX uniq_b05598605fa1e697 TO UNIQ_B27BA8419646419B');
    }
}
