<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251031142954 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE project_sub_tasks (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, task_id INT NOT NULL, assignee_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, initial_estimated_hours NUMERIC(6, 2) NOT NULL, remaining_hours NUMERIC(6, 2) NOT NULL, status VARCHAR(20) NOT NULL, position INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_AD2044F9166D1F9C (project_id), INDEX IDX_AD2044F98DB60186 (task_id), INDEX IDX_AD2044F959EC7D60 (assignee_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE project_sub_tasks ADD CONSTRAINT FK_AD2044F9166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_sub_tasks ADD CONSTRAINT FK_AD2044F98DB60186 FOREIGN KEY (task_id) REFERENCES project_tasks (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_sub_tasks ADD CONSTRAINT FK_AD2044F959EC7D60 FOREIGN KEY (assignee_id) REFERENCES contributors (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE employment_periods CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT \'35\' NOT NULL, CHANGE work_time_percentage work_time_percentage NUMERIC(5, 2) DEFAULT \'100\' NOT NULL');
        $this->addSql('ALTER TABLE timesheets ADD sub_task_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE timesheets ADD CONSTRAINT FK_9AC77D2EF26E5D72 FOREIGN KEY (sub_task_id) REFERENCES project_sub_tasks (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_9AC77D2EF26E5D72 ON timesheets (sub_task_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE timesheets DROP FOREIGN KEY FK_9AC77D2EF26E5D72');
        $this->addSql('ALTER TABLE project_sub_tasks DROP FOREIGN KEY FK_AD2044F9166D1F9C');
        $this->addSql('ALTER TABLE project_sub_tasks DROP FOREIGN KEY FK_AD2044F98DB60186');
        $this->addSql('ALTER TABLE project_sub_tasks DROP FOREIGN KEY FK_AD2044F959EC7D60');
        $this->addSql('DROP TABLE project_sub_tasks');
        $this->addSql('DROP INDEX IDX_9AC77D2EF26E5D72 ON timesheets');
        $this->addSql('ALTER TABLE timesheets DROP sub_task_id');
        $this->addSql('ALTER TABLE employment_periods CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT \'35.00\' NOT NULL, CHANGE work_time_percentage work_time_percentage NUMERIC(5, 2) DEFAULT \'100.00\' NOT NULL');
    }
}
