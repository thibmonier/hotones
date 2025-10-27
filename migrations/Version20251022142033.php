<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251022142033 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE employment_periods CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT \'35\' NOT NULL, CHANGE work_time_percentage work_time_percentage NUMERIC(5, 2) DEFAULT \'100\' NOT NULL');
        $this->addSql('ALTER TABLE timesheets ADD task_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE timesheets ADD CONSTRAINT FK_9AC77D2E8DB60186 FOREIGN KEY (task_id) REFERENCES project_tasks (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_9AC77D2E8DB60186 ON timesheets (task_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE timesheets DROP FOREIGN KEY FK_9AC77D2E8DB60186');
        $this->addSql('DROP INDEX IDX_9AC77D2E8DB60186 ON timesheets');
        $this->addSql('ALTER TABLE timesheets DROP task_id');
        $this->addSql('ALTER TABLE employment_periods CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT \'35.00\' NOT NULL, CHANGE work_time_percentage work_time_percentage NUMERIC(5, 2) DEFAULT \'100.00\' NOT NULL');
    }
}
