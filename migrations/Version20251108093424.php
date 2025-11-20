<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251108093424 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs

        // Create messenger_messages table only if it doesn't exist
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS messenger_messages (
              id BIGINT AUTO_INCREMENT NOT NULL,
              body LONGTEXT NOT NULL,
              headers LONGTEXT NOT NULL,
              queue_name VARCHAR(190) NOT NULL,
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              available_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              delivered_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              INDEX IDX_75EA56E0FB7336F0 (queue_name),
              INDEX IDX_75EA56E0E3BD61CE (available_at),
              INDEX IDX_75EA56E016BA31DB (delivered_at),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        // Rename index on billing_markers if old name exists
        // Check if old index exists before attempting rename
        $this->addSql(<<<'SQL'
            SET @index_exists = (
                SELECT COUNT(*)
                FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                AND table_name = 'billing_markers'
                AND index_name = 'idx_marker_order'
            )
        SQL);
        $this->addSql(<<<'SQL'
            SET @sql = IF(@index_exists > 0,
                'ALTER TABLE billing_markers RENAME INDEX idx_marker_order TO IDX_2AA754E78D9F6D38',
                'SELECT "Index already renamed or does not exist" as notice'
            )
        SQL);
        $this->addSql('PREPARE stmt FROM @sql');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');

        $this->addSql(<<<'SQL'
            ALTER TABLE
              employment_periods
            CHANGE
              weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT '35' NOT NULL,
            CHANGE
              work_time_percentage work_time_percentage NUMERIC(5, 2) DEFAULT '100' NOT NULL
        SQL);

        // Create unique index only if it doesn't exist
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX IF NOT EXISTS unique_fact_metrics ON fact_project_metrics (
              dim_time_id, dim_project_type_id,
              dim_project_manager_id, dim_sales_person_id,
              dim_project_director_id, granularity,
              project_id, order_id
            )
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('DROP INDEX unique_fact_metrics ON fact_project_metrics');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              employment_periods
            CHANGE
              weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT '35.00' NOT NULL,
            CHANGE
              work_time_percentage work_time_percentage NUMERIC(5, 2) DEFAULT '100.00' NOT NULL
        SQL);
        $this->addSql('ALTER TABLE billing_markers RENAME INDEX idx_2aa754e78d9f6d38 TO idx_marker_order');
    }
}
