<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add CJM (Coût Journalier Moyen) and margin coefficient to Profile entity
 */
final class Version20251127140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add CJM (Coût Journalier Moyen) and margin coefficient fields to profiles table for better margin calculation';
    }

    public function up(Schema $schema): void
    {
        // Add CJM field to profiles table
        $this->addSql('ALTER TABLE profiles ADD cjm NUMERIC(10, 2) DEFAULT NULL');

        // Add margin coefficient field with default value of 1.00
        $this->addSql('ALTER TABLE profiles ADD margin_coefficient NUMERIC(5, 2) DEFAULT \'1.00\'');
    }

    public function down(Schema $schema): void
    {
        // Remove added fields
        $this->addSql('ALTER TABLE profiles DROP cjm');
        $this->addSql('ALTER TABLE profiles DROP margin_coefficient');
    }
}
