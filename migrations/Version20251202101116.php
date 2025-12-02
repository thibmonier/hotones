<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251202101116 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add composite indexes for performance optimization on timesheets and projects tables';
    }

    public function up(Schema $schema): void
    {
        // Add composite indexes for performance optimization

        // Timesheets: Composite index for contributor + date queries (most frequent pattern)
        $this->addSql('CREATE INDEX idx_timesheet_contributor_date ON timesheets (contributor_id, date)');

        // Timesheets: Composite index for project + date queries
        $this->addSql('CREATE INDEX idx_timesheet_project_date ON timesheets (project_id, date)');

        // Projects: Composite index for status + project_type (often filtered together)
        $this->addSql('CREATE INDEX idx_project_status_type ON projects (status, project_type)');

        // Projects: Composite index for date range queries with status
        $this->addSql('CREATE INDEX idx_project_dates_status ON projects (status, start_date, end_date)');

        // Orders: Composite index for status + created_at (analytics queries)
        $this->addSql('CREATE INDEX idx_order_status_created ON orders (status, created_at)');
    }

    public function down(Schema $schema): void
    {
        // Remove composite indexes
        $this->addSql('DROP INDEX idx_timesheet_contributor_date ON timesheets');
        $this->addSql('DROP INDEX idx_timesheet_project_date ON timesheets');
        $this->addSql('DROP INDEX idx_project_status_type ON projects');
        $this->addSql('DROP INDEX idx_project_dates_status ON projects');
        $this->addSql('DROP INDEX idx_order_status_created ON orders');
    }
}
