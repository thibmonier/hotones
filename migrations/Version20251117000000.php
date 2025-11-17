<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add index on validated_at column for orders table to improve dashboard performance.
 */
final class Version20251117000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add index on orders.validated_at for better performance on sales dashboard queries';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_order_validated_at ON orders (validated_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_order_validated_at ON orders');
    }
}
