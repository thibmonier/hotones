<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251221075243 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Refonte du module SaaS: separation Vendor/Provider et fusion Service/Subscription';
    }

    public function up(Schema $schema): void
    {
        // Étape 1: Créer les nouvelles tables
        $this->addSql('CREATE TABLE saas_distribution_providers (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(50) DEFAULT \'other\' NOT NULL, notes LONGTEXT DEFAULT NULL, active TINYINT DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE saas_vendors (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, website VARCHAR(500) DEFAULT NULL, contact_email VARCHAR(255) DEFAULT NULL, contact_phone VARCHAR(50) DEFAULT NULL, logo_url VARCHAR(500) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, active TINYINT DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE saas_subscriptions_v2 (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, category VARCHAR(100) DEFAULT NULL, service_url VARCHAR(500) DEFAULT NULL, logo_url VARCHAR(500) DEFAULT NULL, billing_period VARCHAR(20) NOT NULL, price NUMERIC(10, 2) NOT NULL, currency VARCHAR(3) DEFAULT \'EUR\' NOT NULL, quantity INT DEFAULT 1 NOT NULL, start_date DATE NOT NULL, end_date DATE DEFAULT NULL, next_renewal_date DATE DEFAULT NULL, last_renewal_date DATE DEFAULT NULL, auto_renewal TINYINT DEFAULT 1 NOT NULL, status VARCHAR(20) DEFAULT \'active\' NOT NULL, external_reference VARCHAR(255) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, active TINYINT DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, vendor_id INT NOT NULL, provider_id INT DEFAULT NULL, INDEX idx_subscription_vendor (vendor_id), INDEX idx_subscription_provider (provider_id), INDEX idx_subscription_status (status), INDEX idx_subscription_renewal (next_renewal_date), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');

        // Étape 2: Migrer les données de saas_providers vers saas_vendors
        $this->addSql('INSERT INTO saas_vendors (id, name, website, contact_email, contact_phone, logo_url, notes, active, created_at, updated_at)
                      SELECT id, name, website, contact_email, contact_phone, logo_url, notes, active, created_at, updated_at
                      FROM saas_providers');

        // Étape 3: Créer un vendor "Inconnu" pour les services sans provider
        $this->addSql('INSERT INTO saas_vendors (name, website, notes, active, created_at)
                      VALUES (\'Inconnu\', NULL, \'Fournisseur non spécifié (données migrées)\', 1, NOW())');

        // Étape 4: Créer un provider "Direct" par défaut
        $this->addSql('INSERT INTO saas_distribution_providers (name, type, notes, active, created_at)
                      VALUES (\'Direct\', \'direct\', \'Abonnements souscrits directement auprès du fournisseur\', 1, NOW())');

        // Étape 5: Migrer les données de saas_services + saas_subscriptions vers saas_subscriptions_v2
        // On fusionne les deux tables : les infos du service + les infos de l\'abonnement
        // Si provider_id est NULL, on utilise le vendor "Inconnu" créé ci-dessus
        $this->addSql('INSERT INTO saas_subscriptions_v2
                      (name, description, vendor_id, provider_id, category, service_url, logo_url,
                       billing_period, price, currency, quantity, start_date, end_date,
                       next_renewal_date, last_renewal_date, auto_renewal, status,
                       external_reference, notes, active, created_at, updated_at)
                      SELECT
                        srv.name,
                        srv.description,
                        COALESCE(srv.provider_id, (SELECT id FROM saas_vendors WHERE name = \'Inconnu\' LIMIT 1)),
                        NULL,
                        srv.category,
                        srv.service_url,
                        srv.logo_url,
                        sub.billing_period,
                        sub.price,
                        sub.currency,
                        sub.quantity,
                        sub.start_date,
                        sub.end_date,
                        sub.next_renewal_date,
                        sub.last_renewal_date,
                        sub.auto_renewal,
                        sub.status,
                        sub.external_reference,
                        CONCAT_WS(\'\n---\n\', srv.notes, sub.notes),
                        srv.active,
                        sub.created_at,
                        sub.updated_at
                      FROM saas_subscriptions sub
                      INNER JOIN saas_services srv ON sub.service_id = srv.id');

        // Étape 6: Ajouter les contraintes de clés étrangères
        $this->addSql('ALTER TABLE saas_subscriptions_v2 ADD CONSTRAINT FK_80C6E0B3F603EE73 FOREIGN KEY (vendor_id) REFERENCES saas_vendors (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE saas_subscriptions_v2 ADD CONSTRAINT FK_80C6E0B3A53A8AA FOREIGN KEY (provider_id) REFERENCES saas_distribution_providers (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE saas_subscriptions_v2 DROP FOREIGN KEY FK_80C6E0B3F603EE73');
        $this->addSql('ALTER TABLE saas_subscriptions_v2 DROP FOREIGN KEY FK_80C6E0B3A53A8AA');
        $this->addSql('DROP TABLE saas_subscriptions_v2');
        $this->addSql('DROP TABLE saas_distribution_providers');
        $this->addSql('DROP TABLE saas_vendors');
    }
}
