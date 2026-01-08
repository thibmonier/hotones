<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour le système de notifications (Lot 6)
 */
final class Version20251109084500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création des tables pour le système de notifications : notifications, notification_preferences, notification_settings';
    }

    public function up(Schema $schema): void
    {
        // Table notifications
        $this->addSql('CREATE TABLE notifications (
            id INT AUTO_INCREMENT NOT NULL,
            recipient_id INT NOT NULL,
            type VARCHAR(255) NOT NULL,
            title VARCHAR(255) NOT NULL,
            message LONGTEXT NOT NULL,
            data JSON DEFAULT NULL,
            entity_type VARCHAR(100) DEFAULT NULL,
            entity_id INT DEFAULT NULL,
            read_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_6000B0D3E92F8F78 (recipient_id),
            INDEX IDX_6000B0D3C54C8C93 (type),
            INDEX IDX_6000B0D38B8E8428 (created_at),
            INDEX IDX_6000B0D37C69D773 (read_at),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE notifications ADD CONSTRAINT FK_6000B0D3E92F8F78 FOREIGN KEY (recipient_id) REFERENCES users (id) ON DELETE CASCADE');

        // Table notification_preferences
        $this->addSql('CREATE TABLE notification_preferences (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            event_type VARCHAR(255) NOT NULL,
            in_app TINYINT(1) NOT NULL,
            email TINYINT(1) NOT NULL,
            webhook TINYINT(1) NOT NULL,
            INDEX IDX_857AEBAEA76ED395 (user_id),
            UNIQUE INDEX user_event_unique (user_id, event_type),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE notification_preferences ADD CONSTRAINT FK_857AEBAEA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');

        // Table notification_settings
        $this->addSql('CREATE TABLE notification_settings (
            id INT AUTO_INCREMENT NOT NULL,
            setting_key VARCHAR(100) NOT NULL,
            setting_value JSON NOT NULL,
            UNIQUE INDEX UNIQ_B27BA8419646419B (setting_key),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Initialisation des valeurs par défaut
        $this->addSql("INSERT INTO notification_settings (setting_key, setting_value) VALUES 
            ('budget_alert_threshold', '{\"value\": 80}'),
            ('payment_due_days', '{\"value\": 7}')
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE notifications DROP FOREIGN KEY FK_6000B0D3E92F8F78');
        $this->addSql('ALTER TABLE notification_preferences DROP FOREIGN KEY FK_857AEBAEA76ED395');
        
        $this->addSql('DROP TABLE notifications');
        $this->addSql('DROP TABLE notification_preferences');
        $this->addSql('DROP TABLE notification_settings');
    }
}
