<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Atelier business 2026-05-15 reflect — sprint-008 DB-MIG-ATELIER.
 *
 * Adds the following per atelier business decisions:
 *  - Order.win_probability (Q5) — sales-set probability 0-100 for forecast weighting
 *  - CompanySettings.ai_key_* + ai_monthly_budget_usd (Q8) — per-tenant AI configuration
 *  - ai_usage_log table (Q8) — AI consumption audit + cost monitoring
 *  - FULLTEXT indexes on clients.name, projects.name, orders.name (Q11) — search via MariaDB FULLTEXT
 *
 * @see project-management/analysis/atelier-business-prep.md (Q5, Q8, Q11)
 * @see project-management/sprints/sprint-008-ddd-phase1-and-tech-debt/tasks/DB-MIG-ATELIER-tasks.md
 */
final class Version20260506092354 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Atelier business reflect: Order.winProbability + CompanySettings.aiKeys* + AiUsageLog table + FULLTEXT indexes (sprint-008 DB-MIG-ATELIER).';
    }

    public function up(Schema $schema): void
    {
        // 1. AiUsageLog table — AI consumption audit per tenant
        $this->addSql('CREATE TABLE ai_usage_log (
            id INT AUTO_INCREMENT NOT NULL,
            provider VARCHAR(32) NOT NULL,
            model VARCHAR(64) NOT NULL,
            prompt_tokens INT NOT NULL,
            completion_tokens INT NOT NULL,
            cost_usd NUMERIC(10, 6) NOT NULL,
            occurred_at DATETIME NOT NULL,
            company_id INT NOT NULL,
            INDEX idx_ai_usage_company (company_id),
            INDEX idx_ai_usage_occurred_at (occurred_at),
            INDEX idx_ai_usage_provider (provider),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 ENGINE=InnoDB');
        $this->addSql('ALTER TABLE ai_usage_log ADD CONSTRAINT FK_B9BDAD34979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE');

        // 2. CompanySettings: per-tenant AI keys + monthly budget
        $this->addSql('ALTER TABLE company_settings ADD ai_key_anthropic LONGTEXT DEFAULT NULL, ADD ai_key_openai LONGTEXT DEFAULT NULL, ADD ai_key_mistral LONGTEXT DEFAULT NULL, ADD ai_key_google LONGTEXT DEFAULT NULL, ADD ai_monthly_budget_usd NUMERIC(10, 2) DEFAULT NULL');

        // 3. Order: win probability (sales-set)
        $this->addSql('ALTER TABLE orders ADD win_probability INT DEFAULT NULL');

        // 4. FULLTEXT indexes for cross-BC search (Q11) — MariaDB FULLTEXT on InnoDB
        $this->addSql('ALTER TABLE clients ADD FULLTEXT INDEX ftx_client_name (name)');
        $this->addSql('ALTER TABLE projects ADD FULLTEXT INDEX ftx_project_name (name)');
        $this->addSql('ALTER TABLE orders ADD FULLTEXT INDEX ftx_order_name (name)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE clients DROP INDEX ftx_client_name');
        $this->addSql('ALTER TABLE projects DROP INDEX ftx_project_name');
        $this->addSql('ALTER TABLE orders DROP INDEX ftx_order_name');

        $this->addSql('ALTER TABLE orders DROP win_probability');

        $this->addSql('ALTER TABLE company_settings DROP ai_key_anthropic, DROP ai_key_openai, DROP ai_key_mistral, DROP ai_key_google, DROP ai_monthly_budget_usd');

        $this->addSql('ALTER TABLE ai_usage_log DROP FOREIGN KEY FK_B9BDAD34979B1AD6');
        $this->addSql('DROP TABLE ai_usage_log');
    }
}
