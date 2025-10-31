<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migre les anciens noms de client (projects.client texte) vers des entités Client et lie les projets
 */
final class Version20251031152000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migration des valeurs textuelles projects.client vers la FK client_id + suppression de l\'ancienne colonne';
    }

    public function up(Schema $schema): void
    {
        // Créer les clients manquants à partir des noms distincts
        $this->addSql("INSERT INTO clients (name)
            SELECT DISTINCT TRIM(p.client) AS name
            FROM projects p
            WHERE p.client IS NOT NULL AND TRIM(p.client) <> ''
              AND NOT EXISTS (
                SELECT 1 FROM clients c WHERE c.name = TRIM(p.client)
              )");

        // Renseigner la FK client_id à partir de la correspondance sur le nom
        $this->addSql("UPDATE projects p
            INNER JOIN clients c ON c.name = p.client
            SET p.client_id = c.id
            WHERE p.client IS NOT NULL");

        // Supprimer l'ancienne colonne texte
        $this->addSql("ALTER TABLE projects DROP COLUMN client");
    }

    public function down(Schema $schema): void
    {
        // Réintroduire la colonne texte et la re-remplir depuis la FK
        $this->addSql("ALTER TABLE projects ADD client VARCHAR(180) DEFAULT NULL");
        $this->addSql("UPDATE projects p
            LEFT JOIN clients c ON c.id = p.client_id
            SET p.client = c.name");
    }
}
