<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add HubSpot integration table for CRM synchronization.
 */
final class Version20260203100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add HubSpot settings table for CRM synchronization (deals, companies, contacts)';
    }

    public function up(Schema $schema): void
    {
        // Create hubspot_settings table (idempotent)
        $this->addSql("
            SET @table_exists = (
                SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'hubspot_settings'
            );

            SET @sql = IF(@table_exists = 0,
                'CREATE TABLE hubspot_settings (
                    id INT AUTO_INCREMENT NOT NULL,
                    company_id INT NOT NULL,
                    access_token VARCHAR(500) DEFAULT NULL,
                    portal_id VARCHAR(50) DEFAULT NULL,
                    enabled TINYINT(1) NOT NULL DEFAULT 0,
                    auto_sync_enabled TINYINT(1) NOT NULL DEFAULT 0,
                    sync_frequency_hours INT NOT NULL DEFAULT 24,
                    sync_deals TINYINT(1) NOT NULL DEFAULT 1,
                    sync_companies TINYINT(1) NOT NULL DEFAULT 1,
                    sync_contacts TINYINT(1) NOT NULL DEFAULT 1,
                    pipeline_filter LONGTEXT DEFAULT NULL,
                    excluded_stages LONGTEXT DEFAULT NULL,
                    last_sync_at DATETIME DEFAULT NULL,
                    last_sync_status VARCHAR(50) DEFAULT NULL,
                    last_sync_error LONGTEXT DEFAULT NULL,
                    last_sync_count INT DEFAULT NULL,
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME NOT NULL,
                    PRIMARY KEY(id),
                    INDEX idx_hubspot_settings_company (company_id),
                    CONSTRAINT FK_hubspot_settings_company FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB',
                'SELECT ''Table hubspot_settings already exists'' AS message'
            );

            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");
    }

    public function down(Schema $schema): void
    {
        // Drop hubspot_settings table (idempotent)
        $this->addSql("
            SET @table_exists = (
                SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'hubspot_settings'
            );

            SET @sql = IF(@table_exists > 0,
                'DROP TABLE hubspot_settings',
                'SELECT ''Table hubspot_settings does not exist'' AS message'
            );

            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");
    }
}
