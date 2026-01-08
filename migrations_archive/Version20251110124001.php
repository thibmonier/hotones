<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251110124001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create running_timers table to support per-contributor active time tracking (project/task/subtask, startedAt/stoppedAt)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE running_timers (
            id INT AUTO_INCREMENT NOT NULL,
            contributor_id INT NOT NULL,
            project_id INT NOT NULL,
            task_id INT DEFAULT NULL,
            sub_task_id INT DEFAULT NULL,
            started_at DATETIME NOT NULL,
            stopped_at DATETIME DEFAULT NULL,
            INDEX IDX_RT_CONTRIBUTOR (contributor_id),
            INDEX IDX_RT_PROJECT (project_id),
            INDEX IDX_RT_TASK (task_id),
            INDEX IDX_RT_SUB_TASK (sub_task_id),
            INDEX IDX_RT_CONTRIBUTOR_ACTIVE (contributor_id, stopped_at),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE running_timers ADD CONSTRAINT FK_RT_CONTRIBUTOR FOREIGN KEY (contributor_id) REFERENCES contributors (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE running_timers ADD CONSTRAINT FK_RT_PROJECT FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE running_timers ADD CONSTRAINT FK_RT_TASK FOREIGN KEY (task_id) REFERENCES project_tasks (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE running_timers ADD CONSTRAINT FK_RT_SUB_TASK FOREIGN KEY (sub_task_id) REFERENCES project_sub_tasks (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE running_timers');
    }
}
