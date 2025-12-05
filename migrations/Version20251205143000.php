<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajout des champs signedOrderCount et lostOrderCount à fact_project_metrics.
 */
final class Version20251205143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute signedOrderCount et lostOrderCount à fact_project_metrics';
    }

    public function up(Schema $schema): void
    {
        // Ajouter les colonnes avec valeur par défaut 0
        $this->addSql('ALTER TABLE fact_project_metrics ADD signed_order_count INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE fact_project_metrics ADD lost_order_count INT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // Retirer les colonnes
        $this->addSql('ALTER TABLE fact_project_metrics DROP signed_order_count');
        $this->addSql('ALTER TABLE fact_project_metrics DROP lost_order_count');
    }
}
