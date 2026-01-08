<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interface\CompanyOwnedInterface;
use App\Repository\ProjectSubTaskRepository;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProjectSubTaskRepository::class)]
#[ORM\Table(name: 'project_sub_tasks')]
#[ORM\Index(name: 'idx_projectsubtask_company', columns: ['company_id'])]
class ProjectSubTask implements CompanyOwnedInterface
{
    public const STATUS_TODO        = 'todo';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_DONE        = 'done';
    public const STATUS_BLOCKED     = 'blocked';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private Company $company;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Project $project;

    #[ORM\ManyToOne(targetEntity: ProjectTask::class, inversedBy: 'subTasks')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ProjectTask $task;

    #[ORM\ManyToOne(targetEntity: Contributor::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Contributor $assignee = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    // Estimation initiale en heures (1j = 8h)
    #[ORM\Column(type: 'decimal', precision: 6, scale: 2)]
    private string $initialEstimatedHours = '0.00';

    // Reste à faire (RAF) en heures; initialisé avec initialEstimatedHours
    #[ORM\Column(type: 'decimal', precision: 6, scale: 2)]
    private string $remainingHours = '0.00';

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = self::STATUS_TODO;

    #[ORM\Column(type: 'integer')]
    private int $position = 0;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeInterface $updatedAt;

    public function __construct()
    {
        $now             = new DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
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

    public function getTask(): ProjectTask
    {
        return $this->task;
    }

    public function setTask(ProjectTask $task): self
    {
        $this->task    = $task;
        $this->project = $task->getProject();

        return $this;
    }

    public function getAssignee(): ?Contributor
    {
        return $this->assignee;
    }

    public function setAssignee(?Contributor $assignee): self
    {
        $this->assignee = $assignee;

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

    public function getInitialEstimatedHours(): string
    {
        return $this->initialEstimatedHours;
    }

    public function setInitialEstimatedHours(string $hours): self
    {
        $this->initialEstimatedHours = $hours;
        if ($this->remainingHours === '0.00') {
            $this->remainingHours = $hours;
        }

        return $this;
    }

    public function getRemainingHours(): string
    {
        return $this->remainingHours;
    }

    public function setRemainingHours(string $hours): self
    {
        $this->remainingHours = $hours;

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

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Somme des heures passées (timesheets) sur cette sous-tâche.
     */
    public function getTimeSpentHours(): string
    {
        $total = '0.00';
        // Parcourir les timesheets du projet pour éviter une relation bidirectionnelle lourde
        foreach ($this->project->getTimesheets() as $timesheet) {
            if ($timesheet->getSubTask() && $timesheet->getSubTask()->getId() === $this->getId()) {
                $total = bcadd($total, (string) $timesheet->getHours(), 2);
            }
        }

        return $total;
    }

    /**
     * Pourcentage d'avancement = temps passé / (temps passé + RAF) * 100.
     */
    public function getProgressPercentage(): int
    {
        $spent = $this->getTimeSpentHours();
        $raf   = $this->getRemainingHours();
        $den   = bcadd($spent, $raf, 2);
        if (bccomp($den, '0.00', 2) <= 0) {
            return 0;
        }
        $ratio = bcmul(bcdiv($spent, $den, 4), '100', 0);

        return (int) $ratio;
    }

    public static function getAvailableStatuses(): array
    {
        return [
            self::STATUS_TODO        => 'À faire',
            self::STATUS_IN_PROGRESS => 'En cours',
            self::STATUS_DONE        => 'Terminé',
            self::STATUS_BLOCKED     => 'Bloqué',
        ];
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
