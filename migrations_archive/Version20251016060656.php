<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251016060656 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE contributor_profiles (contributor_id INT NOT NULL, profile_id INT NOT NULL, INDEX IDX_BDF600067A19A357 (contributor_id), INDEX IDX_BDF60006CCFA12B8 (profile_id), PRIMARY KEY(contributor_id, profile_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE project_technologies (project_id INT NOT NULL, technology_id INT NOT NULL, INDEX IDX_666C1F7B166D1F9C (project_id), INDEX IDX_666C1F7B4235D463 (technology_id), PRIMARY KEY(project_id, technology_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE service_categories (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, description VARCHAR(255) DEFAULT NULL, color VARCHAR(7) DEFAULT NULL, active TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE technologies (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, category VARCHAR(50) NOT NULL, color VARCHAR(7) DEFAULT NULL, active TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE contributor_profiles ADD CONSTRAINT FK_BDF600067A19A357 FOREIGN KEY (contributor_id) REFERENCES contributors (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE contributor_profiles ADD CONSTRAINT FK_BDF60006CCFA12B8 FOREIGN KEY (profile_id) REFERENCES profiles (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_technologies ADD CONSTRAINT FK_666C1F7B166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_technologies ADD CONSTRAINT FK_666C1F7B4235D463 FOREIGN KEY (technology_id) REFERENCES technologies (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE contributors ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE contributors ADD CONSTRAINT FK_72D26262A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_72D26262A76ED395 ON contributors (user_id)');
        $this->addSql('ALTER TABLE employment_periods CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT \'35\' NOT NULL');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEE166D1F9C');
        $this->addSql('ALTER TABLE orders ADD contingence_amount NUMERIC(12, 2) DEFAULT NULL, ADD contingence_reason LONGTEXT DEFAULT NULL, CHANGE description notes LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id)');
        $this->addSql('ALTER TABLE projects ADD service_category_id INT DEFAULT NULL, ADD is_internal TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE projects ADD CONSTRAINT FK_5C93B3A4DEDCBB4E FOREIGN KEY (service_category_id) REFERENCES service_categories (id)');
        $this->addSql('CREATE INDEX IDX_5C93B3A4DEDCBB4E ON projects (service_category_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE projects DROP FOREIGN KEY FK_5C93B3A4DEDCBB4E');
        $this->addSql('ALTER TABLE contributor_profiles DROP FOREIGN KEY FK_BDF600067A19A357');
        $this->addSql('ALTER TABLE contributor_profiles DROP FOREIGN KEY FK_BDF60006CCFA12B8');
        $this->addSql('ALTER TABLE project_technologies DROP FOREIGN KEY FK_666C1F7B166D1F9C');
        $this->addSql('ALTER TABLE project_technologies DROP FOREIGN KEY FK_666C1F7B4235D463');
        $this->addSql('DROP TABLE contributor_profiles');
        $this->addSql('DROP TABLE project_technologies');
        $this->addSql('DROP TABLE service_categories');
        $this->addSql('DROP TABLE technologies');
        $this->addSql('DROP INDEX IDX_5C93B3A4DEDCBB4E ON projects');
        $this->addSql('ALTER TABLE projects DROP service_category_id, DROP is_internal');
        $this->addSql('ALTER TABLE contributors DROP FOREIGN KEY FK_72D26262A76ED395');
        $this->addSql('DROP INDEX UNIQ_72D26262A76ED395 ON contributors');
        $this->addSql('ALTER TABLE contributors DROP user_id');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEE166D1F9C');
        $this->addSql('ALTER TABLE orders ADD description LONGTEXT DEFAULT NULL, DROP notes, DROP contingence_amount, DROP contingence_reason');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE employment_periods CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT \'35.00\' NOT NULL');
    }
}
