<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251025125640 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE employment_periods CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT \'35\' NOT NULL, CHANGE work_time_percentage work_time_percentage NUMERIC(5, 2) DEFAULT \'100\' NOT NULL');
        $this->addSql('ALTER TABLE order_tasks ADD profile_id INT NOT NULL, DROP profile');
        $this->addSql('ALTER TABLE order_tasks ADD CONSTRAINT FK_D3C6116ACCFA12B8 FOREIGN KEY (profile_id) REFERENCES profiles (id)');
        $this->addSql('CREATE INDEX IDX_D3C6116ACCFA12B8 ON order_tasks (profile_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE employment_periods CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT \'35.00\' NOT NULL, CHANGE work_time_percentage work_time_percentage NUMERIC(5, 2) DEFAULT \'100.00\' NOT NULL');
        $this->addSql('ALTER TABLE order_tasks DROP FOREIGN KEY FK_D3C6116ACCFA12B8');
        $this->addSql('DROP INDEX IDX_D3C6116ACCFA12B8 ON order_tasks');
        $this->addSql('ALTER TABLE order_tasks ADD profile VARCHAR(50) NOT NULL, DROP profile_id');
    }
}
