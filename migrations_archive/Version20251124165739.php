<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251124165739 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE contributor_skills (id INT AUTO_INCREMENT NOT NULL, contributor_id INT NOT NULL, skill_id INT NOT NULL, self_assessment_level INT NOT NULL, manager_assessment_level INT DEFAULT NULL, date_acquired DATE DEFAULT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_A0CA02C87A19A357 (contributor_id), INDEX IDX_A0CA02C85585C142 (skill_id), UNIQUE INDEX contributor_skill_unique (contributor_id, skill_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE skills (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, category VARCHAR(50) NOT NULL, description LONGTEXT DEFAULT NULL, active TINYINT(1) DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_D53116705E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE contributor_skills ADD CONSTRAINT FK_A0CA02C87A19A357 FOREIGN KEY (contributor_id) REFERENCES contributors (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE contributor_skills ADD CONSTRAINT FK_A0CA02C85585C142 FOREIGN KEY (skill_id) REFERENCES skills (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE employment_periods CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT \'35\' NOT NULL, CHANGE work_time_percentage work_time_percentage NUMERIC(5, 2) DEFAULT \'100\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contributor_skills DROP FOREIGN KEY FK_A0CA02C87A19A357');
        $this->addSql('ALTER TABLE contributor_skills DROP FOREIGN KEY FK_A0CA02C85585C142');
        $this->addSql('DROP TABLE contributor_skills');
        $this->addSql('DROP TABLE skills');
        $this->addSql('ALTER TABLE employment_periods CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT \'35.00\' NOT NULL, CHANGE work_time_percentage work_time_percentage NUMERIC(5, 2) DEFAULT \'100.00\' NOT NULL');
    }
}
