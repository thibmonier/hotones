<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add missing updated_at columns to tables that have Gedmo\Timestampable
 * but don't have the column in the database schema.
 *
 * This fixes production deployment error: "Unknown column 't0.updated_at'"
 */
final class Version20260105073000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing updated_at columns to tables with Timestampable entities';
    }

    public function up(Schema $schema): void
    {
        // List of tables that have entities with updatedAt property but missing DB column
        // These are determined by entities that use #[Gedmo\Timestampable]

        $tablesToFix = [
            'companies',
            'business_units',
            'company_settings',
            'contributor_progress',
            'contributor_satisfactions',
            'contributor_skills',
            'expense_reports',
            'invoices',
            'lead_captures',
            'nps_surveys',
            'onboarding_tasks',
            'onboarding_templates',
            'orders',
            'performance_reviews',
            'plannings',
            'project_sub_tasks',
            'providers',
            'saas_providers',
            'saas_services',
            'saas_subscriptions_v2',
            'scheduler_entries',
            'skills',
            'vendors',
        ];

        foreach ($tablesToFix as $table) {
            // Check if column exists before adding
            $this->addSql("
                SET @column_exists = (
                    SELECT COUNT(*)
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = '{$table}'
                    AND COLUMN_NAME = 'updated_at'
                )
            ");

            $this->addSql("
                SET @sql = IF(
                    @column_exists = 0,
                    'ALTER TABLE {$table} ADD updated_at DATETIME DEFAULT NULL COMMENT ''(DC2Type:datetime_immutable)''',
                    'SELECT ''Column updated_at already exists in {$table}'''
                )
            ");

            $this->addSql("PREPARE stmt FROM @sql");
            $this->addSql("EXECUTE stmt");
            $this->addSql("DEALLOCATE PREPARE stmt");
        }
    }

    public function down(Schema $schema): void
    {
        // Reverting would drop data, so we only drop if empty
        // In practice, down migrations should rarely be run in production

        $this->addSql('-- This migration adds columns that may contain data.');
        $this->addSql('-- Down migration intentionally does nothing to preserve data.');
        $this->addSql('-- If you need to revert, manually drop the updated_at columns.');
    }
}
