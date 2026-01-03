<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interface\CompanyOwnedInterface;
use App\Repository\ProjectHealthScoreRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProjectHealthScoreRepository::class)]
#[ORM\Table(name: 'project_health_score')]
#[ORM\Index(columns: ['project_id', 'calculated_at'], name: 'idx_project_date')]
#[ORM\Index(columns: ['health_level'], name: 'idx_health_level')]
#[ORM\Index(columns: ['company_id'], name: 'idx_projecthealthscore_company')]
class ProjectHealthScore implements CompanyOwnedInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public private(set) ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private Company $company;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Project $project;

    /**
     * Health score (0-100).
     */
    #[ORM\Column(type: Types::SMALLINT)]
    public int $score {
        get => $this->score;
        set {
            $this->score = $value;
        }
    }

    /**
     * Health level: healthy (>80), warning (50-80), critical (<50).
     */
    #[ORM\Column(length: 20)]
    public string $healthLevel {
        get => $this->healthLevel;
        set {
            $this->healthLevel = $value;
        }
    }

    /**
     * Budget score component (0-100).
     */
    #[ORM\Column(type: Types::SMALLINT)]
    public int $budgetScore {
        get => $this->budgetScore;
        set {
            $this->budgetScore = $value;
        }
    }

    /**
     * Timeline score component (0-100).
     */
    #[ORM\Column(type: Types::SMALLINT)]
    public int $timelineScore {
        get => $this->timelineScore;
        set {
            $this->timelineScore = $value;
        }
    }

    /**
     * Velocity score component (0-100).
     */
    #[ORM\Column(type: Types::SMALLINT)]
    public int $velocityScore {
        get => $this->velocityScore;
        set {
            $this->velocityScore = $value;
        }
    }

    /**
     * Quality score component (0-100).
     */
    #[ORM\Column(type: Types::SMALLINT)]
    public int $qualityScore {
        get => $this->qualityScore;
        set {
            $this->qualityScore = $value;
        }
    }

    /**
     * Recommended actions (JSON array of strings).
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    public ?array $recommendations = null {
        get => $this->recommendations;
        set {
            $this->recommendations = $value;
        }
    }

    /**
     * Detailed breakdown (JSON with calculation details).
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    public ?array $details = null {
        get => $this->details;
        set {
            $this->details = $value;
        }
    }

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public DateTimeImmutable $calculatedAt {
        get => $this->calculatedAt;
        set {
            $this->calculatedAt = $value;
        }
    }

    public function __construct()
    {
        $this->calculatedAt = new DateTimeImmutable();
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function setProject(Project $project): self
    {
        $this->project = $project;

        return $this;
    }

    /**
     * Get badge color based on health level.
     */
    public function getBadgeColor(): string
    {
        return match ($this->healthLevel) {
            'healthy'  => 'success',
            'warning'  => 'warning',
            'critical' => 'danger',
            default    => 'secondary',
        };
    }

    /**
     * Get icon based on health level.
     */
    public function getIcon(): string
    {
        return match ($this->healthLevel) {
            'healthy'  => 'bx-check-circle',
            'warning'  => 'bx-error-circle',
            'critical' => 'bx-x-circle',
            default    => 'bx-help-circle',
        };
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

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 public private(set), prefer direct access: $healthScore->id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $healthScore->score.
     */
    public function getScore(): int
    {
        return $this->score;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $healthScore->score = $value.
     */
    public function setScore(int $value): self
    {
        $this->score = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $healthScore->healthLevel.
     */
    public function getHealthLevel(): string
    {
        return $this->healthLevel;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $healthScore->healthLevel = $value.
     */
    public function setHealthLevel(string $value): self
    {
        $this->healthLevel = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $healthScore->budgetScore.
     */
    public function getBudgetScore(): int
    {
        return $this->budgetScore;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $healthScore->budgetScore = $value.
     */
    public function setBudgetScore(int $value): self
    {
        $this->budgetScore = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $healthScore->timelineScore.
     */
    public function getTimelineScore(): int
    {
        return $this->timelineScore;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $healthScore->timelineScore = $value.
     */
    public function setTimelineScore(int $value): self
    {
        $this->timelineScore = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $healthScore->velocityScore.
     */
    public function getVelocityScore(): int
    {
        return $this->velocityScore;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $healthScore->velocityScore = $value.
     */
    public function setVelocityScore(int $value): self
    {
        $this->velocityScore = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $healthScore->qualityScore.
     */
    public function getQualityScore(): int
    {
        return $this->qualityScore;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $healthScore->qualityScore = $value.
     */
    public function setQualityScore(int $value): self
    {
        $this->qualityScore = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $healthScore->recommendations.
     */
    public function getRecommendations(): ?array
    {
        return $this->recommendations;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $healthScore->recommendations = $value.
     */
    public function setRecommendations(?array $value): self
    {
        $this->recommendations = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $healthScore->details.
     */
    public function getDetails(): ?array
    {
        return $this->details;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $healthScore->details = $value.
     */
    public function setDetails(?array $value): self
    {
        $this->details = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $healthScore->calculatedAt.
     */
    public function getCalculatedAt(): DateTimeImmutable
    {
        return $this->calculatedAt;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $healthScore->calculatedAt = $value.
     */
    public function setCalculatedAt(DateTimeImmutable $value): static
    {
        $this->calculatedAt = $value;

        return $this;
    }
}
