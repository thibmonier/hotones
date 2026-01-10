<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260110134551 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add SEO fields (metaTitle, metaDescription, keywords) to blog_posts table (idempotent using INFORMATION_SCHEMA)';
    }

    public function up(Schema $schema): void
    {
        // Add meta_title column (idempotent)
        $this->addSql("
            SET @column_exists = (
                SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'blog_posts'
                AND COLUMN_NAME = 'meta_title'
            );

            SET @sql = IF(@column_exists = 0,
                'ALTER TABLE blog_posts ADD COLUMN meta_title VARCHAR(60) DEFAULT NULL COMMENT ''SEO custom title (max 60 chars)''',
                'SELECT ''Column meta_title already exists'' AS message'
            );

            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        // Add meta_description column (idempotent)
        $this->addSql("
            SET @column_exists = (
                SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'blog_posts'
                AND COLUMN_NAME = 'meta_description'
            );

            SET @sql = IF(@column_exists = 0,
                'ALTER TABLE blog_posts ADD COLUMN meta_description VARCHAR(160) DEFAULT NULL COMMENT ''SEO custom description (max 160 chars)''',
                'SELECT ''Column meta_description already exists'' AS message'
            );

            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        // Add keywords column (idempotent)
        $this->addSql("
            SET @column_exists = (
                SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'blog_posts'
                AND COLUMN_NAME = 'keywords'
            );

            SET @sql = IF(@column_exists = 0,
                'ALTER TABLE blog_posts ADD COLUMN keywords VARCHAR(255) DEFAULT NULL COMMENT ''SEO keywords, comma-separated''',
                'SELECT ''Column keywords already exists'' AS message'
            );

            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");
    }

    public function down(Schema $schema): void
    {
        // Remove SEO columns if they exist
        $this->addSql("
            SET @column_exists = (
                SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'blog_posts'
                AND COLUMN_NAME = 'meta_title'
            );

            SET @sql = IF(@column_exists > 0,
                'ALTER TABLE blog_posts DROP COLUMN meta_title',
                'SELECT ''Column meta_title does not exist'' AS message'
            );

            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        $this->addSql("
            SET @column_exists = (
                SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'blog_posts'
                AND COLUMN_NAME = 'meta_description'
            );

            SET @sql = IF(@column_exists > 0,
                'ALTER TABLE blog_posts DROP COLUMN meta_description',
                'SELECT ''Column meta_description does not exist'' AS message'
            );

            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        $this->addSql("
            SET @column_exists = (
                SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'blog_posts'
                AND COLUMN_NAME = 'keywords'
            );

            SET @sql = IF(@column_exists > 0,
                'ALTER TABLE blog_posts DROP COLUMN keywords',
                'SELECT ''Column keywords does not exist'' AS message'
            );

            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");
    }
}
