<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251020145307 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE employment_period_profiles (employment_period_id INT NOT NULL, profile_id INT NOT NULL, INDEX IDX_A643DBB15A128608 (employment_period_id), INDEX IDX_A643DBB1CCFA12B8 (profile_id), PRIMARY KEY(employment_period_id, profile_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE employment_period_profiles ADD CONSTRAINT FK_A643DBB15A128608 FOREIGN KEY (employment_period_id) REFERENCES employment_periods (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE employment_period_profiles ADD CONSTRAINT FK_A643DBB1CCFA12B8 FOREIGN KEY (profile_id) REFERENCES profiles (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE employment_periods ADD tjm NUMERIC(10, 2) DEFAULT NULL, ADD work_time_percentage NUMERIC(5, 2) DEFAULT \'100\' NOT NULL, DROP working_time_percentage, CHANGE salary salary NUMERIC(10, 2) DEFAULT NULL, CHANGE cjm cjm NUMERIC(10, 2) DEFAULT NULL, CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT \'35\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE employment_period_profiles DROP FOREIGN KEY FK_A643DBB15A128608');
        $this->addSql('ALTER TABLE employment_period_profiles DROP FOREIGN KEY FK_A643DBB1CCFA12B8');
        $this->addSql('DROP TABLE employment_period_profiles');
        $this->addSql('ALTER TABLE employment_periods ADD working_time_percentage NUMERIC(5, 2) DEFAULT \'100.00\' NOT NULL, DROP tjm, DROP work_time_percentage, CHANGE salary salary NUMERIC(10, 2) NOT NULL, CHANGE cjm cjm NUMERIC(10, 2) NOT NULL, CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT \'35.00\' NOT NULL');
    }
}
