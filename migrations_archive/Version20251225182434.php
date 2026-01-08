<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251225182434 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create account_deletion_requests table for GDPR right to erasure (right to be forgotten)';
    }

    public function up(Schema $schema): void
    {
        // Create account_deletion_requests table for GDPR right to erasure
        $this->addSql('CREATE TABLE account_deletion_requests (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, status VARCHAR(20) NOT NULL, confirmation_token VARCHAR(64) NOT NULL, requested_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', confirmed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', scheduled_deletion_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', cancelled_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', completed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', reason LONGTEXT DEFAULT NULL, ip_address VARCHAR(45) DEFAULT NULL, UNIQUE INDEX UNIQ_748FBDF6C05FB297 (confirmation_token), INDEX idx_deletion_request_user (user_id), INDEX idx_deletion_request_status (status), INDEX idx_deletion_scheduled (scheduled_deletion_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE account_deletion_requests ADD CONSTRAINT FK_748FBDF6A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');

        // Fix schema drift
        $this->addSql('ALTER TABLE employment_periods CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT 35 NOT NULL, CHANGE work_time_percentage work_time_percentage NUMERIC(5, 2) DEFAULT 100 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE account_deletion_requests DROP FOREIGN KEY FK_748FBDF6A76ED395');
        $this->addSql('DROP TABLE account_deletion_requests');
        $this->addSql('ALTER TABLE cookie_consents CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE expires_at expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE employment_periods CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT \'35.00\' NOT NULL, CHANGE work_time_percentage work_time_percentage NUMERIC(5, 2) DEFAULT \'100.00\' NOT NULL');
    }
}
