<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251126175617 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE achievements (id INT AUTO_INCREMENT NOT NULL, contributor_id INT NOT NULL, badge_id INT NOT NULL, unlocked_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', notified TINYINT(1) NOT NULL, INDEX IDX_D1227EFE7A19A357 (contributor_id), INDEX IDX_D1227EFEF7A2C2FC (badge_id), UNIQUE INDEX unique_contributor_badge (contributor_id, badge_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE badges (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, description LONGTEXT NOT NULL, icon VARCHAR(50) NOT NULL, category VARCHAR(50) NOT NULL, xp_reward INT NOT NULL, criteria JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE contributor_progress (id INT AUTO_INCREMENT NOT NULL, contributor_id INT NOT NULL, total_xp INT NOT NULL, level INT NOT NULL, title VARCHAR(50) DEFAULT NULL, current_level_xp INT NOT NULL, next_level_xp INT NOT NULL, last_xp_gained_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_14C777707A19A357 (contributor_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE xp_history (id INT AUTO_INCREMENT NOT NULL, contributor_id INT NOT NULL, xp_amount INT NOT NULL, source VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, metadata JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', gained_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_C06720907A19A357 (contributor_id), INDEX idx_contributor_gained (contributor_id, gained_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE achievements ADD CONSTRAINT FK_D1227EFE7A19A357 FOREIGN KEY (contributor_id) REFERENCES contributors (id)');
        $this->addSql('ALTER TABLE achievements ADD CONSTRAINT FK_D1227EFEF7A2C2FC FOREIGN KEY (badge_id) REFERENCES badges (id)');
        $this->addSql('ALTER TABLE contributor_progress ADD CONSTRAINT FK_14C777707A19A357 FOREIGN KEY (contributor_id) REFERENCES contributors (id)');
        $this->addSql('ALTER TABLE xp_history ADD CONSTRAINT FK_C06720907A19A357 FOREIGN KEY (contributor_id) REFERENCES contributors (id)');
        $this->addSql('ALTER TABLE employment_periods CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT \'35\' NOT NULL, CHANGE work_time_percentage work_time_percentage NUMERIC(5, 2) DEFAULT \'100\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE achievements DROP FOREIGN KEY FK_D1227EFE7A19A357');
        $this->addSql('ALTER TABLE achievements DROP FOREIGN KEY FK_D1227EFEF7A2C2FC');
        $this->addSql('ALTER TABLE contributor_progress DROP FOREIGN KEY FK_14C777707A19A357');
        $this->addSql('ALTER TABLE xp_history DROP FOREIGN KEY FK_C06720907A19A357');
        $this->addSql('DROP TABLE achievements');
        $this->addSql('DROP TABLE badges');
        $this->addSql('DROP TABLE contributor_progress');
        $this->addSql('DROP TABLE xp_history');
        $this->addSql('ALTER TABLE employment_periods CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT \'35.00\' NOT NULL, CHANGE work_time_percentage work_time_percentage NUMERIC(5, 2) DEFAULT \'100.00\' NOT NULL');
    }
}
