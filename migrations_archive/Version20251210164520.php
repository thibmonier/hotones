<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251210164520 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE onboarding_tasks (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, order_num INT NOT NULL, assigned_to VARCHAR(50) NOT NULL, type VARCHAR(50) NOT NULL, days_after_start INT NOT NULL, due_date DATETIME DEFAULT NULL, status VARCHAR(50) NOT NULL, completed_at DATETIME DEFAULT NULL, comments LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, contributor_id INT NOT NULL, template_id INT DEFAULT NULL, INDEX IDX_6DCA087B7A19A357 (contributor_id), INDEX IDX_6DCA087B5DA0FB8 (template_id), INDEX idx_onboarding_task_status (status), INDEX idx_onboarding_task_due_date (due_date), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE onboarding_templates (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, tasks JSON NOT NULL, active TINYINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, profile_id INT DEFAULT NULL, INDEX IDX_A8917FF4CCFA12B8 (profile_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE performance_reviews (id INT AUTO_INCREMENT NOT NULL, year INT NOT NULL, status VARCHAR(50) NOT NULL, self_evaluation JSON DEFAULT NULL, manager_evaluation JSON DEFAULT NULL, objectives JSON DEFAULT NULL, overall_rating INT DEFAULT NULL, interview_date DATETIME DEFAULT NULL, comments LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, validated_at DATETIME DEFAULT NULL, contributor_id INT NOT NULL, manager_id INT NOT NULL, INDEX IDX_CAAC03557A19A357 (contributor_id), INDEX IDX_CAAC0355783E3463 (manager_id), INDEX idx_performance_review_year (year), INDEX idx_performance_review_status (status), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE onboarding_tasks ADD CONSTRAINT FK_6DCA087B7A19A357 FOREIGN KEY (contributor_id) REFERENCES contributors (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE onboarding_tasks ADD CONSTRAINT FK_6DCA087B5DA0FB8 FOREIGN KEY (template_id) REFERENCES onboarding_templates (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE onboarding_templates ADD CONSTRAINT FK_A8917FF4CCFA12B8 FOREIGN KEY (profile_id) REFERENCES profiles (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE performance_reviews ADD CONSTRAINT FK_CAAC03557A19A357 FOREIGN KEY (contributor_id) REFERENCES contributors (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE performance_reviews ADD CONSTRAINT FK_CAAC0355783E3463 FOREIGN KEY (manager_id) REFERENCES users (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE employment_periods CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT 35 NOT NULL, CHANGE work_time_percentage work_time_percentage NUMERIC(5, 2) DEFAULT 100 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE onboarding_tasks DROP FOREIGN KEY FK_6DCA087B7A19A357');
        $this->addSql('ALTER TABLE onboarding_tasks DROP FOREIGN KEY FK_6DCA087B5DA0FB8');
        $this->addSql('ALTER TABLE onboarding_templates DROP FOREIGN KEY FK_A8917FF4CCFA12B8');
        $this->addSql('ALTER TABLE performance_reviews DROP FOREIGN KEY FK_CAAC03557A19A357');
        $this->addSql('ALTER TABLE performance_reviews DROP FOREIGN KEY FK_CAAC0355783E3463');
        $this->addSql('DROP TABLE onboarding_tasks');
        $this->addSql('DROP TABLE onboarding_templates');
        $this->addSql('DROP TABLE performance_reviews');
        $this->addSql('ALTER TABLE employment_periods CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT \'35.00\' NOT NULL, CHANGE work_time_percentage work_time_percentage NUMERIC(5, 2) DEFAULT \'100.00\' NOT NULL');
    }
}
