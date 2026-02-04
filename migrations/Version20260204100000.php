<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajout du système de niveaux d'emploi (1-12).
 *
 * Structure des niveaux:
 * - 1, 2, 3: Junior
 * - 4, 5, 6: Expérimenté (Confirmé)
 * - 7, 8, 9: Senior
 * - 10, 11, 12: Lead / Expert
 */
final class Version20260204100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout du système de niveaux d\'emploi (1-12) avec fourchettes salariales';
    }

    public function up(Schema $schema): void
    {
        // Création de la table employee_levels
        $this->addSql('CREATE TABLE employee_levels (
            id INT AUTO_INCREMENT NOT NULL,
            company_id INT NOT NULL,
            level SMALLINT NOT NULL,
            name VARCHAR(100) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            salary_min NUMERIC(10, 2) DEFAULT NULL,
            salary_max NUMERIC(10, 2) DEFAULT NULL,
            salary_target NUMERIC(10, 2) DEFAULT NULL,
            target_tjm NUMERIC(10, 2) DEFAULT NULL,
            color VARCHAR(7) DEFAULT NULL,
            active TINYINT(1) DEFAULT 1 NOT NULL,
            INDEX idx_employee_level_company (company_id),
            UNIQUE INDEX unique_level_per_company (company_id, level),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE employee_levels ADD CONSTRAINT FK_employee_levels_company FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE');

        // Ajout de la colonne employee_level_id dans employment_periods
        $this->addSql('ALTER TABLE employment_periods ADD employee_level_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE employment_periods ADD CONSTRAINT FK_employment_periods_level FOREIGN KEY (employee_level_id) REFERENCES employee_levels (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX idx_employment_period_level ON employment_periods (employee_level_id)');
    }

    public function down(Schema $schema): void
    {
        // Suppression de la colonne employee_level_id
        $this->addSql('ALTER TABLE employment_periods DROP FOREIGN KEY FK_employment_periods_level');
        $this->addSql('DROP INDEX idx_employment_period_level ON employment_periods');
        $this->addSql('ALTER TABLE employment_periods DROP employee_level_id');

        // Suppression de la table employee_levels
        $this->addSql('ALTER TABLE employee_levels DROP FOREIGN KEY FK_employee_levels_company');
        $this->addSql('DROP TABLE employee_levels');
    }
}
