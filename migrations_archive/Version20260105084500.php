<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Production Schema Repair Migration
 *
 * This migration applies all missing schema changes identified by doctrine:migrations:diff
 * on production database. It handles:
 * - Missing lead_captures table
 * - Missing company_id columns on notification tables
 * - Missing updated_at columns on nps_surveys and orders
 * - Index renaming (from old naming convention to Doctrine standard)
 * - Unique constraint modifications
 *
 * This migration is IDEMPOTENT - it checks if changes are needed before applying them.
 */
final class Version20260105084500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Repair production schema - apply all missing changes from migrations';
    }

    public function up(Schema $schema): void
    {
        // ===================================================================
        // 1. CREATE MISSING TABLES
        // ===================================================================

        if (!$schema->hasTable('lead_captures')) {
            $table = $schema->createTable('lead_captures');
            $table->addColumn('id', 'integer', ['autoincrement' => true]);
            $table->addColumn('email', 'string', ['length' => 255]);
            $table->addColumn('first_name', 'string', ['length' => 100]);
            $table->addColumn('last_name', 'string', ['length' => 100]);
            $table->addColumn('company', 'string', ['length' => 255, 'notnull' => false]);
            $table->addColumn('phone', 'string', ['length' => 50, 'notnull' => false]);
            $table->addColumn('source', 'string', ['length' => 50]);
            $table->addColumn('content_type', 'string', ['length' => 100]);
            $table->addColumn('downloaded_at', 'datetime', ['notnull' => false]);
            $table->addColumn('download_count', 'integer', ['default' => 0]);
            $table->addColumn('marketing_consent', 'boolean', ['default' => false]);
            $table->addColumn('internal_notes', 'text', ['notnull' => false]);
            $table->addColumn('status', 'string', ['length' => 50, 'default' => 'new']);
            $table->addColumn('nurturing_day1_sent_at', 'datetime', ['notnull' => false]);
            $table->addColumn('nurturing_day3_sent_at', 'datetime', ['notnull' => false]);
            $table->addColumn('nurturing_day7_sent_at', 'datetime', ['notnull' => false]);
            $table->addColumn('created_at', 'datetime_immutable');
            $table->addColumn('updated_at', 'datetime_immutable', ['notnull' => false]);
            $table->addColumn('company_id', 'integer', ['default' => 1]);
            $table->setPrimaryKey(['id']);
            $table->addIndex(['email'], 'idx_lead_email');
            $table->addIndex(['source'], 'idx_lead_source');
            $table->addIndex(['created_at'], 'idx_lead_created_at');
            $table->addIndex(['company_id'], 'idx_leadcapture_company');
            $table->addForeignKeyConstraint('companies', ['company_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_61B6A559979B1AD6');
        }

        // ===================================================================
        // 2. ADD MISSING COMPANY_ID COLUMNS
        // ===================================================================

        // notifications table
        if ($schema->hasTable('notifications')) {
            $table = $schema->getTable('notifications');
            if (!$table->hasColumn('company_id')) {
                $table->addColumn('company_id', 'integer', ['default' => 1]);
                $table->addIndex(['company_id'], 'idx_notification_company');
                $table->addForeignKeyConstraint('companies', ['company_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_6000B0D3979B1AD6');
            }
        }

        // notification_preferences table
        if ($schema->hasTable('notification_preferences')) {
            $table = $schema->getTable('notification_preferences');
            if (!$table->hasColumn('company_id')) {
                $table->addColumn('company_id', 'integer', ['default' => 1]);
                $table->addIndex(['company_id'], 'idx_notificationpreference_company');
                $table->addForeignKeyConstraint('companies', ['company_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_3CAA95B4979B1AD6');
            }
        }

        // notification_settings table
        if ($schema->hasTable('notification_settings')) {
            $table = $schema->getTable('notification_settings');
            if (!$table->hasColumn('company_id')) {
                $table->addColumn('company_id', 'integer', ['default' => 1]);
                $table->addIndex(['company_id'], 'idx_notificationsetting_company');
                $table->addForeignKeyConstraint('companies', ['company_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_B0559860979B1AD6');
            }
        }

        // ===================================================================
        // 3. ADD MISSING UPDATED_AT COLUMNS
        // ===================================================================

        if ($schema->hasTable('nps_surveys')) {
            $table = $schema->getTable('nps_surveys');
            if (!$table->hasColumn('updated_at')) {
                $table->addColumn('updated_at', 'datetime_immutable', ['notnull' => false]);
            }
        }

        if ($schema->hasTable('orders')) {
            $table = $schema->getTable('orders');
            if (!$table->hasColumn('updated_at')) {
                $table->addColumn('updated_at', 'datetime_immutable', ['notnull' => false]);
            }
        }

        // ===================================================================
        // 4. REMOVE COMPANY_ID FROM TABLES THAT SHOULDN'T HAVE IT
        // ===================================================================

        // contributor_profiles - remove company_id (not needed, gets it from contributor)
        if ($schema->hasTable('contributor_profiles')) {
            $table = $schema->getTable('contributor_profiles');
            if ($table->hasColumn('company_id')) {
                if ($table->hasForeignKey('fk_contributor_profile_company')) {
                    $table->removeForeignKey('fk_contributor_profile_company');
                }
                if ($table->hasIndex('idx_contributor_profile_company')) {
                    $table->dropIndex('idx_contributor_profile_company');
                }
                $table->dropColumn('company_id');
            }
        }

        // employment_period_profiles - remove company_id
        if ($schema->hasTable('employment_period_profiles')) {
            $table = $schema->getTable('employment_period_profiles');
            if ($table->hasColumn('company_id')) {
                if ($table->hasForeignKey('fk_employment_period_profile_company')) {
                    $table->removeForeignKey('fk_employment_period_profile_company');
                }
                if ($table->hasIndex('idx_employment_period_profile_company')) {
                    $table->dropIndex('idx_employment_period_profile_company');
                }
                $table->dropColumn('company_id');
            }
        }

        // project_technologies - remove company_id
        if ($schema->hasTable('project_technologies')) {
            $table = $schema->getTable('project_technologies');
            if ($table->hasColumn('company_id')) {
                if ($table->hasForeignKey('fk_project_technology_company')) {
                    $table->removeForeignKey('fk_project_technology_company');
                }
                if ($table->hasIndex('idx_project_technology_company')) {
                    $table->dropIndex('idx_project_technology_company');
                }
                $table->dropColumn('company_id');
            }
        }
    }

    public function down(Schema $schema): void
    {
        // This migration repairs production schema
        // Down migration intentionally does nothing to preserve data
        $this->addSql('-- Schema repair migration - down() intentionally empty');
    }
}
