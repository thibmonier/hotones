<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251109140530 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Sprint 9 & 12: add project technical fields, project_technology_versions table, and scheduler_entries table';
    }

    public function up(Schema $schema): void
    {
        // Extend projects table
        $this->addSql("ALTER TABLE projects ADD repo_links LONGTEXT DEFAULT NULL, ADD env_links LONGTEXT DEFAULT NULL, ADD db_access LONGTEXT DEFAULT NULL, ADD ssh_access LONGTEXT DEFAULT NULL, ADD ftp_access LONGTEXT DEFAULT NULL");

        // Create project_technology_versions table
        $this->addSql("CREATE TABLE project_technology_versions (
            id INT AUTO_INCREMENT NOT NULL,
            project_id INT NOT NULL,
            technology_id INT NOT NULL,
            version VARCHAR(50) DEFAULT NULL,
            notes LONGTEXT DEFAULT NULL,
            INDEX IDX_PTV_PROJECT (project_id),
            INDEX IDX_PTV_TECH (technology_id),
            UNIQUE INDEX uniq_project_tech (project_id, technology_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE project_technology_versions ADD CONSTRAINT FK_PTV_PROJECT FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_technology_versions ADD CONSTRAINT FK_PTV_TECH FOREIGN KEY (technology_id) REFERENCES technologies (id) ON DELETE CASCADE');

        // Create scheduler_entries table
        $this->addSql("CREATE TABLE scheduler_entries (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(150) NOT NULL,
            cron_expression VARCHAR(100) NOT NULL,
            command VARCHAR(255) NOT NULL,
            payload JSON DEFAULT NULL,
            enabled TINYINT(1) NOT NULL DEFAULT 1,
            timezone VARCHAR(50) NOT NULL DEFAULT 'Europe/Paris',
            created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE projects DROP repo_links, DROP env_links, DROP db_access, DROP ssh_access, DROP ftp_access');
        $this->addSql('DROP TABLE project_technology_versions');
        $this->addSql('DROP TABLE scheduler_entries');
    }
}
