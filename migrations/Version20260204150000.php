<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajout du système de compétences pour les projets et le planning.
 *
 * Structure:
 * - project_skills: Compétences requises par projet avec niveau et priorité
 * - planning_skills: Compétences requises par affectation (surcharge des compétences projet)
 * - technology_skills: Association entre technologies et compétences suggérées
 * - contributor_technologies: Technologies maîtrisées par les collaborateurs
 */
final class Version20260204150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des compétences projet/planning, liaison technologies-compétences et technologies collaborateurs';
    }

    public function up(Schema $schema): void
    {
        // Table project_skills: compétences requises pour un projet
        $this->addSql('CREATE TABLE project_skills (
            id INT AUTO_INCREMENT NOT NULL,
            company_id INT NOT NULL,
            project_id INT NOT NULL,
            skill_id INT NOT NULL,
            required_level INT NOT NULL DEFAULT 2,
            priority INT NOT NULL DEFAULT 2,
            notes LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_projectskill_company (company_id),
            INDEX idx_projectskill_project (project_id),
            INDEX idx_projectskill_skill (skill_id),
            UNIQUE INDEX project_skill_unique (project_id, skill_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE project_skills
            ADD CONSTRAINT FK_project_skills_company FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE,
            ADD CONSTRAINT FK_project_skills_project FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE,
            ADD CONSTRAINT FK_project_skills_skill FOREIGN KEY (skill_id) REFERENCES skills (id) ON DELETE CASCADE');

        // Table planning_skills: compétences requises pour une affectation planning
        $this->addSql('CREATE TABLE planning_skills (
            id INT AUTO_INCREMENT NOT NULL,
            company_id INT NOT NULL,
            planning_id INT NOT NULL,
            skill_id INT NOT NULL,
            required_level INT NOT NULL DEFAULT 2,
            mandatory TINYINT(1) NOT NULL DEFAULT 1,
            notes LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_planningskill_company (company_id),
            INDEX idx_planningskill_planning (planning_id),
            INDEX idx_planningskill_skill (skill_id),
            UNIQUE INDEX planning_skill_unique (planning_id, skill_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE planning_skills
            ADD CONSTRAINT FK_planning_skills_company FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE,
            ADD CONSTRAINT FK_planning_skills_planning FOREIGN KEY (planning_id) REFERENCES planning (id) ON DELETE CASCADE,
            ADD CONSTRAINT FK_planning_skills_skill FOREIGN KEY (skill_id) REFERENCES skills (id) ON DELETE CASCADE');

        // Table technology_skills: liaison entre technologies et compétences suggérées
        $this->addSql('CREATE TABLE technology_skills (
            technology_id INT NOT NULL,
            skill_id INT NOT NULL,
            INDEX IDX_technology_skills_technology (technology_id),
            INDEX IDX_technology_skills_skill (skill_id),
            PRIMARY KEY(technology_id, skill_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE technology_skills
            ADD CONSTRAINT FK_technology_skills_technology FOREIGN KEY (technology_id) REFERENCES technologies (id) ON DELETE CASCADE,
            ADD CONSTRAINT FK_technology_skills_skill FOREIGN KEY (skill_id) REFERENCES skills (id) ON DELETE CASCADE');

        // Table contributor_technologies: technologies maîtrisées par les collaborateurs
        $this->addSql('CREATE TABLE contributor_technologies (
            id INT AUTO_INCREMENT NOT NULL,
            company_id INT NOT NULL,
            contributor_id INT NOT NULL,
            technology_id INT NOT NULL,
            self_assessment_level INT NOT NULL,
            manager_assessment_level INT DEFAULT NULL,
            years_of_experience NUMERIC(4, 1) DEFAULT NULL,
            first_used_date DATE DEFAULT NULL,
            last_used_date DATE DEFAULT NULL,
            primary_context VARCHAR(20) NOT NULL DEFAULT \'professional\',
            wants_to_use TINYINT(1) NOT NULL DEFAULT 1,
            wants_to_improve TINYINT(1) NOT NULL DEFAULT 0,
            version_used VARCHAR(50) DEFAULT NULL,
            notes LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_contributortechnology_company (company_id),
            INDEX idx_contributortechnology_contributor (contributor_id),
            INDEX idx_contributortechnology_technology (technology_id),
            UNIQUE INDEX contributor_technology_unique (contributor_id, technology_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE contributor_technologies
            ADD CONSTRAINT FK_contributor_technologies_company FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE,
            ADD CONSTRAINT FK_contributor_technologies_contributor FOREIGN KEY (contributor_id) REFERENCES contributors (id) ON DELETE CASCADE,
            ADD CONSTRAINT FK_contributor_technologies_technology FOREIGN KEY (technology_id) REFERENCES technologies (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // Suppression de contributor_technologies
        $this->addSql('ALTER TABLE contributor_technologies DROP FOREIGN KEY FK_contributor_technologies_company');
        $this->addSql('ALTER TABLE contributor_technologies DROP FOREIGN KEY FK_contributor_technologies_contributor');
        $this->addSql('ALTER TABLE contributor_technologies DROP FOREIGN KEY FK_contributor_technologies_technology');
        $this->addSql('DROP TABLE contributor_technologies');

        // Suppression de technology_skills
        $this->addSql('ALTER TABLE technology_skills DROP FOREIGN KEY FK_technology_skills_technology');
        $this->addSql('ALTER TABLE technology_skills DROP FOREIGN KEY FK_technology_skills_skill');
        $this->addSql('DROP TABLE technology_skills');

        // Suppression de planning_skills
        $this->addSql('ALTER TABLE planning_skills DROP FOREIGN KEY FK_planning_skills_company');
        $this->addSql('ALTER TABLE planning_skills DROP FOREIGN KEY FK_planning_skills_planning');
        $this->addSql('ALTER TABLE planning_skills DROP FOREIGN KEY FK_planning_skills_skill');
        $this->addSql('DROP TABLE planning_skills');

        // Suppression de project_skills
        $this->addSql('ALTER TABLE project_skills DROP FOREIGN KEY FK_project_skills_company');
        $this->addSql('ALTER TABLE project_skills DROP FOREIGN KEY FK_project_skills_project');
        $this->addSql('ALTER TABLE project_skills DROP FOREIGN KEY FK_project_skills_skill');
        $this->addSql('DROP TABLE project_skills');
    }
}
