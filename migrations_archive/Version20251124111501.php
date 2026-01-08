<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251124111501 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create project_events table for project timeline/history tracking';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE project_events (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, actor_id INT DEFAULT NULL, event_type VARCHAR(50) NOT NULL, description LONGTEXT DEFAULT NULL, data JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_4423BC00166D1F9C (project_id), INDEX IDX_4423BC0010DAF24A (actor_id), INDEX idx_project_created (project_id, created_at), INDEX idx_event_type (event_type), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE project_events ADD CONSTRAINT FK_4423BC00166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_events ADD CONSTRAINT FK_4423BC0010DAF24A FOREIGN KEY (actor_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE employment_periods CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT \'35\' NOT NULL, CHANGE work_time_percentage work_time_percentage NUMERIC(5, 2) DEFAULT \'100\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE project_events DROP FOREIGN KEY FK_4423BC00166D1F9C');
        $this->addSql('ALTER TABLE project_events DROP FOREIGN KEY FK_4423BC0010DAF24A');
        $this->addSql('DROP TABLE project_events');
        $this->addSql('ALTER TABLE employment_periods CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT \'35.00\' NOT NULL, CHANGE work_time_percentage work_time_percentage NUMERIC(5, 2) DEFAULT \'100.00\' NOT NULL');
    }
}
