<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\OnboardingTaskRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OnboardingTaskRepository::class)]
#[ORM\Table(name: 'onboarding_tasks')]
#[ORM\Index(columns: ['status'], name: 'idx_onboarding_task_status')]
#[ORM\Index(columns: ['due_date'], name: 'idx_onboarding_task_due_date')]
#[ORM\HasLifecycleCallbacks]
class OnboardingTask
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Contributor::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Contributor $contributor;

    #[ORM\ManyToOne(targetEntity: OnboardingTemplate::class, inversedBy: 'onboardingTasks')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?OnboardingTemplate $template = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $title;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $orderNum = 0;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $assignedTo = 'contributor'; // contributor or manager

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $type = 'action'; // action, lecture, formation, meeting

    #[ORM\Column(type: Types::INTEGER)]
    private int $daysAfterStart = 0; // Relative to employment start date

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $dueDate = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $status = 'a_faire'; // a_faire, en_cours, termine

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $completedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comments = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $updatedAt;

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

    public function getContributor(): Contributor
    {
        return $this->contributor;
    }

    public function setContributor(Contributor $contributor): self
    {
        $this->contributor = $contributor;

        return $this;
    }

    public function getTemplate(): ?OnboardingTemplate
    {
        return $this->template;
    }

    public function setTemplate(?OnboardingTemplate $template): self
    {
        $this->template = $template;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getOrderNum(): int
    {
        return $this->orderNum;
    }

    public function setOrderNum(int $orderNum): self
    {
        $this->orderNum = $orderNum;

        return $this;
    }

    public function getAssignedTo(): string
    {
        return $this->assignedTo;
    }

    public function setAssignedTo(string $assignedTo): self
    {
        $this->assignedTo = $assignedTo;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getDaysAfterStart(): int
    {
        return $this->daysAfterStart;
    }

    public function setDaysAfterStart(int $daysAfterStart): self
    {
        $this->daysAfterStart = $daysAfterStart;

        return $this;
    }

    public function getDueDate(): ?DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function setDueDate(?DateTimeImmutable $dueDate): self
    {
        $this->dueDate = $dueDate;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        // Auto-set completedAt when status changes to termine
        if ('termine' === $status && null === $this->completedAt) {
            $this->completedAt = new DateTimeImmutable();
        }

        return $this;
    }

    public function getCompletedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?DateTimeImmutable $completedAt): self
    {
        $this->completedAt = $completedAt;

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

    /**
     * Check if task is completed.
     */
    public function isCompleted(): bool
    {
        return 'termine' === $this->status;
    }

    /**
     * Check if task is overdue.
     */
    public function isOverdue(): bool
    {
        if (null === $this->dueDate || $this->isCompleted()) {
            return false;
        }

        return $this->dueDate < new DateTimeImmutable();
    }

    /**
     * Mark task as completed.
     */
    public function complete(?string $comments = null): self
    {
        $this->status      = 'termine';
        $this->completedAt = new DateTimeImmutable();

        if (null !== $comments) {
            $this->comments = $comments;
        }

        return $this;
    }

    /**
     * Get status label for display.
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'a_faire'  => 'À faire',
            'en_cours' => 'En cours',
            'termine'  => 'Terminé',
            default    => 'Inconnu',
        };
    }

    /**
     * Get type label for display.
     */
    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'action'    => 'Action',
            'lecture'   => 'Lecture',
            'formation' => 'Formation',
            'meeting'   => 'Réunion',
            default     => 'Autre',
        };
    }

    /**
     * Get type icon for display.
     */
    public function getTypeIcon(): string
    {
        return match ($this->type) {
            'action'    => 'bx-task',
            'lecture'   => 'bx-book',
            'formation' => 'bx-graduation',
            'meeting'   => 'bx-calendar',
            default     => 'bx-circle',
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
}
