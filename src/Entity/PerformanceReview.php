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
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private Company $company;

    #[ORM\Column(type: Types::INTEGER)]
    private int $year;

    #[ORM\ManyToOne(targetEntity: Contributor::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Contributor $contributor;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private User $manager;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $status = 'en_attente'; // en_attente, auto_eval_faite, eval_manager_faite, validee

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $selfEvaluation = null; // {achievements, strengths, improvements}

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $managerEvaluation = null; // {achievements, strengths, improvements, feedback}

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $objectives = null; // Array of SMART objectives for next year

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $overallRating = null; // 1-5 scale (optional)

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $interviewDate = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comments = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $updatedAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $validatedAt = null;

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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function setYear(int $year): self
    {
        $this->year = $year;

        return $this;
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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getSelfEvaluation(): ?array
    {
        return $this->selfEvaluation;
    }

    public function setSelfEvaluation(?array $selfEvaluation): self
    {
        $this->selfEvaluation = $selfEvaluation;

        return $this;
    }

    public function getManagerEvaluation(): ?array
    {
        return $this->managerEvaluation;
    }

    public function setManagerEvaluation(?array $managerEvaluation): self
    {
        $this->managerEvaluation = $managerEvaluation;

        return $this;
    }

    public function getObjectives(): ?array
    {
        return $this->objectives;
    }

    public function setObjectives(?array $objectives): self
    {
        $this->objectives = $objectives;

        return $this;
    }

    public function getOverallRating(): ?int
    {
        return $this->overallRating;
    }

    public function setOverallRating(?int $overallRating): self
    {
        $this->overallRating = $overallRating;

        return $this;
    }

    public function getInterviewDate(): ?DateTimeImmutable
    {
        return $this->interviewDate;
    }

    public function setInterviewDate(?DateTimeImmutable $interviewDate): self
    {
        $this->interviewDate = $interviewDate;

        return $this;
    }

    public function getComments(): ?string
    {
        return $this->comments;
    }

    public function setComments(?string $comments): self
    {
        $this->comments = $comments;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getValidatedAt(): ?DateTimeImmutable
    {
        return $this->validatedAt;
    }

    public function setValidatedAt(?DateTimeImmutable $validatedAt): self
    {
        $this->validatedAt = $validatedAt;

        return $this;
    }

    /**
     * Check if self-evaluation is completed.
     */
    public function isSelfEvaluationCompleted(): bool
    {
        return null !== $this->selfEvaluation
            && isset($this->selfEvaluation['achievements'])
            && isset($this->selfEvaluation['strengths'])
            && isset($this->selfEvaluation['improvements']);
    }

    /**
     * Check if manager evaluation is completed.
     */
    public function isManagerEvaluationCompleted(): bool
    {
        return null !== $this->managerEvaluation
            && isset($this->managerEvaluation['achievements'])
            && isset($this->managerEvaluation['strengths'])
            && isset($this->managerEvaluation['improvements'])
            && isset($this->managerEvaluation['feedback']);
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

    public function setCreatedAt(DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function setUpdatedAt(DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
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
