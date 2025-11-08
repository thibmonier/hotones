<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add composite indexes to speed up period queries on timesheets.
 */
final class Version20251108090122 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add composite indexes on timesheets (project_id, date) and (contributor_id, date)';
    }

    public function up(Schema $schema): void
    {
        // MariaDB/MySQL
        $this->addSql('CREATE INDEX idx_timesheet_project_date ON timesheets (project_id, date)');
        $this->addSql('CREATE INDEX idx_timesheet_contributor_date ON timesheets (contributor_id, date)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_timesheet_project_date ON timesheets');
        $this->addSql('DROP INDEX idx_timesheet_contributor_date ON timesheets');
    }
}