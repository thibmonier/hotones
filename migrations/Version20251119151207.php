<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251119151207 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add manager relationship to contributors table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contributors ADD manager_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE contributors ADD CONSTRAINT FK_72D26262783E3463 FOREIGN KEY (manager_id) REFERENCES contributors (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_72D26262783E3463 ON contributors (manager_id)');
        $this->addSql('ALTER TABLE employment_periods CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT \'35\' NOT NULL, CHANGE work_time_percentage work_time_percentage NUMERIC(5, 2) DEFAULT \'100\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contributors DROP FOREIGN KEY FK_72D26262783E3463');
        $this->addSql('DROP INDEX IDX_72D26262783E3463 ON contributors');
        $this->addSql('ALTER TABLE contributors DROP manager_id');
        $this->addSql('ALTER TABLE employment_periods CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT \'35.00\' NOT NULL, CHANGE work_time_percentage work_time_percentage NUMERIC(5, 2) DEFAULT \'100.00\' NOT NULL');
    }
}
