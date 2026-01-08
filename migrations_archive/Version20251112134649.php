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
        // SKIP: dim_profile and fact_staffing_metrics already created in Version20251019155000
        // SKIP: Foreign key constraints already created in Version20251019155000
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
        // SKIP: dim_profile and fact_staffing_metrics managed by Version20251019155000
        // SKIP: Foreign key constraints managed by Version20251019155000
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
