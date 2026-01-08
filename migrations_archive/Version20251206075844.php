<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251206075844 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add login tracking fields (last_login_at, last_login_ip) to users table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE employment_periods CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT 35 NOT NULL, CHANGE work_time_percentage work_time_percentage NUMERIC(5, 2) DEFAULT 100 NOT NULL');
        $this->addSql('DROP INDEX idx_invoice_paid_at ON invoices');
        $this->addSql('DROP INDEX idx_invoice_status ON invoices');
        $this->addSql('DROP INDEX idx_invoice_client ON invoices');
        $this->addSql('DROP INDEX idx_invoice_issued_at ON invoices');
        $this->addSql('DROP INDEX idx_invoice_due_date ON invoices');
        $this->addSql('DROP INDEX idx_invoice_number ON invoices');
        $this->addSql('DROP INDEX idx_planning_project ON planning');
        $this->addSql('DROP INDEX idx_planning_contributor_dates ON planning');
        $this->addSql('ALTER TABLE users ADD last_login_at DATETIME DEFAULT NULL, ADD last_login_ip VARCHAR(45) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE employment_periods CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT \'35.00\' NOT NULL, CHANGE work_time_percentage work_time_percentage NUMERIC(5, 2) DEFAULT \'100.00\' NOT NULL');
        $this->addSql('CREATE INDEX idx_invoice_paid_at ON invoices (paid_at)');
        $this->addSql('CREATE INDEX idx_invoice_status ON invoices (status)');
        $this->addSql('CREATE INDEX idx_invoice_client ON invoices (client_id)');
        $this->addSql('CREATE INDEX idx_invoice_issued_at ON invoices (issued_at)');
        $this->addSql('CREATE INDEX idx_invoice_due_date ON invoices (due_date)');
        $this->addSql('CREATE INDEX idx_invoice_number ON invoices (invoice_number)');
        $this->addSql('CREATE INDEX idx_planning_project ON planning (project_id)');
        $this->addSql('CREATE INDEX idx_planning_contributor_dates ON planning (contributor_id, start_date, end_date)');
        $this->addSql('ALTER TABLE users DROP last_login_at, DROP last_login_ip');
    }
}
