<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add order_line_id to project_tasks to link tasks to budget lines from orders.
 */
final class Version20251108151552 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add order_line_id foreign key to project_tasks table to link tasks to order budget lines';
    }

    public function up(Schema $schema): void
    {
        // Add order_line_id column to project_tasks
        $this->addSql('ALTER TABLE project_tasks ADD COLUMN order_line_id INT DEFAULT NULL');
        
        // Add foreign key constraint
        $this->addSql('ALTER TABLE project_tasks ADD CONSTRAINT FK_DA05C8B3CC4B3E FOREIGN KEY (order_line_id) REFERENCES order_lines (id) ON DELETE SET NULL');
        
        // Add index for better performance
        $this->addSql('CREATE INDEX IDX_DA05C8B3CC4B3E ON project_tasks (order_line_id)');
    }

    public function down(Schema $schema): void
    {
        // Remove foreign key and column
        $this->addSql('ALTER TABLE project_tasks DROP FOREIGN KEY FK_DA05C8B3CC4B3E');
        $this->addSql('DROP INDEX IDX_DA05C8B3CC4B3E ON project_tasks');
        $this->addSql('ALTER TABLE project_tasks DROP COLUMN order_line_id');
    }
}
