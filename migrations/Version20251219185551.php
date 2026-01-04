<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251219185551 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add nurturing tracking fields to lead_captures';
    }

    public function up(Schema $schema): void
    {
        // Check if table exists by querying database directly (schema diff doesn't reflect actual DB state)
        $tableExists = $this->connection->createSchemaManager()->tablesExist(['lead_captures']);

        if ($tableExists) {
            // Add nurturing tracking fields
            $this->addSql('ALTER TABLE lead_captures ADD status VARCHAR(50) DEFAULT \'new\' NOT NULL, ADD nurturing_day1_sent_at DATETIME DEFAULT NULL, ADD nurturing_day3_sent_at DATETIME DEFAULT NULL, ADD nurturing_day7_sent_at DATETIME DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        // Check if table exists by querying database directly (schema diff doesn't reflect actual DB state)
        $tableExists = $this->connection->createSchemaManager()->tablesExist(['lead_captures']);

        if ($tableExists) {
            // Remove nurturing tracking fields
            $this->addSql('ALTER TABLE lead_captures DROP status, DROP nurturing_day1_sent_at, DROP nurturing_day3_sent_at, DROP nurturing_day7_sent_at');
        }
    }
}
