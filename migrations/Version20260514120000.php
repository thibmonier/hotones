<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * EPIC-003 Phase 5 sprint-025 US-114 T-114-02.
 *
 * Add index on `orders.valid_until` — la query du read-model
 * RevenueForecast filtre sur l'horizon `valid_until BETWEEN now AND now+90j`.
 * Sans index : full scan sur la table orders.
 *
 * Migration idempotente — `CREATE INDEX IF NOT EXISTS` (MariaDB 10.5+ / MySQL 8).
 */
final class Version20260514120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'US-114 T-114-02 — index orders.valid_until pour query RevenueForecast read-model';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_order_valid_until ON orders (valid_until)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_order_valid_until ON orders');
    }
}
