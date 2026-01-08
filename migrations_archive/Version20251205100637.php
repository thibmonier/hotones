<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251205100637 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create Invoice and InvoiceLine tables + add signed/lost order counts to FactProjectMetrics';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE invoice_lines (id INT AUTO_INCREMENT NOT NULL, description LONGTEXT NOT NULL, quantity NUMERIC(10, 2) NOT NULL, unit VARCHAR(50) NOT NULL, unit_price_ht NUMERIC(12, 2) NOT NULL, total_ht NUMERIC(12, 2) NOT NULL, tva_rate NUMERIC(5, 2) NOT NULL, tva_amount NUMERIC(12, 2) NOT NULL, total_ttc NUMERIC(12, 2) NOT NULL, display_order INT NOT NULL, invoice_id INT NOT NULL, INDEX IDX_72DBDC232989F1FD (invoice_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE invoices (id INT AUTO_INCREMENT NOT NULL, invoice_number VARCHAR(50) NOT NULL, status VARCHAR(20) NOT NULL, issued_at DATE NOT NULL, due_date DATE NOT NULL, paid_at DATE DEFAULT NULL, amount_ht NUMERIC(12, 2) NOT NULL, amount_tva NUMERIC(12, 2) NOT NULL, tva_rate NUMERIC(5, 2) NOT NULL, amount_ttc NUMERIC(12, 2) NOT NULL, internal_notes LONGTEXT DEFAULT NULL, payment_terms LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, order_id INT DEFAULT NULL, project_id INT DEFAULT NULL, client_id INT NOT NULL, payment_schedule_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_6A2F2F952DA68207 (invoice_number), INDEX idx_invoice_number (invoice_number), INDEX idx_invoice_status (status), INDEX idx_invoice_issued_at (issued_at), INDEX idx_invoice_due_date (due_date), INDEX idx_invoice_paid_at (paid_at), INDEX idx_invoice_client (client_id), INDEX IDX_6A2F2F958D9F6D38 (order_id), INDEX IDX_6A2F2F95166D1F9C (project_id), INDEX IDX_6A2F2F9519EB6921 (client_id), INDEX IDX_6A2F2F955287120F (payment_schedule_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE invoice_lines ADD CONSTRAINT FK_72DBDC232989F1FD FOREIGN KEY (invoice_id) REFERENCES invoices (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE invoices ADD CONSTRAINT FK_6A2F2F958D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE invoices ADD CONSTRAINT FK_6A2F2F95166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE invoices ADD CONSTRAINT FK_6A2F2F9519EB6921 FOREIGN KEY (client_id) REFERENCES clients (id)');
        $this->addSql('ALTER TABLE invoices ADD CONSTRAINT FK_6A2F2F955287120F FOREIGN KEY (payment_schedule_id) REFERENCES order_payment_schedules (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE achievements CHANGE unlocked_at unlocked_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE badges CHANGE criteria criteria JSON DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE contributor_progress CHANGE last_xp_gained_at last_xp_gained_at DATETIME NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE employment_periods CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT 35 NOT NULL, CHANGE work_time_percentage work_time_percentage NUMERIC(5, 2) DEFAULT 100 NOT NULL');
        $this->addSql('ALTER TABLE fact_project_metrics ADD signed_order_count INT NOT NULL, ADD lost_order_count INT NOT NULL');
        $this->addSql('ALTER TABLE notifications CHANGE read_at read_at DATETIME DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('DROP INDEX idx_order_status_created ON orders');
        $this->addSql('ALTER TABLE project_events CHANGE data data JSON DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE project_sub_tasks CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('DROP INDEX idx_project_dates_status ON projects');
        $this->addSql('DROP INDEX idx_project_status_type ON projects');
        $this->addSql('ALTER TABLE scheduler_entries CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('DROP INDEX idx_timesheet_contributor_date ON timesheets');
        $this->addSql('DROP INDEX idx_timesheet_project_date ON timesheets');
        $this->addSql('ALTER TABLE users CHANGE roles roles JSON NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE xp_history CHANGE metadata metadata JSON DEFAULT NULL, CHANGE gained_at gained_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE messenger_messages CHANGE created_at created_at DATETIME NOT NULL, CHANGE available_at available_at DATETIME NOT NULL, CHANGE delivered_at delivered_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE invoice_lines DROP FOREIGN KEY FK_72DBDC232989F1FD');
        $this->addSql('ALTER TABLE invoices DROP FOREIGN KEY FK_6A2F2F958D9F6D38');
        $this->addSql('ALTER TABLE invoices DROP FOREIGN KEY FK_6A2F2F95166D1F9C');
        $this->addSql('ALTER TABLE invoices DROP FOREIGN KEY FK_6A2F2F9519EB6921');
        $this->addSql('ALTER TABLE invoices DROP FOREIGN KEY FK_6A2F2F955287120F');
        $this->addSql('DROP TABLE invoice_lines');
        $this->addSql('DROP TABLE invoices');
        $this->addSql('ALTER TABLE achievements CHANGE unlocked_at unlocked_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE badges CHANGE criteria criteria JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE contributor_progress CHANGE last_xp_gained_at last_xp_gained_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE employment_periods CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT \'35.00\' NOT NULL, CHANGE work_time_percentage work_time_percentage NUMERIC(5, 2) DEFAULT \'100.00\' NOT NULL');
        $this->addSql('ALTER TABLE fact_project_metrics DROP signed_order_count, DROP lost_order_count');
        $this->addSql('ALTER TABLE messenger_messages CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE available_at available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE delivered_at delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE notifications CHANGE read_at read_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE INDEX idx_order_status_created ON orders (status, created_at)');
        $this->addSql('CREATE INDEX idx_project_dates_status ON projects (status, start_date, end_date)');
        $this->addSql('CREATE INDEX idx_project_status_type ON projects (status, project_type)');
        $this->addSql('ALTER TABLE project_events CHANGE data data JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE project_sub_tasks CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE scheduler_entries CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE INDEX idx_timesheet_contributor_date ON timesheets (contributor_id, date)');
        $this->addSql('CREATE INDEX idx_timesheet_project_date ON timesheets (project_id, date)');
        $this->addSql('ALTER TABLE users CHANGE roles roles JSON NOT NULL COMMENT \'(DC2Type:json)\', CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE xp_history CHANGE metadata metadata JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE gained_at gained_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }
}
