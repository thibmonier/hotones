<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251108100511 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add firstName, lastName, phones, address, avatar to contributors';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE
              contributors
            ADD
              first_name VARCHAR(100) NOT NULL,
            ADD
              last_name VARCHAR(100) NOT NULL,
            ADD
              phone_professional VARCHAR(20) DEFAULT NULL,
            ADD
              address LONGTEXT DEFAULT NULL,
            ADD
              avatar_filename VARCHAR(255) DEFAULT NULL,
            DROP
              name,
            CHANGE
              phone phone_personal VARCHAR(20) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE
              contributors
            ADD
              name VARCHAR(180) NOT NULL,
            ADD
              phone VARCHAR(20) DEFAULT NULL,
            DROP
              first_name,
            DROP
              last_name,
            DROP
              phone_personal,
            DROP
              phone_professional,
            DROP
              address,
            DROP
              avatar_filename
        SQL);
    }
}
