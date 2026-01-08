<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajoute les colonnes manquantes pour ProjectTask
 */
final class Version20251019180800 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les colonnes estimated_hours_sold, estimated_hours_revised, progress_percentage et assigned_contributor_id à project_task';
    }

    public function up(Schema $schema): void
    {
        // Ajouter les colonnes manquantes à project_tasks
        $this->addSql('ALTER TABLE project_tasks ADD COLUMN estimated_hours_sold INT DEFAULT NULL COMMENT "Heures vendues estimées"');
        $this->addSql('ALTER TABLE project_tasks ADD COLUMN estimated_hours_revised INT DEFAULT NULL COMMENT "Heures révisées estimées"');
        $this->addSql('ALTER TABLE project_tasks ADD COLUMN progress_percentage INT DEFAULT 0 COMMENT "Pourcentage d\'avancement (0-100)"');
        $this->addSql('ALTER TABLE project_tasks ADD COLUMN assigned_contributor_id INT DEFAULT NULL COMMENT "ID du contributeur assigné"');
        
        // Ajouter la clé étrangère pour assigned_contributor_id
        $this->addSql('ALTER TABLE project_tasks ADD CONSTRAINT FK_project_tasks_assigned_contributor FOREIGN KEY (assigned_contributor_id) REFERENCES contributors (id)');
        
        // Ajouter un index sur assigned_contributor_id
        $this->addSql('CREATE INDEX IDX_project_tasks_assigned_contributor ON project_tasks (assigned_contributor_id)');
    }

    public function down(Schema $schema): void
    {
        // Supprimer la contrainte de clé étrangère et l'index
        $this->addSql('ALTER TABLE project_tasks DROP FOREIGN KEY FK_project_tasks_assigned_contributor');
        $this->addSql('DROP INDEX IDX_project_tasks_assigned_contributor ON project_tasks');
        
        // Supprimer les colonnes
        $this->addSql('ALTER TABLE project_tasks DROP COLUMN assigned_contributor_id');
        $this->addSql('ALTER TABLE project_tasks DROP COLUMN progress_percentage');
        $this->addSql('ALTER TABLE project_tasks DROP COLUMN estimated_hours_revised');
        $this->addSql('ALTER TABLE project_tasks DROP COLUMN estimated_hours_sold');
    }
}