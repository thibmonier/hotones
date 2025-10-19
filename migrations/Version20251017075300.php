<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251017075300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add email, phone and notes fields to Contributor entity and make cjm/tjm nullable';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contributors ADD email VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE contributors ADD phone VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE contributors ADD notes LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE contributors CHANGE cjm cjm NUMERIC(10, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE contributors CHANGE tjm tjm NUMERIC(10, 2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contributors DROP email');
        $this->addSql('ALTER TABLE contributors DROP phone');
        $this->addSql('ALTER TABLE contributors DROP notes');
        $this->addSql('ALTER TABLE contributors CHANGE cjm cjm NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE contributors CHANGE tjm tjm NUMERIC(10, 2) NOT NULL');
    }
}