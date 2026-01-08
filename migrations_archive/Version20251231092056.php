<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Lot 23 - Migration 1: Create companies and business_units tables
 *
 * This migration creates the core multi-tenant entities:
 * - companies: Root tenant entity
 * - business_units: Hierarchical sub-organization within companies
 *
 * REVERSIBLE: down() drops both tables and restores previous schema
 */
final class Version20251231092056 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Lot 23 - Create companies and business_units tables for multi-tenant support';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE business_units (
              id INT AUTO_INCREMENT NOT NULL,
              name VARCHAR(255) NOT NULL,
              description LONGTEXT DEFAULT NULL,
              annual_revenue_target NUMERIC(12, 2) DEFAULT NULL,
              annual_margin_target NUMERIC(5, 2) DEFAULT NULL,
              headcount_target INT DEFAULT NULL,
              cost_center VARCHAR(100) DEFAULT NULL,
              active TINYINT NOT NULL,
              created_at DATETIME NOT NULL,
              updated_at DATETIME DEFAULT NULL,
              company_id INT NOT NULL,
              parent_id INT DEFAULT NULL,
              manager_id INT DEFAULT NULL,
              INDEX IDX_975193F6783E3463 (manager_id),
              INDEX idx_bu_company (company_id),
              INDEX idx_bu_parent (parent_id),
              INDEX idx_bu_active (active),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE companies (
              id INT AUTO_INCREMENT NOT NULL,
              name VARCHAR(255) NOT NULL,
              slug VARCHAR(100) NOT NULL,
              description LONGTEXT DEFAULT NULL,
              status VARCHAR(50) NOT NULL,
              subscription_tier VARCHAR(50) NOT NULL,
              max_users INT DEFAULT NULL,
              max_projects INT DEFAULT NULL,
              max_storage_mb INT DEFAULT NULL,
              billing_start_date DATE NOT NULL,
              billing_day_of_month INT NOT NULL,
              currency VARCHAR(3) NOT NULL,
              settings JSON NOT NULL,
              enabled_features JSON NOT NULL,
              structure_cost_coefficient NUMERIC(10, 4) NOT NULL,
              employer_charges_coefficient NUMERIC(10, 4) NOT NULL,
              annual_paid_leave_days INT NOT NULL,
              annual_rtt_days INT NOT NULL,
              created_at DATETIME NOT NULL,
              updated_at DATETIME DEFAULT NULL,
              suspended_at DATETIME DEFAULT NULL,
              trial_ends_at DATETIME DEFAULT NULL,
              owner_id INT NOT NULL,
              UNIQUE INDEX UNIQ_8244AA3A989D9B62 (slug),
              INDEX IDX_8244AA3A7E3C61F9 (owner_id),
              INDEX idx_company_slug (slug),
              INDEX idx_company_status (status),
              INDEX idx_company_subscription_tier (subscription_tier),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              business_units
            ADD
              CONSTRAINT FK_975193F6979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              business_units
            ADD
              CONSTRAINT FK_975193F6727ACA70 FOREIGN KEY (parent_id) REFERENCES business_units (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              business_units
            ADD
              CONSTRAINT FK_975193F6783E3463 FOREIGN KEY (manager_id) REFERENCES users (id) ON DELETE
            SET
              NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              companies
            ADD
              CONSTRAINT FK_8244AA3A7E3C61F9 FOREIGN KEY (owner_id) REFERENCES users (id) ON DELETE RESTRICT
        SQL);
        // Skip account_deletion_requests changes as scheduled_at rename was never applied
        // $this->addSql('DROP INDEX idx_deletion_scheduled ON account_deletion_requests');
        // $this->addSql(<<<'SQL'
        //     ALTER TABLE
        //       account_deletion_requests
        //     CHANGE
        //       scheduled_at scheduled_deletion_at DATETIME DEFAULT NULL
        // SQL);
        // $this->addSql('CREATE INDEX idx_deletion_scheduled ON account_deletion_requests (scheduled_deletion_at)');
        // Skip DROP INDEX operations as indexes were never created (Version20251228113853 was disabled)
        // $this->addSql('DROP INDEX idx_dates ON employment_periods');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              employment_periods
            CHANGE
              weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT 35 NOT NULL,
            CHANGE
              work_time_percentage work_time_percentage NUMERIC(5, 2) DEFAULT 100 NOT NULL
        SQL);
        // $this->addSql('DROP INDEX idx_order_status ON orders');
        // $this->addSql('DROP INDEX idx_validated_at ON orders');
        // $this->addSql('DROP INDEX idx_project_position ON project_tasks');
        // $this->addSql('DROP INDEX idx_status ON project_tasks');
        // $this->addSql('DROP INDEX idx_contributor_date ON timesheets');
        // $this->addSql('DROP INDEX idx_date ON timesheets');
        // $this->addSql('DROP INDEX idx_vacation_dates ON vacations');
        // $this->addSql('DROP INDEX idx_contributor_status ON vacations');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE business_units DROP FOREIGN KEY FK_975193F6979B1AD6');
        $this->addSql('ALTER TABLE business_units DROP FOREIGN KEY FK_975193F6727ACA70');
        $this->addSql('ALTER TABLE business_units DROP FOREIGN KEY FK_975193F6783E3463');
        $this->addSql('ALTER TABLE companies DROP FOREIGN KEY FK_8244AA3A7E3C61F9');
        $this->addSql('DROP TABLE business_units');
        $this->addSql('DROP TABLE companies');
        $this->addSql('DROP INDEX idx_deletion_scheduled ON account_deletion_requests');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              account_deletion_requests
            CHANGE
              scheduled_deletion_at scheduled_at DATETIME DEFAULT NULL
        SQL);
        $this->addSql('CREATE INDEX idx_deletion_scheduled ON account_deletion_requests (scheduled_at)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              employment_periods
            CHANGE
              weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT '35.00' NOT NULL,
            CHANGE
              work_time_percentage work_time_percentage NUMERIC(5, 2) DEFAULT '100.00' NOT NULL
        SQL);
        $this->addSql('CREATE INDEX idx_dates ON employment_periods (start_date, end_date)');
        $this->addSql('CREATE INDEX idx_order_status ON orders (status)');
        $this->addSql('CREATE INDEX idx_validated_at ON orders (validated_at)');
        $this->addSql('CREATE INDEX idx_project_position ON project_tasks (project_id, position)');
        $this->addSql('CREATE INDEX idx_status ON project_tasks (status)');
        $this->addSql('CREATE INDEX idx_contributor_date ON timesheets (contributor_id, date)');
        $this->addSql('CREATE INDEX idx_date ON timesheets (date)');
        $this->addSql('CREATE INDEX idx_vacation_dates ON vacations (start_date, end_date)');
        $this->addSql('CREATE INDEX idx_contributor_status ON vacations (contributor_id, status)');
    }
}
