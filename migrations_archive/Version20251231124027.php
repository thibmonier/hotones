<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Lot 23 - Migration 5: Add company_id to Batch 3 (Orders)
 *
 * This migration adds company_id to the third batch of entities:
 * - orders (copy from projects, or default if no project)
 *   + IMPORTANT: Changes unique constraint order_number → (order_number, company_id)
 * - order_sections (copy from orders)
 * - order_lines (copy from orders via sections)
 * - order_payment_schedules (copy from orders)
 *
 * REVERSIBLE: down() removes company_id and restores original unique constraint
 */
final class Version20251231124027 extends AbstractMigration
{
    private const DEFAULT_COMPANY_ID = 1;

    public function getDescription(): string
    {
        return 'Lot 23 - Add company_id to orders, order_sections, order_lines, order_payment_schedules + modify order_number unique constraint';
    }

    public function up(Schema $schema): void
    {
        // ===================================================================
        // TABLE 1: orders
        // ===================================================================

        // STEP 1: Add company_id column (nullable)
        $this->addSql(<<<'SQL'
            ALTER TABLE orders
            ADD company_id INT NULL AFTER id
        SQL);

        // STEP 2a: Copy company_id from projects (for orders with a project)
        $this->addSql(<<<'SQL'
            UPDATE orders o
            INNER JOIN projects p ON o.project_id = p.id
            SET o.company_id = p.company_id
            WHERE o.project_id IS NOT NULL
        SQL);

        // STEP 2b: Orders without project get default company
        $this->addSql(<<<'SQL'
            UPDATE orders
            SET company_id = 1
            WHERE company_id IS NULL
        SQL);

        // STEP 3: Make NOT NULL
        $this->addSql(<<<'SQL'
            ALTER TABLE orders
            MODIFY company_id INT NOT NULL
        SQL);

        // STEP 4: Drop old unique constraint on order_number
        $this->addSql(<<<'SQL'
            ALTER TABLE orders
            DROP INDEX UNIQ_E52FFDEE551F0F81
        SQL);

        // STEP 5: Add composite unique constraint (order_number, company_id)
        $this->addSql(<<<'SQL'
            ALTER TABLE orders
            ADD CONSTRAINT order_number_company_unique
            UNIQUE (order_number, company_id)
        SQL);

        // STEP 6: Add index
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_order_company ON orders (company_id)
        SQL);

        // STEP 7: Add foreign key
        $this->addSql(<<<'SQL'
            ALTER TABLE orders
            ADD CONSTRAINT fk_order_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // ===================================================================
        // TABLE 2: order_sections
        // ===================================================================

        // STEP 1: Add company_id column (nullable)
        $this->addSql(<<<'SQL'
            ALTER TABLE order_sections
            ADD company_id INT NULL AFTER id
        SQL);

        // STEP 2: Copy company_id from orders
        $this->addSql(<<<'SQL'
            UPDATE order_sections os
            INNER JOIN orders o ON os.order_id = o.id
            SET os.company_id = o.company_id
        SQL);

        // STEP 3: Make NOT NULL
        $this->addSql(<<<'SQL'
            ALTER TABLE order_sections
            MODIFY company_id INT NOT NULL
        SQL);

        // STEP 4: Add index
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_order_section_company ON order_sections (company_id)
        SQL);

        // STEP 5: Add foreign key
        $this->addSql(<<<'SQL'
            ALTER TABLE order_sections
            ADD CONSTRAINT fk_order_section_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // ===================================================================
        // TABLE 3: order_lines
        // ===================================================================

        // STEP 1: Add company_id column (nullable)
        $this->addSql(<<<'SQL'
            ALTER TABLE order_lines
            ADD company_id INT NULL AFTER id
        SQL);

        // STEP 2: Copy company_id from orders (via sections)
        $this->addSql(<<<'SQL'
            UPDATE order_lines ol
            INNER JOIN order_sections os ON ol.section_id = os.id
            INNER JOIN orders o ON os.order_id = o.id
            SET ol.company_id = o.company_id
        SQL);

        // STEP 3: Make NOT NULL
        $this->addSql(<<<'SQL'
            ALTER TABLE order_lines
            MODIFY company_id INT NOT NULL
        SQL);

        // STEP 4: Add index
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_order_line_company ON order_lines (company_id)
        SQL);

        // STEP 5: Add foreign key
        $this->addSql(<<<'SQL'
            ALTER TABLE order_lines
            ADD CONSTRAINT fk_order_line_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);

        // ===================================================================
        // TABLE 4: order_payment_schedules
        // ===================================================================

        // STEP 1: Add company_id column (nullable)
        $this->addSql(<<<'SQL'
            ALTER TABLE order_payment_schedules
            ADD company_id INT NULL AFTER id
        SQL);

        // STEP 2: Copy company_id from orders
        $this->addSql(<<<'SQL'
            UPDATE order_payment_schedules ops
            INNER JOIN orders o ON ops.order_id = o.id
            SET ops.company_id = o.company_id
        SQL);

        // STEP 3: Make NOT NULL
        $this->addSql(<<<'SQL'
            ALTER TABLE order_payment_schedules
            MODIFY company_id INT NOT NULL
        SQL);

        // STEP 4: Add index
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_order_payment_schedule_company ON order_payment_schedules (company_id)
        SQL);

        // STEP 5: Add foreign key
        $this->addSql(<<<'SQL'
            ALTER TABLE order_payment_schedules
            ADD CONSTRAINT fk_order_payment_schedule_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // ===================================================================
        // REVERSE TABLE 4: order_payment_schedules
        // ===================================================================

        $this->addSql(<<<'SQL'
            ALTER TABLE order_payment_schedules
            DROP FOREIGN KEY fk_order_payment_schedule_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_order_payment_schedule_company ON order_payment_schedules
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE order_payment_schedules
            DROP COLUMN company_id
        SQL);

        // ===================================================================
        // REVERSE TABLE 3: order_lines
        // ===================================================================

        $this->addSql(<<<'SQL'
            ALTER TABLE order_lines
            DROP FOREIGN KEY fk_order_line_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_order_line_company ON order_lines
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE order_lines
            DROP COLUMN company_id
        SQL);

        // ===================================================================
        // REVERSE TABLE 2: order_sections
        // ===================================================================

        $this->addSql(<<<'SQL'
            ALTER TABLE order_sections
            DROP FOREIGN KEY fk_order_section_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_order_section_company ON order_sections
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE order_sections
            DROP COLUMN company_id
        SQL);

        // ===================================================================
        // REVERSE TABLE 1: orders
        // ===================================================================

        $this->addSql(<<<'SQL'
            ALTER TABLE orders
            DROP FOREIGN KEY fk_order_company
        SQL);

        $this->addSql(<<<'SQL'
            DROP INDEX idx_order_company ON orders
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE orders
            DROP INDEX order_number_company_unique
        SQL);

        // Restore original unique constraint on order_number
        $this->addSql(<<<'SQL'
            ALTER TABLE orders
            ADD CONSTRAINT UNIQ_E52FFDEE551F0F81
            UNIQUE (order_number)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE orders
            DROP COLUMN company_id
        SQL);
    }
}
