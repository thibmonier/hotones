<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251126123628 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des tables pour la satisfaction client (NPS) et la satisfaction collaborateur';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE contributor_satisfactions (id INT AUTO_INCREMENT NOT NULL, contributor_id INT NOT NULL, year INT NOT NULL, month INT NOT NULL, overall_score INT NOT NULL, projects_score INT DEFAULT NULL, team_score INT DEFAULT NULL, work_environment_score INT DEFAULT NULL, work_life_balance_score INT DEFAULT NULL, comment LONGTEXT DEFAULT NULL, positive_points LONGTEXT DEFAULT NULL, improvement_points LONGTEXT DEFAULT NULL, submitted_at DATETIME NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_77E270717A19A357 (contributor_id), UNIQUE INDEX unique_contributor_period (contributor_id, year, month), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE nps_surveys (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, token VARCHAR(64) NOT NULL, sent_at DATETIME NOT NULL, responded_at DATETIME DEFAULT NULL, score INT DEFAULT NULL, comment LONGTEXT DEFAULT NULL, status VARCHAR(20) NOT NULL, recipient_email VARCHAR(255) NOT NULL, recipient_name VARCHAR(255) DEFAULT NULL, expires_at DATETIME NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_E88066E95F37A13B (token), INDEX IDX_E88066E9166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE contributor_satisfactions ADD CONSTRAINT FK_77E270717A19A357 FOREIGN KEY (contributor_id) REFERENCES contributors (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE nps_surveys ADD CONSTRAINT FK_E88066E9166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE employment_periods CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT \'35\' NOT NULL, CHANGE work_time_percentage work_time_percentage NUMERIC(5, 2) DEFAULT \'100\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contributor_satisfactions DROP FOREIGN KEY FK_77E270717A19A357');
        $this->addSql('ALTER TABLE nps_surveys DROP FOREIGN KEY FK_E88066E9166D1F9C');
        $this->addSql('DROP TABLE contributor_satisfactions');
        $this->addSql('DROP TABLE nps_surveys');
        $this->addSql('ALTER TABLE employment_periods CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT \'35.00\' NOT NULL, CHANGE work_time_percentage work_time_percentage NUMERIC(5, 2) DEFAULT \'100.00\' NOT NULL');
    }
}
