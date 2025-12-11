<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProjectHealthScoreRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectHealthScoreRepository::class)]
#[ORM\Table(name: 'project_health_score')]
#[ORM\Index(columns: ['project_id', 'calculated_at'], name: 'idx_project_date')]
#[ORM\Index(columns: ['health_level'], name: 'idx_health_level')]
class ProjectHealthScore
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Project $project;

    /**
     * Health score (0-100).
     */
    #[ORM\Column(type: Types::SMALLINT)]
    private int $score;

    /**
     * Health level: healthy (>80), warning (50-80), critical (<50).
     */
    #[ORM\Column(length: 20)]
    private string $healthLevel;

    /**
     * Budget score component (0-100).
     */
    #[ORM\Column(type: Types::SMALLINT)]
    private int $budgetScore;

    /**
     * Timeline score component (0-100).
     */
    #[ORM\Column(type: Types::SMALLINT)]
    private int $timelineScore;

    /**
     * Velocity score component (0-100).
     */
    #[ORM\Column(type: Types::SMALLINT)]
    private int $velocityScore;

    /**
     * Quality score component (0-100).
     */
    #[ORM\Column(type: Types::SMALLINT)]
    private int $qualityScore;

    /**
     * Recommended actions (JSON array of strings).
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $recommendations = null;

    /**
     * Detailed breakdown (JSON with calculation details).
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $details = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $calculatedAt;

    public function __construct()
    {
        $this->calculatedAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getScore(): int
    {
        return $this->score;
    }

    public function setScore(int $score): self
    {
        $this->score = $score;

        return $this;
    }

    public function getHealthLevel(): string
    {
        return $this->healthLevel;
    }

    public function setHealthLevel(string $healthLevel): self
    {
        $this->healthLevel = $healthLevel;

        return $this;
    }

    public function getBudgetScore(): int
    {
        return $this->budgetScore;
    }

    public function setBudgetScore(int $budgetScore): self
    {
        $this->budgetScore = $budgetScore;

        return $this;
    }

    public function getTimelineScore(): int
    {
        return $this->timelineScore;
    }

    public function setTimelineScore(int $timelineScore): self
    {
        $this->timelineScore = $timelineScore;

        return $this;
    }

    public function getVelocityScore(): int
    {
        return $this->velocityScore;
    }

    public function setVelocityScore(int $velocityScore): self
    {
        $this->velocityScore = $velocityScore;

        return $this;
    }

    public function getQualityScore(): int
    {
        return $this->qualityScore;
    }

    public function setQualityScore(int $qualityScore): self
    {
        $this->qualityScore = $qualityScore;

        return $this;
    }

    public function getRecommendations(): ?array
    {
        return $this->recommendations;
    }

    public function setRecommendations(?array $recommendations): self
    {
        $this->recommendations = $recommendations;

        return $this;
    }

    public function getDetails(): ?array
    {
        return $this->details;
    }

    public function setDetails(?array $details): self
    {
        $this->details = $details;

        return $this;
    }

    public function getCalculatedAt(): DateTimeImmutable
    {
        return $this->calculatedAt;
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

    public function setCalculatedAt(DateTimeImmutable $calculatedAt): static
    {
        $this->calculatedAt = $calculatedAt;

        return $this;
    }
}
