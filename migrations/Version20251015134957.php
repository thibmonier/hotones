<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251015134957 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE contributors (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(180) NOT NULL, cjm NUMERIC(10, 2) NOT NULL, tjm NUMERIC(10, 2) NOT NULL, active TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE projects (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(180) NOT NULL, client VARCHAR(180) DEFAULT NULL, sold_days NUMERIC(10, 2) DEFAULT NULL, sold_daily_rate NUMERIC(10, 2) DEFAULT NULL, start_date DATE DEFAULT NULL, end_date DATE DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE timesheets (id INT AUTO_INCREMENT NOT NULL, contributor_id INT NOT NULL, project_id INT NOT NULL, date DATE NOT NULL, hours NUMERIC(5, 2) NOT NULL, notes LONGTEXT DEFAULT NULL, INDEX IDX_9AC77D2E7A19A357 (contributor_id), INDEX IDX_9AC77D2E166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE timesheets ADD CONSTRAINT FK_9AC77D2E7A19A357 FOREIGN KEY (contributor_id) REFERENCES contributors (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE timesheets ADD CONSTRAINT FK_9AC77D2E166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE timesheets DROP FOREIGN KEY FK_9AC77D2E7A19A357');
        $this->addSql('ALTER TABLE timesheets DROP FOREIGN KEY FK_9AC77D2E166D1F9C');
        $this->addSql('DROP TABLE contributors');
        $this->addSql('DROP TABLE projects');
        $this->addSql('DROP TABLE timesheets');
    }
}
