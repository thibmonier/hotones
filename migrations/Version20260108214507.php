<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260108214507 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE blog_categories (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, description VARCHAR(500) DEFAULT NULL, color VARCHAR(7) DEFAULT NULL, active TINYINT DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_DC3564815E237E06 (name), UNIQUE INDEX UNIQ_DC356481989D9B62 (slug), INDEX idx_blogcategory_slug (slug), INDEX idx_blogcategory_active (active), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE blog_posts (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, content LONGTEXT DEFAULT NULL, excerpt VARCHAR(500) DEFAULT NULL, featured_image VARCHAR(500) DEFAULT NULL, status VARCHAR(20) NOT NULL, published_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, company_id INT NOT NULL, author_id INT NOT NULL, category_id INT DEFAULT NULL, INDEX idx_blogpost_company (company_id), INDEX idx_blogpost_status (status), INDEX idx_blogpost_published_at (published_at), INDEX idx_blogpost_author (author_id), INDEX idx_blogpost_category (category_id), UNIQUE INDEX uniq_blogpost_company_slug (company_id, slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE blog_post_tag (blog_post_id INT NOT NULL, blog_tag_id INT NOT NULL, INDEX IDX_2E931ED7A77FBEAF (blog_post_id), INDEX IDX_2E931ED72F9DC6D0 (blog_tag_id), PRIMARY KEY (blog_post_id, blog_tag_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE blog_tags (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, slug VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_8F6C18B65E237E06 (name), UNIQUE INDEX UNIQ_8F6C18B6989D9B62 (slug), INDEX idx_blogtag_slug (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE blog_posts ADD CONSTRAINT FK_78B2F932979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE blog_posts ADD CONSTRAINT FK_78B2F932F675F31B FOREIGN KEY (author_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE blog_posts ADD CONSTRAINT FK_78B2F93212469DE2 FOREIGN KEY (category_id) REFERENCES blog_categories (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE blog_post_tag ADD CONSTRAINT FK_2E931ED7A77FBEAF FOREIGN KEY (blog_post_id) REFERENCES blog_posts (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE blog_post_tag ADD CONSTRAINT FK_2E931ED72F9DC6D0 FOREIGN KEY (blog_tag_id) REFERENCES blog_tags (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE employment_periods CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT 35 NOT NULL, CHANGE work_time_percentage work_time_percentage NUMERIC(5, 2) DEFAULT 100 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE blog_posts DROP FOREIGN KEY FK_78B2F932979B1AD6');
        $this->addSql('ALTER TABLE blog_posts DROP FOREIGN KEY FK_78B2F932F675F31B');
        $this->addSql('ALTER TABLE blog_posts DROP FOREIGN KEY FK_78B2F93212469DE2');
        $this->addSql('ALTER TABLE blog_post_tag DROP FOREIGN KEY FK_2E931ED7A77FBEAF');
        $this->addSql('ALTER TABLE blog_post_tag DROP FOREIGN KEY FK_2E931ED72F9DC6D0');
        $this->addSql('DROP TABLE blog_categories');
        $this->addSql('DROP TABLE blog_posts');
        $this->addSql('DROP TABLE blog_post_tag');
        $this->addSql('DROP TABLE blog_tags');
        $this->addSql('ALTER TABLE employment_periods CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT \'35.00\' NOT NULL, CHANGE work_time_percentage work_time_percentage NUMERIC(5, 2) DEFAULT \'100.00\' NOT NULL');
    }
}
