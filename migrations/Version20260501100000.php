<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * US-068 — store the rejection reason on a vacation alongside the existing
 * reason column (which carries the contributor's request motif).
 */
final class Version20260501100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'US-068: ajout colonne rejection_reason sur vacations';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE vacations ADD rejection_reason LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE vacations DROP rejection_reason');
    }
}
