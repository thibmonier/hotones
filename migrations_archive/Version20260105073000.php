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
            'saas_subscriptions',      // SaasSubscription entity
            'saas_subscriptions_v2',   // Subscription entity
            'scheduler_entries',
            'skills',
            'users',                   // User entity
            'vendors',
        ];

        // Use Doctrine Schema API to safely add columns only if they don't exist
        foreach ($tablesToFix as $tableName) {
            // Skip if table doesn't exist in schema
            if (!$schema->hasTable($tableName)) {
                continue;
            }

            $table = $schema->getTable($tableName);

            if (!$table->hasColumn('updated_at')) {
                $table->addColumn('updated_at', 'datetime_immutable', [
                    'notnull' => false,
                    'default' => null,
                ]);
            }
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
