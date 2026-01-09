<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260109115915 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add AI image generation fields to blog_posts table';
    }

    public function up(Schema $schema): void
    {
        // Add AI image generation support to blog posts
        $this->addSql("ALTER TABLE blog_posts
            ADD image_prompt VARCHAR(1000) DEFAULT NULL COMMENT 'AI prompt for generating featured image',
            ADD image_source VARCHAR(20) NOT NULL DEFAULT 'external' COMMENT 'Source: external, upload, ai_generated',
            ADD image_generated_at DATETIME DEFAULT NULL COMMENT 'Timestamp when AI image was generated',
            ADD image_model VARCHAR(50) DEFAULT NULL COMMENT 'AI model used (e.g. dall-e-3)'"
        );
        $this->addSql('CREATE INDEX idx_blogpost_image_source ON blog_posts (image_source)');

        // Sync employment_periods default values
        $this->addSql('ALTER TABLE employment_periods CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT 35 NOT NULL, CHANGE work_time_percentage work_time_percentage NUMERIC(5, 2) DEFAULT 100 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX idx_blogpost_image_source ON blog_posts');
        $this->addSql('ALTER TABLE blog_posts DROP image_prompt, DROP image_source, DROP image_generated_at, DROP image_model');
        $this->addSql('ALTER TABLE employment_periods CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT \'35.00\' NOT NULL, CHANGE work_time_percentage work_time_percentage NUMERIC(5, 2) DEFAULT \'100.00\' NOT NULL');
    }
}
