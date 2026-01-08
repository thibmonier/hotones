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

    public function isTransactional(): bool
    {
        // DDL statements cause implicit commits in MySQL/MariaDB
        return false;
    }

    public function up(Schema $schema): void
    {
        // Add first_name if it doesn't exist
        $this->addSql(<<<'SQL'
            SET @col_exists = (
                SELECT COUNT(*)
                FROM information_schema.columns
                WHERE table_schema = DATABASE()
                AND table_name = 'contributors'
                AND column_name = 'first_name'
            )
        SQL);
        $this->addSql(<<<'SQL'
            SET @sql = IF(@col_exists = 0,
                'ALTER TABLE contributors ADD first_name VARCHAR(100) NOT NULL',
                'SELECT "Column first_name already exists" as notice'
            )
        SQL);
        $this->addSql('PREPARE stmt FROM @sql');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');

        // Add last_name if it doesn't exist
        $this->addSql(<<<'SQL'
            SET @col_exists = (
                SELECT COUNT(*)
                FROM information_schema.columns
                WHERE table_schema = DATABASE()
                AND table_name = 'contributors'
                AND column_name = 'last_name'
            )
        SQL);
        $this->addSql(<<<'SQL'
            SET @sql = IF(@col_exists = 0,
                'ALTER TABLE contributors ADD last_name VARCHAR(100) NOT NULL',
                'SELECT "Column last_name already exists" as notice'
            )
        SQL);
        $this->addSql('PREPARE stmt FROM @sql');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');

        // Add phone_professional if it doesn't exist
        $this->addSql(<<<'SQL'
            SET @col_exists = (
                SELECT COUNT(*)
                FROM information_schema.columns
                WHERE table_schema = DATABASE()
                AND table_name = 'contributors'
                AND column_name = 'phone_professional'
            )
        SQL);
        $this->addSql(<<<'SQL'
            SET @sql = IF(@col_exists = 0,
                'ALTER TABLE contributors ADD phone_professional VARCHAR(20) DEFAULT NULL',
                'SELECT "Column phone_professional already exists" as notice'
            )
        SQL);
        $this->addSql('PREPARE stmt FROM @sql');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');

        // Add address if it doesn't exist
        $this->addSql(<<<'SQL'
            SET @col_exists = (
                SELECT COUNT(*)
                FROM information_schema.columns
                WHERE table_schema = DATABASE()
                AND table_name = 'contributors'
                AND column_name = 'address'
            )
        SQL);
        $this->addSql(<<<'SQL'
            SET @sql = IF(@col_exists = 0,
                'ALTER TABLE contributors ADD address LONGTEXT DEFAULT NULL',
                'SELECT "Column address already exists" as notice'
            )
        SQL);
        $this->addSql('PREPARE stmt FROM @sql');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');

        // Add avatar_filename if it doesn't exist
        $this->addSql(<<<'SQL'
            SET @col_exists = (
                SELECT COUNT(*)
                FROM information_schema.columns
                WHERE table_schema = DATABASE()
                AND table_name = 'contributors'
                AND column_name = 'avatar_filename'
            )
        SQL);
        $this->addSql(<<<'SQL'
            SET @sql = IF(@col_exists = 0,
                'ALTER TABLE contributors ADD avatar_filename VARCHAR(255) DEFAULT NULL',
                'SELECT "Column avatar_filename already exists" as notice'
            )
        SQL);
        $this->addSql('PREPARE stmt FROM @sql');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');

        // Drop name if it exists
        $this->addSql(<<<'SQL'
            SET @col_exists = (
                SELECT COUNT(*)
                FROM information_schema.columns
                WHERE table_schema = DATABASE()
                AND table_name = 'contributors'
                AND column_name = 'name'
            )
        SQL);
        $this->addSql(<<<'SQL'
            SET @sql = IF(@col_exists > 0,
                'ALTER TABLE contributors DROP name',
                'SELECT "Column name already dropped" as notice'
            )
        SQL);
        $this->addSql('PREPARE stmt FROM @sql');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');

        // Rename phone to phone_personal if phone exists and phone_personal doesn't
        $this->addSql(<<<'SQL'
            SET @old_col_exists = (
                SELECT COUNT(*)
                FROM information_schema.columns
                WHERE table_schema = DATABASE()
                AND table_name = 'contributors'
                AND column_name = 'phone'
            )
        SQL);
        $this->addSql(<<<'SQL'
            SET @new_col_exists = (
                SELECT COUNT(*)
                FROM information_schema.columns
                WHERE table_schema = DATABASE()
                AND table_name = 'contributors'
                AND column_name = 'phone_personal'
            )
        SQL);
        $this->addSql(<<<'SQL'
            SET @sql = IF(@old_col_exists > 0 AND @new_col_exists = 0,
                'ALTER TABLE contributors CHANGE phone phone_personal VARCHAR(20) DEFAULT NULL',
                'SELECT "Column phone already renamed or phone_personal exists" as notice'
            )
        SQL);
        $this->addSql('PREPARE stmt FROM @sql');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');
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
