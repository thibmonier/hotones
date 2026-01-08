<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251220172900 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create SaaS management tables (providers, services, subscriptions)';
    }

    public function up(Schema $schema): void
    {
        // Create SaaS tables
        $this->addSql('CREATE TABLE saas_providers (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, website VARCHAR(500) DEFAULT NULL, contact_email VARCHAR(255) DEFAULT NULL, contact_phone VARCHAR(50) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, active TINYINT DEFAULT 1 NOT NULL, logo_url VARCHAR(500) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE saas_services (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, category VARCHAR(100) DEFAULT NULL, service_url VARCHAR(500) DEFAULT NULL, logo_url VARCHAR(500) DEFAULT NULL, default_monthly_price NUMERIC(10, 2) DEFAULT NULL, default_yearly_price NUMERIC(10, 2) DEFAULT NULL, currency VARCHAR(3) DEFAULT \'EUR\' NOT NULL, notes LONGTEXT DEFAULT NULL, active TINYINT DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, provider_id INT DEFAULT NULL, INDEX idx_saas_service_provider (provider_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE saas_subscriptions (id INT AUTO_INCREMENT NOT NULL, custom_name VARCHAR(255) DEFAULT NULL, billing_period VARCHAR(20) NOT NULL, price NUMERIC(10, 2) NOT NULL, currency VARCHAR(3) DEFAULT \'EUR\' NOT NULL, quantity INT DEFAULT 1 NOT NULL, start_date DATE NOT NULL, end_date DATE DEFAULT NULL, next_renewal_date DATE NOT NULL, last_renewal_date DATE DEFAULT NULL, auto_renewal TINYINT DEFAULT 1 NOT NULL, status VARCHAR(20) DEFAULT \'active\' NOT NULL, external_reference VARCHAR(255) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, service_id INT NOT NULL, INDEX idx_saas_subscription_service (service_id), INDEX idx_saas_subscription_status (status), INDEX idx_saas_subscription_renewal (next_renewal_date), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE saas_services ADD CONSTRAINT FK_8C62B16BA53A8AA FOREIGN KEY (provider_id) REFERENCES saas_providers (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE saas_subscriptions ADD CONSTRAINT FK_4A220501ED5CA9E6 FOREIGN KEY (service_id) REFERENCES saas_services (id) ON DELETE RESTRICT');
    }

    public function down(Schema $schema): void
    {
        // Drop SaaS tables
        $this->addSql('ALTER TABLE saas_services DROP FOREIGN KEY FK_8C62B16BA53A8AA');
        $this->addSql('ALTER TABLE saas_subscriptions DROP FOREIGN KEY FK_4A220501ED5CA9E6');
        $this->addSql('DROP TABLE saas_providers');
        $this->addSql('DROP TABLE saas_services');
        $this->addSql('DROP TABLE saas_subscriptions');
    }
}
