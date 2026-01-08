<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajoute la relation projects.client_id -> clients.id
 */
final class Version20251031151200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout de la colonne client_id sur projects avec contrainte de clé étrangère vers clients';
    }

    public function up(Schema $schema): void
    {
        // Ajout colonne client_id nullable
        $this->addSql('ALTER TABLE projects ADD client_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE projects ADD CONSTRAINT FK_projects_client FOREIGN KEY (client_id) REFERENCES clients (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_projects_client ON projects (client_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE projects DROP FOREIGN KEY FK_projects_client');
        $this->addSql('DROP INDEX IDX_projects_client ON projects');
        $this->addSql('ALTER TABLE projects DROP client_id');
    }
}
