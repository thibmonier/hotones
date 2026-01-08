<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251205103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des index de performance pour les tables critiques';
    }

    public function up(Schema $schema): void
    {
        // On vérifie d'abord si les tables existent pour éviter les erreurs lors d'une migration initiale
        try {
            $sm = $this->connection->createSchemaManager();
            $tables = $sm->listTableNames();
        } catch (\Throwable $e) {
            $tables = [];
        }
        
        $hasTable = fn($name) => count(array_filter($tables, fn($t) => strtolower($t) === strtolower($name))) > 0;

        // Timesheet indexes
        if ($hasTable('timesheet')) {
            $this->addSql('CREATE INDEX idx_timesheet_contributor_date ON timesheet (contributor_id, date)');
            $this->addSql('CREATE INDEX idx_timesheet_project_date ON timesheet (project_id, date)');
            $this->addSql('CREATE INDEX idx_timesheet_contributor_project_date ON timesheet (contributor_id, project_id, date)');
        }

        // Project indexes
        if ($hasTable('project')) {
            $this->addSql('CREATE INDEX idx_project_status_client ON project (status, client_id)');
            $this->addSql('CREATE INDEX idx_project_type ON project (type)');
        }

        // Order indexes
        if ($hasTable('order')) {
            $this->addSql('CREATE INDEX idx_order_status_created ON `order` (status, created_at)');
            $this->addSql('CREATE INDEX idx_order_client ON `order` (client_id)');
        }

        // Invoice indexes
        if ($hasTable('invoice')) {
            $this->addSql('CREATE INDEX idx_invoice_status_due ON invoice (status, due_date)');
            $this->addSql('CREATE INDEX idx_invoice_client ON invoice (client_id)');
        }

        // Planning indexes
        if ($hasTable('planning')) {
            $this->addSql('CREATE INDEX idx_planning_contributor_dates ON planning (contributor_id, start_date, end_date)');
            $this->addSql('CREATE INDEX idx_planning_project ON planning (project_id)');
        }

        // Vacation indexes
        if ($hasTable('vacation')) {
            $this->addSql('CREATE INDEX idx_vacation_contributor_status ON vacation (contributor_id, status)');
            $this->addSql('CREATE INDEX idx_vacation_dates ON vacation (start_date, end_date)');
        }

        // EmploymentPeriod indexes
        if ($hasTable('employment_period')) {
            $this->addSql('CREATE INDEX idx_employment_period_contributor ON employment_period (contributor_id)');
        }

        // Notification indexes
        if ($hasTable('notification')) {
            $this->addSql('CREATE INDEX idx_notification_user_read ON notification (user_id, is_read, created_at DESC)');
        }

        // Contributor indexes
        if ($hasTable('contributor')) {
            $this->addSql('CREATE INDEX idx_contributor_active ON contributor (active)');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_timesheet_contributor_date ON timesheet');
        $this->addSql('DROP INDEX idx_timesheet_project_date ON timesheet');
        $this->addSql('DROP INDEX idx_timesheet_contributor_project_date ON timesheet');

        $this->addSql('DROP INDEX idx_project_status_client ON project');
        $this->addSql('DROP INDEX idx_project_type ON project');

        $this->addSql('DROP INDEX idx_order_status_created ON `order`');
        $this->addSql('DROP INDEX idx_order_client ON `order`');

        $this->addSql('DROP INDEX idx_invoice_status_due ON invoice');
        $this->addSql('DROP INDEX idx_invoice_client ON invoice');

        $this->addSql('DROP INDEX idx_planning_contributor_dates ON planning');
        $this->addSql('DROP INDEX idx_planning_project ON planning');

        $this->addSql('DROP INDEX idx_vacation_contributor_status ON vacation');
        $this->addSql('DROP INDEX idx_vacation_dates ON vacation');

        $this->addSql('DROP INDEX idx_employment_period_contributor ON employment_period');

        $this->addSql('DROP INDEX idx_notification_user_read ON notification');

        $this->addSql('DROP INDEX idx_contributor_active ON contributor');
    }
}
