<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251106064753 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE order_payment_schedules (id INT AUTO_INCREMENT NOT NULL, order_id INT NOT NULL, label VARCHAR(255) DEFAULT NULL, billing_date DATE NOT NULL, amount_type VARCHAR(20) NOT NULL, percent NUMERIC(5, 2) DEFAULT NULL, fixed_amount NUMERIC(12, 2) DEFAULT NULL, INDEX IDX_6671B9FD8D9F6D38 (order_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE order_payment_schedules ADD CONSTRAINT FK_6671B9FD8D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE employment_periods CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT \'35\' NOT NULL, CHANGE work_time_percentage work_time_percentage NUMERIC(5, 2) DEFAULT \'100\' NOT NULL');
        $this->addSql('ALTER TABLE orders ADD contract_type VARCHAR(20) DEFAULT \'forfait\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE order_payment_schedules DROP FOREIGN KEY FK_6671B9FD8D9F6D38');
        $this->addSql('DROP TABLE order_payment_schedules');
        $this->addSql('ALTER TABLE orders DROP contract_type');
        $this->addSql('ALTER TABLE employment_periods CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT \'35.00\' NOT NULL, CHANGE work_time_percentage work_time_percentage NUMERIC(5, 2) DEFAULT \'100.00\' NOT NULL');
    }
}
