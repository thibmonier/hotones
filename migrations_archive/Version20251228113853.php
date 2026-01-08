<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251228113853 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add performance indexes for frequently queried columns';
    }

    public function up(Schema $schema): void
    {
        // Migration disabled due to schema inconsistencies
        // Performance indexes will be added when schema is stable
        // TODO: Re-enable after verifying all required columns exist
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE account_deletion_requests CHANGE requested_at requested_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE confirmed_at confirmed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE scheduled_deletion_at scheduled_deletion_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE cancelled_at cancelled_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE completed_at completed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE cookie_consents CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE expires_at expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE employment_periods CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT \'35.00\' NOT NULL, CHANGE work_time_percentage work_time_percentage NUMERIC(5, 2) DEFAULT \'100.00\' NOT NULL');

        // Drop performance indexes (reverse order)
        $this->addSql('DROP INDEX idx_validated_at ON orders');
        $this->addSql('DROP INDEX idx_order_status ON orders');
        $this->addSql('DROP INDEX idx_vacation_dates ON vacations');
        $this->addSql('DROP INDEX idx_contributor_status ON vacations');
        $this->addSql('DROP INDEX idx_status ON project_tasks');
        $this->addSql('DROP INDEX idx_project_position ON project_tasks');
        $this->addSql('DROP INDEX idx_dates ON employment_periods');
        $this->addSql('DROP INDEX idx_contributor_date ON timesheets');
        $this->addSql('DROP INDEX idx_date ON timesheets');
    }
}
