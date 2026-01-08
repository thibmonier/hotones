<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251020060724 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE employment_periods CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT \'35\' NOT NULL, CHANGE working_time_percentage working_time_percentage NUMERIC(5, 2) DEFAULT \'100\' NOT NULL');
        $this->addSql('ALTER TABLE orders ADD description LONGTEXT DEFAULT NULL, ADD contingency_percentage NUMERIC(5, 2) DEFAULT NULL, ADD valid_until DATE DEFAULT NULL, CHANGE name name VARCHAR(180) DEFAULT NULL, CHANGE total_amount total_amount NUMERIC(12, 2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE orders DROP description, DROP contingency_percentage, DROP valid_until, CHANGE name name VARCHAR(180) NOT NULL, CHANGE total_amount total_amount NUMERIC(12, 2) NOT NULL');
        $this->addSql('ALTER TABLE employment_periods CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT \'35.00\' NOT NULL, CHANGE working_time_percentage working_time_percentage NUMERIC(5, 2) DEFAULT \'100.00\' NOT NULL');
    }
}
