<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251106095000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create billing_markers table to track issued/paid/comment for billing entries (forfait schedules or regie monthly periods)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE billing_markers (
            id INT AUTO_INCREMENT NOT NULL,
            schedule_id INT DEFAULT NULL,
            order_id INT DEFAULT NULL,
            year SMALLINT DEFAULT NULL,
            month SMALLINT DEFAULT NULL,
            is_issued TINYINT(1) DEFAULT 0 NOT NULL,
            issued_at DATE DEFAULT NULL,
            paid_at DATE DEFAULT NULL,
            comment LONGTEXT DEFAULT NULL,
            UNIQUE INDEX uniq_marker_schedule (schedule_id),
            UNIQUE INDEX uniq_marker_regie_period (order_id, year, month),
            INDEX idx_marker_order (order_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE billing_markers ADD CONSTRAINT FK_BM_SCHEDULE FOREIGN KEY (schedule_id) REFERENCES order_payment_schedules (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE billing_markers ADD CONSTRAINT FK_BM_ORDER FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE billing_markers DROP FOREIGN KEY FK_BM_SCHEDULE');
        $this->addSql('ALTER TABLE billing_markers DROP FOREIGN KEY FK_BM_ORDER');
        $this->addSql('DROP TABLE billing_markers');
    }
}
