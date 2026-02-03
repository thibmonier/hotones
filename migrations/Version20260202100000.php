<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add BoondManager integration tables and columns.
 */
final class Version20260202100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add BoondManager settings table and mapping columns for contributors and projects';
    }

    public function up(Schema $schema): void
    {
        // Create boond_manager_settings table (idempotent)
        $this->addSql("
            SET @table_exists = (
                SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'boond_manager_settings'
            );

            SET @sql = IF(@table_exists = 0,
                'CREATE TABLE boond_manager_settings (
                    id INT AUTO_INCREMENT NOT NULL,
                    company_id INT NOT NULL,
                    api_base_url VARCHAR(255) DEFAULT NULL,
                    api_username VARCHAR(255) DEFAULT NULL,
                    api_password VARCHAR(500) DEFAULT NULL,
                    user_token VARCHAR(500) DEFAULT NULL,
                    client_token VARCHAR(500) DEFAULT NULL,
                    client_key VARCHAR(500) DEFAULT NULL,
                    auth_type VARCHAR(20) NOT NULL DEFAULT ''basic'',
                    enabled TINYINT(1) NOT NULL DEFAULT 0,
                    auto_sync_enabled TINYINT(1) NOT NULL DEFAULT 0,
                    sync_frequency_hours INT NOT NULL DEFAULT 24,
                    last_sync_at DATETIME DEFAULT NULL,
                    last_sync_status VARCHAR(50) DEFAULT NULL,
                    last_sync_error LONGTEXT DEFAULT NULL,
                    last_sync_count INT DEFAULT NULL,
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME NOT NULL,
                    PRIMARY KEY(id),
                    INDEX idx_boond_settings_company (company_id),
                    CONSTRAINT FK_boond_settings_company FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB',
                'SELECT ''Table boond_manager_settings already exists'' AS message'
            );

            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        // Add boond_manager_id column to contributors table (idempotent)
        $this->addSql("
            SET @column_exists = (
                SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'contributors'
                AND COLUMN_NAME = 'boond_manager_id'
            );

            SET @sql = IF(@column_exists = 0,
                'ALTER TABLE contributors ADD COLUMN boond_manager_id INT DEFAULT NULL',
                'SELECT ''Column boond_manager_id already exists in contributors'' AS message'
            );

            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        // Add index on boond_manager_id for contributors (idempotent)
        $this->addSql("
            SET @index_exists = (
                SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'contributors'
                AND INDEX_NAME = 'idx_contributor_boond_manager'
            );

            SET @sql = IF(@index_exists = 0,
                'CREATE INDEX idx_contributor_boond_manager ON contributors (boond_manager_id)',
                'SELECT ''Index idx_contributor_boond_manager already exists'' AS message'
            );

            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        // Add boond_manager_id column to projects table (idempotent)
        $this->addSql("
            SET @column_exists = (
                SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'projects'
                AND COLUMN_NAME = 'boond_manager_id'
            );

            SET @sql = IF(@column_exists = 0,
                'ALTER TABLE projects ADD COLUMN boond_manager_id INT DEFAULT NULL',
                'SELECT ''Column boond_manager_id already exists in projects'' AS message'
            );

            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        // Add index on boond_manager_id for projects (idempotent)
        $this->addSql("
            SET @index_exists = (
                SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'projects'
                AND INDEX_NAME = 'idx_project_boond_manager'
            );

            SET @sql = IF(@index_exists = 0,
                'CREATE INDEX idx_project_boond_manager ON projects (boond_manager_id)',
                'SELECT ''Index idx_project_boond_manager already exists'' AS message'
            );

            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");
    }

    public function down(Schema $schema): void
    {
        // Drop index on projects (idempotent)
        $this->addSql("
            SET @index_exists = (
                SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'projects'
                AND INDEX_NAME = 'idx_project_boond_manager'
            );

            SET @sql = IF(@index_exists > 0,
                'DROP INDEX idx_project_boond_manager ON projects',
                'SELECT ''Index idx_project_boond_manager does not exist'' AS message'
            );

            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        // Drop boond_manager_id from projects (idempotent)
        $this->addSql("
            SET @column_exists = (
                SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'projects'
                AND COLUMN_NAME = 'boond_manager_id'
            );

            SET @sql = IF(@column_exists > 0,
                'ALTER TABLE projects DROP COLUMN boond_manager_id',
                'SELECT ''Column boond_manager_id does not exist in projects'' AS message'
            );

            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        // Drop index on contributors (idempotent)
        $this->addSql("
            SET @index_exists = (
                SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'contributors'
                AND INDEX_NAME = 'idx_contributor_boond_manager'
            );

            SET @sql = IF(@index_exists > 0,
                'DROP INDEX idx_contributor_boond_manager ON contributors',
                'SELECT ''Index idx_contributor_boond_manager does not exist'' AS message'
            );

            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        // Drop boond_manager_id from contributors (idempotent)
        $this->addSql("
            SET @column_exists = (
                SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'contributors'
                AND COLUMN_NAME = 'boond_manager_id'
            );

            SET @sql = IF(@column_exists > 0,
                'ALTER TABLE contributors DROP COLUMN boond_manager_id',
                'SELECT ''Column boond_manager_id does not exist in contributors'' AS message'
            );

            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        // Drop boond_manager_settings table (idempotent)
        $this->addSql("
            SET @table_exists = (
                SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'boond_manager_settings'
            );

            SET @sql = IF(@table_exists > 0,
                'DROP TABLE boond_manager_settings',
                'SELECT ''Table boond_manager_settings does not exist'' AS message'
            );

            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");
    }
}
