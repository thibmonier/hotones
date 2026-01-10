<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Idempotent migration to ensure AI image fields exist in blog_posts table.
 * This migration can be run multiple times safely - it checks column existence before adding.
 */
final class Version20260110081458 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ensure AI image generation fields exist in blog_posts table (idempotent)';
    }

    public function up(Schema $schema): void
    {
        // MySQL/MariaDB doesn't support IF NOT EXISTS for columns
        // We need to check existence first using stored procedures or catch exceptions

        // Check and add image_prompt if it doesn't exist
        $this->addSql("
            SET @column_exists = (
                SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'blog_posts'
                AND COLUMN_NAME = 'image_prompt'
            );

            SET @sql = IF(@column_exists = 0,
                'ALTER TABLE blog_posts ADD COLUMN image_prompt VARCHAR(1000) DEFAULT NULL COMMENT ''AI prompt for generating featured image''',
                'SELECT ''Column image_prompt already exists'' AS message'
            );

            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        // Check and add image_source if it doesn't exist
        $this->addSql("
            SET @column_exists = (
                SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'blog_posts'
                AND COLUMN_NAME = 'image_source'
            );

            SET @sql = IF(@column_exists = 0,
                'ALTER TABLE blog_posts ADD COLUMN image_source VARCHAR(20) NOT NULL DEFAULT ''external'' COMMENT ''Source: external, upload, ai_generated''',
                'SELECT ''Column image_source already exists'' AS message'
            );

            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        // Check and add image_generated_at if it doesn't exist
        $this->addSql("
            SET @column_exists = (
                SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'blog_posts'
                AND COLUMN_NAME = 'image_generated_at'
            );

            SET @sql = IF(@column_exists = 0,
                'ALTER TABLE blog_posts ADD COLUMN image_generated_at DATETIME DEFAULT NULL COMMENT ''Timestamp when AI image was generated''',
                'SELECT ''Column image_generated_at already exists'' AS message'
            );

            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        // Check and add image_model if it doesn't exist
        $this->addSql("
            SET @column_exists = (
                SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'blog_posts'
                AND COLUMN_NAME = 'image_model'
            );

            SET @sql = IF(@column_exists = 0,
                'ALTER TABLE blog_posts ADD COLUMN image_model VARCHAR(50) DEFAULT NULL COMMENT ''AI model used (e.g. dall-e-3)''',
                'SELECT ''Column image_model already exists'' AS message'
            );

            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        // Create index if it doesn't exist
        $this->addSql("
            SET @index_exists = (
                SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'blog_posts'
                AND INDEX_NAME = 'idx_blogpost_image_source'
            );

            SET @sql = IF(@index_exists = 0,
                'CREATE INDEX idx_blogpost_image_source ON blog_posts (image_source)',
                'SELECT ''Index idx_blogpost_image_source already exists'' AS message'
            );

            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");
    }

    public function down(Schema $schema): void
    {
        // Drop index if it exists
        $this->addSql("
            SET @index_exists = (
                SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'blog_posts'
                AND INDEX_NAME = 'idx_blogpost_image_source'
            );

            SET @sql = IF(@index_exists > 0,
                'DROP INDEX idx_blogpost_image_source ON blog_posts',
                'SELECT ''Index idx_blogpost_image_source does not exist'' AS message'
            );

            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        // Drop columns if they exist
        $columns = ['image_prompt', 'image_source', 'image_generated_at', 'image_model'];
        foreach ($columns as $column) {
            $this->addSql("
                SET @column_exists = (
                    SELECT COUNT(*)
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'blog_posts'
                    AND COLUMN_NAME = '$column'
                );

                SET @sql = IF(@column_exists > 0,
                    'ALTER TABLE blog_posts DROP COLUMN $column',
                    'SELECT ''Column $column does not exist'' AS message'
                );

                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            ");
        }
    }
}
