<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Crée les tables clients et client_contacts
 */
final class Version20251031150700 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création des tables clients et client_contacts (gestion des clients et de leurs contacts)';
    }

    public function up(Schema $schema): void
    {
        // clients
        $this->addSql('CREATE TABLE clients (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(180) NOT NULL,
            logo_path VARCHAR(255) DEFAULT NULL,
            website VARCHAR(255) DEFAULT NULL,
            description LONGTEXT DEFAULT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // client_contacts
        $this->addSql('CREATE TABLE client_contacts (
            id INT AUTO_INCREMENT NOT NULL,
            client_id INT NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            first_name VARCHAR(100) NOT NULL,
            email VARCHAR(180) DEFAULT NULL,
            phone VARCHAR(50) DEFAULT NULL,
            mobile_phone VARCHAR(50) DEFAULT NULL,
            position_title VARCHAR(120) DEFAULT NULL,
            active TINYINT(1) NOT NULL DEFAULT 1,
            INDEX IDX_client_contacts_client (client_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE client_contacts ADD CONSTRAINT FK_client_contacts_client FOREIGN KEY (client_id) REFERENCES clients (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE client_contacts DROP FOREIGN KEY FK_client_contacts_client');
        $this->addSql('DROP TABLE client_contacts');
        $this->addSql('DROP TABLE clients');
    }
}
