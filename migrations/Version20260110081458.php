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
        // Check and add image_prompt if it doesn't exist
        $this->addSql("
            ALTER TABLE blog_posts
            ADD COLUMN IF NOT EXISTS image_prompt VARCHAR(1000) DEFAULT NULL
            COMMENT 'AI prompt for generating featured image'
        ");

        // Check and add image_source if it doesn't exist
        $this->addSql("
            ALTER TABLE blog_posts
            ADD COLUMN IF NOT EXISTS image_source VARCHAR(20) NOT NULL DEFAULT 'external'
            COMMENT 'Source: external, upload, ai_generated'
        ");

        // Check and add image_generated_at if it doesn't exist
        $this->addSql("
            ALTER TABLE blog_posts
            ADD COLUMN IF NOT EXISTS image_generated_at DATETIME DEFAULT NULL
            COMMENT 'Timestamp when AI image was generated'
        ");

        // Check and add image_model if it doesn't exist
        $this->addSql("
            ALTER TABLE blog_posts
            ADD COLUMN IF NOT EXISTS image_model VARCHAR(50) DEFAULT NULL
            COMMENT 'AI model used (e.g. dall-e-3)'
        ");

        // Create index if it doesn't exist
        $this->addSql("
            CREATE INDEX IF NOT EXISTS idx_blogpost_image_source
            ON blog_posts (image_source)
        ");
    }

    public function down(Schema $schema): void
    {
        // Safely drop index and columns
        $this->addSql('DROP INDEX IF EXISTS idx_blogpost_image_source ON blog_posts');
        $this->addSql('ALTER TABLE blog_posts DROP COLUMN IF EXISTS image_prompt');
        $this->addSql('ALTER TABLE blog_posts DROP COLUMN IF EXISTS image_source');
        $this->addSql('ALTER TABLE blog_posts DROP COLUMN IF EXISTS image_generated_at');
        $this->addSql('ALTER TABLE blog_posts DROP COLUMN IF EXISTS image_model');
    }
}
