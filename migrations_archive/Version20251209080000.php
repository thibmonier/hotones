<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour la gestion des notes de frais
 */
final class Version20251209080000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la table expense_reports et les colonnes pour la gestion des notes de frais dans orders';
    }

    public function up(Schema $schema): void
    {
        // Création de la table expense_reports
        $this->addSql('CREATE TABLE expense_reports (
            id INT AUTO_INCREMENT NOT NULL,
            contributor_id INT NOT NULL,
            project_id INT DEFAULT NULL,
            order_id INT DEFAULT NULL,
            validator_id INT DEFAULT NULL,
            expense_date DATE NOT NULL,
            category VARCHAR(50) NOT NULL,
            description LONGTEXT NOT NULL,
            amount_ht NUMERIC(10, 2) NOT NULL,
            vat_rate NUMERIC(5, 2) NOT NULL,
            amount_ttc NUMERIC(10, 2) NOT NULL,
            status VARCHAR(20) NOT NULL,
            file_path VARCHAR(255) DEFAULT NULL,
            validated_at DATETIME DEFAULT NULL,
            validation_comment LONGTEXT DEFAULT NULL,
            paid_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_expense_status (status),
            INDEX idx_expense_date (expense_date),
            INDEX idx_expense_contributor (contributor_id),
            INDEX IDX_9BFFDAB77A19A357 (contributor_id),
            INDEX IDX_9BFFDAB7166D1F9C (project_id),
            INDEX IDX_9BFFDAB78D9F6D38 (order_id),
            INDEX IDX_9BFFDAB7BBE10C72 (validator_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Contraintes de clés étrangères pour expense_reports
        $this->addSql('ALTER TABLE expense_reports ADD CONSTRAINT FK_9BFFDAB77A19A357 FOREIGN KEY (contributor_id) REFERENCES contributors (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE expense_reports ADD CONSTRAINT FK_9BFFDAB7166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE expense_reports ADD CONSTRAINT FK_9BFFDAB78D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE expense_reports ADD CONSTRAINT FK_9BFFDAB7BBE10C72 FOREIGN KEY (validator_id) REFERENCES users (id) ON DELETE SET NULL');

        // Ajout des colonnes dans la table orders
        $this->addSql('ALTER TABLE orders ADD expenses_rebillable TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE orders ADD expense_management_fee_rate NUMERIC(5, 2) DEFAULT \'0.00\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // Suppression des contraintes de clés étrangères
        $this->addSql('ALTER TABLE expense_reports DROP FOREIGN KEY FK_9BFFDAB77A19A357');
        $this->addSql('ALTER TABLE expense_reports DROP FOREIGN KEY FK_9BFFDAB7166D1F9C');
        $this->addSql('ALTER TABLE expense_reports DROP FOREIGN KEY FK_9BFFDAB78D9F6D38');
        $this->addSql('ALTER TABLE expense_reports DROP FOREIGN KEY FK_9BFFDAB7BBE10C72');

        // Suppression de la table expense_reports
        $this->addSql('DROP TABLE expense_reports');

        // Suppression des colonnes dans orders
        $this->addSql('ALTER TABLE orders DROP expenses_rebillable');
        $this->addSql('ALTER TABLE orders DROP expense_management_fee_rate');
    }
}
