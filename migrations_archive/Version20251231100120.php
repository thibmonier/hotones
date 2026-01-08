<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Lot 23 - Migration 4: Add company_id to Batch 2 (Projects)
 *
 * This migration adds company_id to the second batch of entities:
 * - clients (assign all to default company id=1)
 * - projects (copy from clients, or default if no client)
 * - client_contacts (copy from clients)
 * - project_tasks (copy from projects)
 * - project_sub_tasks (copy from projects)
 *
 * REVERSIBLE: down() removes company_id from all tables
 */
final class Version20251231100120 extends AbstractMigration
{
    private const DEFAULT_COMPANY_ID = 1;

    public function getDescription(): string
    {
        return 'Lot 23 - Add company_id to clients, projects, client_contacts, project_tasks, project_sub_tasks';
    }

    public function up(Schema $schema): void
    {
        // ===================================================================
        // TABLE 1: clients
        // ===================================================================

        // STEP 1: Add company_id column (nullable)
        $this->addSql(<<<'SQL'
            ALTER TABLE clients
            ADD company_id INT NULL AFTER id
        SQL);

        // STEP 2: Assign all existing clients to default company
        // Clients have no direct user/company relation, so all go to default company
        $this->addSql(<<<'SQL'
            UPDATE clients
            SET company_id = 1
            WHERE company_id IS NULL
        SQL);

        // STEP 3: Make NOT NULL
        $this->addSql(<<<'SQL'
            ALTER TABLE clients
            MODIFY company_id INT NOT NULL
        SQL);

        // STEP 4: Add index
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_client_company ON clients (company_id)
        SQL);

        // STEP 5: Add foreign key
        $this->addSql(<<<'SQL'
            ALTER TABLE clients
            ADD CONSTRAINT fk_client_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // ===================================================================
        // TABLE 2: projects
        // ===================================================================

        // STEP 1: Add company_id column (nullable)
        $this->addSql(<<<'SQL'
            ALTER TABLE projects
            ADD company_id INT NULL AFTER id
        SQL);

        // STEP 2a: Copy company_id from clients (for projects with a client)
        $this->addSql(<<<'SQL'
            UPDATE projects p
            INNER JOIN clients c ON p.client_id = c.id
            SET p.company_id = c.company_id
            WHERE p.client_id IS NOT NULL
        SQL);

        // STEP 2b: Projects without client get default company
        $this->addSql(<<<'SQL'
            UPDATE projects
            SET company_id = 1
            WHERE company_id IS NULL
        SQL);

        // STEP 3: Make NOT NULL
        $this->addSql(<<<'SQL'
            ALTER TABLE projects
            MODIFY company_id INT NOT NULL
        SQL);

        // STEP 4: Add index
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_project_company ON projects (company_id)
        SQL);

        // STEP 5: Add foreign key
        $this->addSql(<<<'SQL'
            ALTER TABLE projects
            ADD CONSTRAINT fk_project_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // ===================================================================
        // TABLE 3: client_contacts
        // ===================================================================

        // STEP 1: Add company_id column (nullable)
        $this->addSql(<<<'SQL'
            ALTER TABLE client_contacts
            ADD company_id INT NULL AFTER id
        SQL);

        // STEP 2: Copy company_id from clients
        $this->addSql(<<<'SQL'
            UPDATE client_contacts cc
            INNER JOIN clients c ON cc.client_id = c.id
            SET cc.company_id = c.company_id
        SQL);

        // STEP 3: Make NOT NULL
        $this->addSql(<<<'SQL'
            ALTER TABLE client_contacts
            MODIFY company_id INT NOT NULL
        SQL);

        // STEP 4: Add index
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_client_contact_company ON client_contacts (company_id)
        SQL);

        // STEP 5: Add foreign key
        $this->addSql(<<<'SQL'
            ALTER TABLE client_contacts
            ADD CONSTRAINT fk_client_contact_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // ===================================================================
        // TABLE 4: project_tasks
        // ===================================================================

        // STEP 1: Add company_id column (nullable)
        $this->addSql(<<<'SQL'
            ALTER TABLE project_tasks
            ADD company_id INT NULL AFTER id
        SQL);

        // STEP 2: Copy company_id from projects
        $this->addSql(<<<'SQL'
            UPDATE project_tasks pt
            INNER JOIN projects p ON pt.project_id = p.id
            SET pt.company_id = p.company_id
        SQL);

        // STEP 3: Make NOT NULL
        $this->addSql(<<<'SQL'
            ALTER TABLE project_tasks
            MODIFY company_id INT NOT NULL
        SQL);

        // STEP 4: Add index
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_project_task_company ON project_tasks (company_id)
        SQL);

        // STEP 5: Add foreign key
        $this->addSql(<<<'SQL'
            ALTER TABLE project_tasks
            ADD CONSTRAINT fk_project_task_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // ===================================================================
        // TABLE 5: project_sub_tasks
        // ===================================================================

        // STEP 1: Add company_id column (nullable)
        $this->addSql(<<<'SQL'
            ALTER TABLE project_sub_tasks
            ADD company_id INT NULL AFTER id
        SQL);

        // STEP 2: Copy company_id from projects
        $this->addSql(<<<'SQL'
            UPDATE project_sub_tasks pst
            INNER JOIN projects p ON pst.project_id = p.id
            SET pst.company_id = p.company_id
        SQL);

        // STEP 3: Make NOT NULL
        $this->addSql(<<<'SQL'
            ALTER TABLE project_sub_tasks
            MODIFY company_id INT NOT NULL
        SQL);

        // STEP 4: Add index
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_project_sub_task_company ON project_sub_tasks (company_id)
        SQL);

        // STEP 5: Add foreign key
        $this->addSql(<<<'SQL'
            ALTER TABLE project_sub_tasks
            ADD CONSTRAINT fk_project_sub_task_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // ===================================================================
        // REVERSE TABLE 5: project_sub_tasks
        // ===================================================================

        $this->addSql(<<<'SQL'
            ALTER TABLE project_sub_tasks
            DROP FOREIGN KEY fk_project_sub_task_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_project_sub_task_company ON project_sub_tasks
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE project_sub_tasks
            DROP COLUMN company_id
        SQL);

        // ===================================================================
        // REVERSE TABLE 4: project_tasks
        // ===================================================================

        $this->addSql(<<<'SQL'
            ALTER TABLE project_tasks
            DROP FOREIGN KEY fk_project_task_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_project_task_company ON project_tasks
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE project_tasks
            DROP COLUMN company_id
        SQL);

        // ===================================================================
        // REVERSE TABLE 3: client_contacts
        // ===================================================================

        $this->addSql(<<<'SQL'
            ALTER TABLE client_contacts
            DROP FOREIGN KEY fk_client_contact_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_client_contact_company ON client_contacts
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE client_contacts
            DROP COLUMN company_id
        SQL);

        // ===================================================================
        // REVERSE TABLE 2: projects
        // ===================================================================

        $this->addSql(<<<'SQL'
            ALTER TABLE projects
            DROP FOREIGN KEY fk_project_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_project_company ON projects
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE projects
            DROP COLUMN company_id
        SQL);

        // ===================================================================
        // REVERSE TABLE 1: clients
        // ===================================================================

        $this->addSql(<<<'SQL'
            ALTER TABLE clients
            DROP FOREIGN KEY fk_client_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_client_company ON clients
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE clients
            DROP COLUMN company_id
        SQL);
    }
}
