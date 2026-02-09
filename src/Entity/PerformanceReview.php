<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interface\CompanyOwnedInterface;
use App\Repository\PerformanceReviewRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PerformanceReviewRepository::class)]
#[ORM\Table(name: 'performance_reviews')]
#[ORM\Index(columns: ['year'], name: 'idx_performance_review_year')]
#[ORM\Index(columns: ['status'], name: 'idx_performance_review_status')]
#[ORM\Index(columns: ['company_id'], name: 'idx_performancereview_company')]
#[ORM\HasLifecycleCallbacks]
class PerformanceReview implements CompanyOwnedInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    public private(set) ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private Company $company;

    #[ORM\Column(type: Types::INTEGER)]
    public int $year {
        get => $this->year;
        set {
            $this->year = $value;
        }
    }

    #[ORM\ManyToOne(targetEntity: Contributor::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Contributor $contributor;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private User $manager;

    #[ORM\Column(type: Types::STRING, length: 50)]
    public string $status = 'en_attente' {
        get => $this->status;
        set {
            $this->status = $value;
        }
    }

    #[ORM\Column(type: Types::JSON, nullable: true)]
    public ?array $selfEvaluation = null {
        get => $this->selfEvaluation;
        set {
            $this->selfEvaluation = $value;
        }
    }

    #[ORM\Column(type: Types::JSON, nullable: true)]
    public ?array $managerEvaluation = null {
        get => $this->managerEvaluation;
        set {
            $this->managerEvaluation = $value;
        }
    }

    #[ORM\Column(type: Types::JSON, nullable: true)]
    public ?array $objectives = null {
        get => $this->objectives;
        set {
            $this->objectives = $value;
        }
    }

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    public ?int $overallRating = null {
        get => $this->overallRating;
        set {
            $this->overallRating = $value;
        }
    }

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $interviewDate = null {
        get => $this->interviewDate;
        set {
            $this->interviewDate = $value;
        }
    }

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public ?string $comments = null {
        get => $this->comments;
        set {
            $this->comments = $value;
        }
    }

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public DateTimeImmutable $createdAt {
        get => $this->createdAt;
        set {
            $this->createdAt = $value;
        }
    }

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public DateTimeImmutable $updatedAt {
        get => $this->updatedAt;
        set {
            $this->updatedAt = $value;
        }
    }

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $validatedAt = null {
        get => $this->validatedAt;
        set {
            $this->validatedAt = $value;
        }
    }

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getContributor(): Contributor
    {
        return $this->contributor;
    }

    public function setContributor(Contributor $contributor): self
    {
        $this->contributor = $contributor;

        return $this;
    }

    public function getManager(): User
    {
        return $this->manager;
    }

    public function setManager(User $manager): self
    {
        $this->manager = $manager;

        return $this;
    }

    /**
     * Check if self-evaluation is completed.
     */
    public function isSelfEvaluationCompleted(): bool
    {
        return
            null !== $this->selfEvaluation
            && isset($this->selfEvaluation['achievements'])
            && isset($this->selfEvaluation['strengths'])
            && isset($this->selfEvaluation['improvements'])
        ;
    }

    /**
     * Check if manager evaluation is completed.
     */
    public function isManagerEvaluationCompleted(): bool
    {
        return
            null !== $this->managerEvaluation
            && isset($this->managerEvaluation['achievements'])
            && isset($this->managerEvaluation['strengths'])
            && isset($this->managerEvaluation['improvements'])
            && isset($this->managerEvaluation['feedback'])
        ;
    }

    /**
     * Check if review is validated.
     */
    public function isValidated(): bool
    {
        return 'validee' === $this->status;
    }

    /**
     * Mark review as validated.
     */
    public function validate(): self
    {
        $this->status      = 'validee';
        $this->validatedAt = new DateTimeImmutable();

        return $this;
    }

    /**
     * Get status label for display.
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'en_attente'         => 'En attente',
            'auto_eval_faite'    => 'Auto-évaluation complétée',
            'eval_manager_faite' => 'Évaluation manager complétée',
            'validee'            => 'Validée',
            default              => 'Inconnu',
        };
    }

    /**
     * Get progress percentage (0-100).
     */
    public function getProgressPercentage(): int
    {
        return match ($this->status) {
            'en_attente'         => 0,
            'auto_eval_faite'    => 33,
            'eval_manager_faite' => 66,
            'validee'            => 100,
            default              => 0,
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
     * With PHP 8.4 public private(set), prefer direct access: $review->id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $review->year.
     */
    public function getYear(): int
    {
        return $this->year;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $review->year = $value.
     */
    public function setYear(int $value): self
    {
        $this->year = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $review->status.
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $review->status = $value.
     */
    public function setStatus(string $value): self
    {
        $this->status = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $review->selfEvaluation.
     */
    public function getSelfEvaluation(): ?array
    {
        return $this->selfEvaluation;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $review->selfEvaluation = $value.
     */
    public function setSelfEvaluation(?array $value): self
    {
        $this->selfEvaluation = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $review->managerEvaluation.
     */
    public function getManagerEvaluation(): ?array
    {
        return $this->managerEvaluation;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $review->managerEvaluation = $value.
     */
    public function setManagerEvaluation(?array $value): self
    {
        $this->managerEvaluation = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $review->objectives.
     */
    public function getObjectives(): ?array
    {
        return $this->objectives;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $review->objectives = $value.
     */
    public function setObjectives(?array $value): self
    {
        $this->objectives = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $review->overallRating.
     */
    public function getOverallRating(): ?int
    {
        return $this->overallRating;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $review->overallRating = $value.
     */
    public function setOverallRating(?int $value): self
    {
        $this->overallRating = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $review->interviewDate.
     */
    public function getInterviewDate(): ?DateTimeImmutable
    {
        return $this->interviewDate;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $review->interviewDate = $value.
     */
    public function setInterviewDate(?DateTimeImmutable $value): self
    {
        $this->interviewDate = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $review->comments.
     */
    public function getComments(): ?string
    {
        return $this->comments;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $review->comments = $value.
     */
    public function setComments(?string $value): self
    {
        $this->comments = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $review->createdAt.
     */
    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $review->createdAt = $value.
     */
    public function setCreatedAt(DateTimeImmutable $value): self
    {
        $this->createdAt = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $review->updatedAt.
     */
    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $review->updatedAt = $value.
     */
    public function setUpdatedAt(DateTimeImmutable $value): self
    {
        $this->updatedAt = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $review->validatedAt.
     */
    public function getValidatedAt(): ?DateTimeImmutable
    {
        return $this->validatedAt;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $review->validatedAt = $value.
     */
    public function setValidatedAt(?DateTimeImmutable $value): self
    {
        $this->validatedAt = $value;

        return $this;
    }
}
