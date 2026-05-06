<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interface\CompanyOwnedInterface;
use App\Repository\AiUsageLogRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * AI provider usage log per tenant (atelier business 2026-05-15, Q8).
 *
 * Tracks each AI call for billing, cost monitoring, and budget enforcement.
 * Used by:
 *  - `CompanySettings.aiMonthlyBudgetUsd` enforcement
 *  - Admin dashboard for cost reporting
 *  - Audit trail of AI consumption per tenant
 */
#[ORM\Entity(repositoryClass: AiUsageLogRepository::class)]
#[ORM\Table(name: 'ai_usage_log')]
#[ORM\Index(name: 'idx_ai_usage_company', columns: ['company_id'])]
#[ORM\Index(name: 'idx_ai_usage_occurred_at', columns: ['occurred_at'])]
#[ORM\Index(name: 'idx_ai_usage_provider', columns: ['provider'])]
class AiUsageLog implements CompanyOwnedInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public private(set) ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Company $company;

    /**
     * Provider name: anthropic | openai | mistral | google.
     */
    #[ORM\Column(type: 'string', length: 32)]
    public string $provider = 'anthropic' {
        get => $this->provider;
        set {
            $this->provider = $value;
        }
    }

    /**
     * Model identifier (e.g. claude-opus-4-5, gpt-4o, mistral-large).
     */
    #[ORM\Column(type: 'string', length: 64)]
    public string $model = '' {
        get => $this->model;
        set {
            $this->model = $value;
        }
    }

    #[ORM\Column(type: 'integer')]
    public int $promptTokens = 0 {
        get => $this->promptTokens;
        set {
            $this->promptTokens = $value;
        }
    }

    #[ORM\Column(type: 'integer')]
    public int $completionTokens = 0 {
        get => $this->completionTokens;
        set {
            $this->completionTokens = $value;
        }
    }

    /**
     * Cost in USD (computed from provider pricing tables).
     */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 6)]
    public string $costUsd = '0' {
        get => $this->costUsd;
        set {
            $this->costUsd = $value;
        }
    }

    #[ORM\Column(type: 'datetime_immutable')]
    public private(set) DateTimeImmutable $occurredAt;

    public function __construct(Company $company)
    {
        $this->company = $company;
        $this->occurredAt = new DateTimeImmutable();
    }

    public function getCompany(): Company
    {
        return $this->company;
    }

    public function setCompany(Company $company): self
    {
        $this->company = $company;

        return $this;
    }
}
