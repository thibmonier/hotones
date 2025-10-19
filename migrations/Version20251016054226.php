<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251016054226 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE order_tasks (id INT AUTO_INCREMENT NOT NULL, order_id INT NOT NULL, name VARCHAR(180) NOT NULL, description LONGTEXT DEFAULT NULL, profile VARCHAR(50) NOT NULL, sold_days NUMERIC(8, 2) NOT NULL, sold_daily_rate NUMERIC(10, 2) NOT NULL, total_amount NUMERIC(12, 2) NOT NULL, INDEX IDX_D3C6116A8D9F6D38 (order_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE orders (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, name VARCHAR(180) NOT NULL, description LONGTEXT DEFAULT NULL, total_amount NUMERIC(12, 2) NOT NULL, created_at DATE NOT NULL, validated_at DATE DEFAULT NULL, status VARCHAR(20) NOT NULL, INDEX IDX_E52FFDEE166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE profiles (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, default_daily_rate NUMERIC(10, 2) DEFAULT NULL, color VARCHAR(7) DEFAULT NULL, active TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_8B3085305E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE order_tasks ADD CONSTRAINT FK_D3C6116A8D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE employment_periods CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT \'35\' NOT NULL');
        $this->addSql('ALTER TABLE projects ADD description LONGTEXT DEFAULT NULL, ADD purchases_amount NUMERIC(12, 2) DEFAULT NULL, ADD purchases_description LONGTEXT DEFAULT NULL, ADD status VARCHAR(20) NOT NULL, DROP sold_days, DROP sold_daily_rate');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE order_tasks DROP FOREIGN KEY FK_D3C6116A8D9F6D38');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEE166D1F9C');
        $this->addSql('DROP TABLE order_tasks');
        $this->addSql('DROP TABLE orders');
        $this->addSql('DROP TABLE profiles');
        $this->addSql('ALTER TABLE projects ADD sold_days NUMERIC(10, 2) DEFAULT NULL, ADD sold_daily_rate NUMERIC(10, 2) DEFAULT NULL, DROP description, DROP purchases_amount, DROP purchases_description, DROP status');
        $this->addSql('ALTER TABLE employment_periods CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT \'35.00\' NOT NULL');
    }
}
