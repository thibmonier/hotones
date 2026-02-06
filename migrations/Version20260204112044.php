<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260204112044 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE contributor_technologies (id INT AUTO_INCREMENT NOT NULL, self_assessment_level INT NOT NULL, manager_assessment_level INT DEFAULT NULL, years_of_experience NUMERIC(4, 1) DEFAULT NULL, first_used_date DATE DEFAULT NULL, last_used_date DATE DEFAULT NULL, primary_context VARCHAR(20) NOT NULL, wants_to_use TINYINT NOT NULL, wants_to_improve TINYINT NOT NULL, version_used VARCHAR(50) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, company_id INT NOT NULL, contributor_id INT NOT NULL, technology_id INT NOT NULL, INDEX idx_contributortechnology_company (company_id), INDEX idx_contributortechnology_contributor (contributor_id), INDEX idx_contributortechnology_technology (technology_id), UNIQUE INDEX contributor_technology_unique (contributor_id, technology_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE contributor_technologies ADD CONSTRAINT FK_2C6F605F979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE contributor_technologies ADD CONSTRAINT FK_2C6F605F7A19A357 FOREIGN KEY (contributor_id) REFERENCES contributors (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE contributor_technologies ADD CONSTRAINT FK_2C6F605F4235D463 FOREIGN KEY (technology_id) REFERENCES technologies (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE boond_manager_settings CHANGE auth_type auth_type VARCHAR(20) NOT NULL, CHANGE enabled enabled TINYINT NOT NULL, CHANGE auto_sync_enabled auto_sync_enabled TINYINT NOT NULL, CHANGE sync_frequency_hours sync_frequency_hours INT NOT NULL');
        $this->addSql('ALTER TABLE employment_periods CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT 35 NOT NULL, CHANGE work_time_percentage work_time_percentage NUMERIC(5, 2) DEFAULT 100 NOT NULL');
        $this->addSql('ALTER TABLE hubspot_settings CHANGE enabled enabled TINYINT NOT NULL, CHANGE auto_sync_enabled auto_sync_enabled TINYINT NOT NULL, CHANGE sync_frequency_hours sync_frequency_hours INT NOT NULL, CHANGE sync_deals sync_deals TINYINT NOT NULL, CHANGE sync_companies sync_companies TINYINT NOT NULL, CHANGE sync_contacts sync_contacts TINYINT NOT NULL');
        $this->addSql('ALTER TABLE planning_skills CHANGE required_level required_level INT NOT NULL, CHANGE mandatory mandatory TINYINT NOT NULL');
        $this->addSql('ALTER TABLE project_skills CHANGE required_level required_level INT NOT NULL, CHANGE priority priority INT NOT NULL');
        $this->addSql('DROP INDEX idx_project_boond_manager ON projects');
        $this->addSql('ALTER TABLE technology_skills RENAME INDEX idx_technology_skills_technology TO IDX_9F0EC0DB4235D463');
        $this->addSql('ALTER TABLE technology_skills RENAME INDEX idx_technology_skills_skill TO IDX_9F0EC0DB5585C142');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contributor_technologies DROP FOREIGN KEY FK_2C6F605F979B1AD6');
        $this->addSql('ALTER TABLE contributor_technologies DROP FOREIGN KEY FK_2C6F605F7A19A357');
        $this->addSql('ALTER TABLE contributor_technologies DROP FOREIGN KEY FK_2C6F605F4235D463');
        $this->addSql('DROP TABLE contributor_technologies');
        $this->addSql('ALTER TABLE boond_manager_settings CHANGE auth_type auth_type VARCHAR(20) DEFAULT \'basic\' NOT NULL, CHANGE enabled enabled TINYINT DEFAULT 0 NOT NULL, CHANGE auto_sync_enabled auto_sync_enabled TINYINT DEFAULT 0 NOT NULL, CHANGE sync_frequency_hours sync_frequency_hours INT DEFAULT 24 NOT NULL');
        $this->addSql('ALTER TABLE employment_periods CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT \'35.00\' NOT NULL, CHANGE work_time_percentage work_time_percentage NUMERIC(5, 2) DEFAULT \'100.00\' NOT NULL');
        $this->addSql('ALTER TABLE hubspot_settings CHANGE enabled enabled TINYINT DEFAULT 0 NOT NULL, CHANGE auto_sync_enabled auto_sync_enabled TINYINT DEFAULT 0 NOT NULL, CHANGE sync_frequency_hours sync_frequency_hours INT DEFAULT 24 NOT NULL, CHANGE sync_deals sync_deals TINYINT DEFAULT 1 NOT NULL, CHANGE sync_companies sync_companies TINYINT DEFAULT 1 NOT NULL, CHANGE sync_contacts sync_contacts TINYINT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE planning_skills CHANGE required_level required_level INT DEFAULT 2 NOT NULL, CHANGE mandatory mandatory TINYINT DEFAULT 1 NOT NULL');
        $this->addSql('CREATE INDEX idx_project_boond_manager ON projects (boond_manager_id)');
        $this->addSql('ALTER TABLE project_skills CHANGE required_level required_level INT DEFAULT 2 NOT NULL, CHANGE priority priority INT DEFAULT 2 NOT NULL');
        $this->addSql('ALTER TABLE technology_skills RENAME INDEX idx_9f0ec0db4235d463 TO IDX_technology_skills_technology');
        $this->addSql('ALTER TABLE technology_skills RENAME INDEX idx_9f0ec0db5585c142 TO IDX_technology_skills_skill');
    }
}
