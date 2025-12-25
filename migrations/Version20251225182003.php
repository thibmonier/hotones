<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251225182003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create cookie_consents table for GDPR compliance traceability';
    }

    public function up(Schema $schema): void
    {
        // Create cookie_consents table for GDPR compliance
        $this->addSql('CREATE TABLE cookie_consents (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, essential TINYINT(1) NOT NULL, functional TINYINT(1) NOT NULL, analytics TINYINT(1) NOT NULL, version VARCHAR(10) NOT NULL, ip_address VARCHAR(45) DEFAULT NULL, user_agent LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX idx_cookie_consent_user (user_id), INDEX idx_cookie_consent_created (created_at), INDEX idx_cookie_consent_expires (expires_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE cookie_consents ADD CONSTRAINT FK_FCDE2BEFA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');

        // Clean up schema drift: remove external_tasks references first
        $this->addSql('ALTER TABLE employment_periods CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT 35 NOT NULL, CHANGE work_time_percentage work_time_percentage NUMERIC(5, 2) DEFAULT 100 NOT NULL');

        // Check if foreign key exists and drop it
        $this->addSql('SET @constraint_name = (SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = \'timesheets\' AND COLUMN_NAME = \'external_task_id\' AND REFERENCED_TABLE_NAME IS NOT NULL LIMIT 1)');
        $this->addSql('SET @sql_drop_fk = IF(@constraint_name IS NOT NULL, CONCAT(\'ALTER TABLE timesheets DROP FOREIGN KEY \', @constraint_name), \'SELECT 1\')');
        $this->addSql('PREPARE stmt FROM @sql_drop_fk');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');

        // Check if index exists and drop it
        $this->addSql('SET @index_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = \'timesheets\' AND INDEX_NAME = \'IDX_9AC77D2E5414D8C5\')');
        $this->addSql('SET @sql_drop_idx = IF(@index_exists > 0, \'ALTER TABLE timesheets DROP INDEX IDX_9AC77D2E5414D8C5\', \'SELECT 1\')');
        $this->addSql('PREPARE stmt FROM @sql_drop_idx');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');

        // Check if column exists and drop it
        $this->addSql('SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = \'timesheets\' AND COLUMN_NAME = \'external_task_id\')');
        $this->addSql('SET @sql_drop_col = IF(@column_exists > 0, \'ALTER TABLE timesheets DROP COLUMN external_task_id\', \'SELECT 1\')');
        $this->addSql('PREPARE stmt FROM @sql_drop_col');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');

        // Finally, drop the external_tasks table
        $this->addSql('DROP TABLE IF EXISTS external_tasks');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE external_tasks (id INT AUTO_INCREMENT NOT NULL, external_task_key VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, title VARCHAR(500) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, description LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, status VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, external_assignee_identifier VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, estimated_hours NUMERIC(6, 2) DEFAULT NULL, remaining_hours NUMERIC(6, 2) DEFAULT NULL, external_url VARCHAR(500) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, raw_data JSON DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', last_sync_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', external_system_id INT NOT NULL, project_id INT NOT NULL, assignee_id INT DEFAULT NULL, INDEX idx_external_task_project (project_id), UNIQUE INDEX unique_external_task (external_system_id, external_task_key), INDEX idx_external_task_assignee (assignee_id), INDEX idx_external_task_status (status), INDEX IDX_62151614F4CE80B1 (external_system_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE cookie_consents DROP FOREIGN KEY FK_FCDE2BEFA76ED395');
        $this->addSql('DROP TABLE cookie_consents');
        $this->addSql('ALTER TABLE employment_periods CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT \'35.00\' NOT NULL, CHANGE work_time_percentage work_time_percentage NUMERIC(5, 2) DEFAULT \'100.00\' NOT NULL');
        $this->addSql('ALTER TABLE timesheets ADD external_task_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE timesheets ADD CONSTRAINT `FK_9AC77D2E5414D8C5` FOREIGN KEY (external_task_id) REFERENCES external_tasks (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_9AC77D2E5414D8C5 ON timesheets (external_task_id)');
    }
}
