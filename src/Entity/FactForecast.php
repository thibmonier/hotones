<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\FactForecastRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FactForecastRepository::class)]
#[ORM\Table(name: 'fact_forecast')]
#[ORM\Index(columns: ['period_start', 'period_end'], name: 'idx_period')]
#[ORM\Index(columns: ['scenario'], name: 'idx_scenario')]
#[ORM\Index(columns: ['created_at'], name: 'idx_created_at')]
class FactForecast
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private DateTimeImmutable $periodStart;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private DateTimeImmutable $periodEnd;

    /**
     * Scenario: realistic, optimistic, pessimistic.
     */
    #[ORM\Column(length: 20)]
    private string $scenario;

    /**
     * Predicted revenue in euros.
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $predictedRevenue;

    /**
     * Confidence interval lower bound.
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $confidenceMin = null;

    /**
     * Confidence interval upper bound.
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $confidenceMax = null;

    /**
     * Historical revenue for comparison (actual value if period is past).
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $actualRevenue = null;

    /**
     * Prediction accuracy (if period is past): (1 - abs(predicted - actual) / actual) * 100.
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $accuracy = null;

    /**
     * Additional metadata (algorithm used, parameters, etc.).
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPeriodStart(): DateTimeImmutable
    {
        return $this->periodStart;
    }

    public function setPeriodStart(DateTimeImmutable $periodStart): self
    {
        $this->periodStart = $periodStart;

        return $this;
    }

    public function getPeriodEnd(): DateTimeImmutable
    {
        return $this->periodEnd;
    }

    public function setPeriodEnd(DateTimeImmutable $periodEnd): self
    {
        $this->periodEnd = $periodEnd;

        return $this;
    }

    public function getScenario(): string
    {
        return $this->scenario;
    }

    public function setScenario(string $scenario): self
    {
        $this->scenario = $scenario;

        return $this;
    }

    public function getPredictedRevenue(): string
    {
        return $this->predictedRevenue;
    }

    public function setPredictedRevenue(string $predictedRevenue): self
    {
        $this->predictedRevenue = $predictedRevenue;

        return $this;
    }

    public function getConfidenceMin(): ?string
    {
        return $this->confidenceMin;
    }

    public function setConfidenceMin(?string $confidenceMin): self
    {
        $this->confidenceMin = $confidenceMin;

        return $this;
    }

    public function getConfidenceMax(): ?string
    {
        return $this->confidenceMax;
    }

    public function setConfidenceMax(?string $confidenceMax): self
    {
        $this->confidenceMax = $confidenceMax;

        return $this;
    }

    public function getActualRevenue(): ?string
    {
        return $this->actualRevenue;
    }

    public function setActualRevenue(?string $actualRevenue): self
    {
        $this->actualRevenue = $actualRevenue;

        return $this;
    }

    public function getAccuracy(): ?string
    {
        return $this->accuracy;
    }

    public function setAccuracy(?string $accuracy): self
    {
        $this->accuracy = $accuracy;

        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): self
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
