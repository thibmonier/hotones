<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251015140457 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE employment_periods (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, salary NUMERIC(10, 2) NOT NULL, cjm NUMERIC(10, 2) NOT NULL, weekly_hours NUMERIC(5, 2) DEFAULT \'35\' NOT NULL, start_date DATE NOT NULL, end_date DATE DEFAULT NULL, INDEX IDX_B996D77BA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, phone VARCHAR(32) DEFAULT NULL, address LONGTEXT DEFAULT NULL, avatar VARCHAR(255) DEFAULT NULL, totp_secret VARCHAR(255) DEFAULT NULL, totp_enabled TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE employment_periods ADD CONSTRAINT FK_B996D77BA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE employment_periods DROP FOREIGN KEY FK_B996D77BA76ED395');
        $this->addSql('DROP TABLE employment_periods');
        $this->addSql('DROP TABLE users');
    }
}
