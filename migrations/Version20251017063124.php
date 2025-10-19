<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251017063124 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE order_lines (id INT AUTO_INCREMENT NOT NULL, section_id INT NOT NULL, profile_id INT DEFAULT NULL, description VARCHAR(255) NOT NULL, position INT NOT NULL, daily_rate NUMERIC(10, 2) DEFAULT NULL, days NUMERIC(8, 2) DEFAULT NULL, direct_amount NUMERIC(12, 2) DEFAULT NULL, attached_purchase_amount NUMERIC(12, 2) DEFAULT NULL, type VARCHAR(50) NOT NULL, notes LONGTEXT DEFAULT NULL, INDEX IDX_CC9FF86BD823E37A (section_id), INDEX IDX_CC9FF86BCCFA12B8 (profile_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE order_sections (id INT AUTO_INCREMENT NOT NULL, order_id INT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, position INT NOT NULL, INDEX IDX_CA6EA1298D9F6D38 (order_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE planning (id INT AUTO_INCREMENT NOT NULL, contributor_id INT NOT NULL, project_id INT NOT NULL, profile_id INT DEFAULT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, daily_hours NUMERIC(4, 2) NOT NULL, notes LONGTEXT DEFAULT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_D499BFF67A19A357 (contributor_id), INDEX IDX_D499BFF6166D1F9C (project_id), INDEX IDX_D499BFF6CCFA12B8 (profile_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE project_tasks (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, type VARCHAR(20) NOT NULL, is_default TINYINT(1) NOT NULL, counts_for_profitability TINYINT(1) NOT NULL, position INT NOT NULL, active TINYINT(1) NOT NULL, INDEX IDX_430D6C09166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE vacations (id INT AUTO_INCREMENT NOT NULL, contributor_id INT NOT NULL, approved_by_id INT DEFAULT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, type VARCHAR(50) NOT NULL, reason LONGTEXT DEFAULT NULL, status VARCHAR(20) NOT NULL, daily_hours NUMERIC(4, 2) NOT NULL, created_at DATETIME NOT NULL, approved_at DATETIME DEFAULT NULL, INDEX IDX_3B8290677A19A357 (contributor_id), INDEX IDX_3B8290672D234F6A (approved_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE order_lines ADD CONSTRAINT FK_CC9FF86BD823E37A FOREIGN KEY (section_id) REFERENCES order_sections (id)');
        $this->addSql('ALTER TABLE order_lines ADD CONSTRAINT FK_CC9FF86BCCFA12B8 FOREIGN KEY (profile_id) REFERENCES profiles (id)');
        $this->addSql('ALTER TABLE order_sections ADD CONSTRAINT FK_CA6EA1298D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id)');
        $this->addSql('ALTER TABLE planning ADD CONSTRAINT FK_D499BFF67A19A357 FOREIGN KEY (contributor_id) REFERENCES contributors (id)');
        $this->addSql('ALTER TABLE planning ADD CONSTRAINT FK_D499BFF6166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id)');
        $this->addSql('ALTER TABLE planning ADD CONSTRAINT FK_D499BFF6CCFA12B8 FOREIGN KEY (profile_id) REFERENCES profiles (id)');
        $this->addSql('ALTER TABLE project_tasks ADD CONSTRAINT FK_430D6C09166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id)');
        $this->addSql('ALTER TABLE vacations ADD CONSTRAINT FK_3B8290677A19A357 FOREIGN KEY (contributor_id) REFERENCES contributors (id)');
        $this->addSql('ALTER TABLE vacations ADD CONSTRAINT FK_3B8290672D234F6A FOREIGN KEY (approved_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE employment_periods ADD working_time_percentage NUMERIC(5, 2) DEFAULT \'100\' NOT NULL, ADD notes LONGTEXT DEFAULT NULL, CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT \'35\' NOT NULL');
        $this->addSql('ALTER TABLE orders ADD order_number VARCHAR(50) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E52FFDEE551F0F81 ON orders (order_number)');
        $this->addSql('ALTER TABLE projects ADD key_account_manager_id INT DEFAULT NULL, ADD project_manager_id INT DEFAULT NULL, ADD project_director_id INT DEFAULT NULL, ADD sales_person_id INT DEFAULT NULL, ADD project_type VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE projects ADD CONSTRAINT FK_5C93B3A44DDC9A02 FOREIGN KEY (key_account_manager_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE projects ADD CONSTRAINT FK_5C93B3A460984F51 FOREIGN KEY (project_manager_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE projects ADD CONSTRAINT FK_5C93B3A44150449D FOREIGN KEY (project_director_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE projects ADD CONSTRAINT FK_5C93B3A41D35E30E FOREIGN KEY (sales_person_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_5C93B3A44DDC9A02 ON projects (key_account_manager_id)');
        $this->addSql('CREATE INDEX IDX_5C93B3A460984F51 ON projects (project_manager_id)');
        $this->addSql('CREATE INDEX IDX_5C93B3A44150449D ON projects (project_director_id)');
        $this->addSql('CREATE INDEX IDX_5C93B3A41D35E30E ON projects (sales_person_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE order_lines DROP FOREIGN KEY FK_CC9FF86BD823E37A');
        $this->addSql('ALTER TABLE order_lines DROP FOREIGN KEY FK_CC9FF86BCCFA12B8');
        $this->addSql('ALTER TABLE order_sections DROP FOREIGN KEY FK_CA6EA1298D9F6D38');
        $this->addSql('ALTER TABLE planning DROP FOREIGN KEY FK_D499BFF67A19A357');
        $this->addSql('ALTER TABLE planning DROP FOREIGN KEY FK_D499BFF6166D1F9C');
        $this->addSql('ALTER TABLE planning DROP FOREIGN KEY FK_D499BFF6CCFA12B8');
        $this->addSql('ALTER TABLE project_tasks DROP FOREIGN KEY FK_430D6C09166D1F9C');
        $this->addSql('ALTER TABLE vacations DROP FOREIGN KEY FK_3B8290677A19A357');
        $this->addSql('ALTER TABLE vacations DROP FOREIGN KEY FK_3B8290672D234F6A');
        $this->addSql('DROP TABLE order_lines');
        $this->addSql('DROP TABLE order_sections');
        $this->addSql('DROP TABLE planning');
        $this->addSql('DROP TABLE project_tasks');
        $this->addSql('DROP TABLE vacations');
        $this->addSql('ALTER TABLE projects DROP FOREIGN KEY FK_5C93B3A44DDC9A02');
        $this->addSql('ALTER TABLE projects DROP FOREIGN KEY FK_5C93B3A460984F51');
        $this->addSql('ALTER TABLE projects DROP FOREIGN KEY FK_5C93B3A44150449D');
        $this->addSql('ALTER TABLE projects DROP FOREIGN KEY FK_5C93B3A41D35E30E');
        $this->addSql('DROP INDEX IDX_5C93B3A44DDC9A02 ON projects');
        $this->addSql('DROP INDEX IDX_5C93B3A460984F51 ON projects');
        $this->addSql('DROP INDEX IDX_5C93B3A44150449D ON projects');
        $this->addSql('DROP INDEX IDX_5C93B3A41D35E30E ON projects');
        $this->addSql('ALTER TABLE projects DROP key_account_manager_id, DROP project_manager_id, DROP project_director_id, DROP sales_person_id, DROP project_type');
        $this->addSql('DROP INDEX UNIQ_E52FFDEE551F0F81 ON orders');
        $this->addSql('ALTER TABLE orders DROP order_number');
        $this->addSql('ALTER TABLE employment_periods DROP working_time_percentage, DROP notes, CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT \'35.00\' NOT NULL');
    }
}
