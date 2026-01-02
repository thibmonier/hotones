<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Entity\Interface\CompanyOwnedInterface;
use App\Repository\TimesheetRepository;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TimesheetRepository::class)]
#[ORM\Table(name: 'timesheets', indexes: [
    new ORM\Index(name: 'idx_timesheet_project', columns: ['project_id']),
    new ORM\Index(name: 'idx_timesheet_contributor', columns: ['contributor_id']),
    new ORM\Index(name: 'idx_timesheet_date', columns: ['date']),
    new ORM\Index(name: 'idx_timesheet_company', columns: ['company_id']),
])]
#[ApiResource(
    operations: [
        new Get(security: "is_granted('ROLE_USER')"),
        new GetCollection(security: "is_granted('ROLE_USER')"),
        new Post(security: "is_granted('ROLE_INTERVENANT')"),
        new Put(security: "is_granted('ROLE_INTERVENANT') and object.contributor.getUser() == user"),
        new Delete(security: "is_granted('ROLE_CHEF_PROJET') or (is_granted('ROLE_INTERVENANT') and object.contributor.getUser() == user)"),
    ],
    normalizationContext: ['groups' => ['timesheet:read']],
    denormalizationContext: ['groups' => ['timesheet:write']],
    paginationItemsPerPage: 50,
)]
class Timesheet implements CompanyOwnedInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['timesheet:read'])]
    public private(set) ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    public Company $company {
        get => $this->company;
        set {
            $this->company = $value;
        }
    }

    #[ORM\ManyToOne(targetEntity: Contributor::class, inversedBy: 'timesheets')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['timesheet:read', 'timesheet:write'])]
    public Contributor $contributor {
        get => $this->contributor;
        set {
            $this->contributor = $value;
        }
    }

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'timesheets')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['timesheet:read', 'timesheet:write'])]
    public Project $project {
        get => $this->project;
        set {
            $this->project = $value;
        }
    }

    // Lien optionnel vers une tâche du projet
    #[ORM\ManyToOne(targetEntity: ProjectTask::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['timesheet:read', 'timesheet:write'])]
    public ?ProjectTask $task = null {
        get => $this->task;
        set {
            $this->task = $value;
        }
    }

    // Lien optionnel vers une sous-tâche du projet
    #[ORM\ManyToOne(targetEntity: ProjectSubTask::class)]
    #[ORM\JoinColumn(name: 'sub_task_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    #[Groups(['timesheet:read', 'timesheet:write'])]
    public ?ProjectSubTask $subTask = null {
        get => $this->subTask;
        set {
            $this->subTask = $value;
        }
    }

    #[ORM\Column(type: 'date')]
    #[Groups(['timesheet:read', 'timesheet:write'])]
    public DateTimeInterface $date {
        get => $this->date;
        set {
            $this->date = $value;
        }
    }

    // Durée en heures (ex: 7.5)
    #[ORM\Column(type: 'decimal', precision: 5, scale: 2)]
    #[Groups(['timesheet:read', 'timesheet:write'])]
    public string $hours {
        get => $this->hours;
        set {
            $this->hours = $value;
        }
    }

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['timesheet:read', 'timesheet:write'])]
    public ?string $notes = null {
        get => $this->notes;
        set {
            $this->notes = $value;
        }
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
     * With PHP 8.4 property hooks, prefer direct access: $timesheet->contributor.
     */
    public function getContributor(): Contributor
    {
        return $this->contributor;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $timesheet->contributor = $value.
     */
    public function setContributor(Contributor $value): self
    {
        $this->contributor = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $timesheet->project.
     */
    public function getProject(): Project
    {
        return $this->project;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $timesheet->project = $value.
     */
    public function setProject(Project $value): self
    {
        $this->project = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $timesheet->task.
     */
    public function getTask(): ?ProjectTask
    {
        return $this->task;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $timesheet->task = $value.
     */
    public function setTask(?ProjectTask $value): self
    {
        $this->task = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $timesheet->subTask.
     */
    public function getSubTask(): ?ProjectSubTask
    {
        return $this->subTask;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $timesheet->subTask = $value.
     */
    public function setSubTask(?ProjectSubTask $value): self
    {
        $this->subTask = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $timesheet->date.
     */
    public function getDate(): DateTimeInterface
    {
        return $this->date;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $timesheet->date = $value.
     */
    public function setDate(DateTimeInterface $value): self
    {
        $this->date = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $timesheet->hours.
     */
    public function getHours(): string
    {
        return $this->hours;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $timesheet->hours = $value.
     */
    public function setHours(string $value): self
    {
        $this->hours = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $timesheet->notes.
     */
    public function getNotes(): ?string
    {
        return $this->notes;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $timesheet->notes = $value.
     */
    public function setNotes(?string $value): self
    {
        $this->notes = $value;

        return $this;
    }
}
