<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\TimesheetRepository;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: TimesheetRepository::class)]
#[ORM\Table(name: 'timesheets', indexes: [
    new ORM\Index(name: 'idx_timesheet_project', columns: ['project_id']),
    new ORM\Index(name: 'idx_timesheet_contributor', columns: ['contributor_id']),
    new ORM\Index(name: 'idx_timesheet_date', columns: ['date']),
])]
#[ApiResource(
    operations: [
        new Get(security: "is_granted('ROLE_USER')"),
        new GetCollection(security: "is_granted('ROLE_USER')"),
        new Post(security: "is_granted('ROLE_INTERVENANT')"),
        new Put(security: "is_granted('ROLE_INTERVENANT') and object.getContributor().getUser() == user"),
        new Delete(security: "is_granted('ROLE_CHEF_PROJET') or (is_granted('ROLE_INTERVENANT') and object.getContributor().getUser() == user)"),
    ],
    normalizationContext: ['groups' => ['timesheet:read']],
    denormalizationContext: ['groups' => ['timesheet:write']],
    paginationItemsPerPage: 50,
)]
class Timesheet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['timesheet:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Contributor::class, inversedBy: 'timesheets')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['timesheet:read', 'timesheet:write'])]
    private Contributor $contributor;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'timesheets')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['timesheet:read', 'timesheet:write'])]
    private Project $project;

    // Lien optionnel vers une tâche du projet
    #[ORM\ManyToOne(targetEntity: ProjectTask::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['timesheet:read', 'timesheet:write'])]
    private ?ProjectTask $task = null;

    // Lien optionnel vers une sous-tâche du projet
    #[ORM\ManyToOne(targetEntity: ProjectSubTask::class)]
    #[ORM\JoinColumn(name: 'sub_task_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    #[Groups(['timesheet:read', 'timesheet:write'])]
    private ?ProjectSubTask $subTask = null;

    #[ORM\Column(type: 'date')]
    #[Groups(['timesheet:read', 'timesheet:write'])]
    private DateTimeInterface $date;

    // Durée en heures (ex: 7.5)
    #[ORM\Column(type: 'decimal', precision: 5, scale: 2)]
    #[Groups(['timesheet:read', 'timesheet:write'])]
    private string $hours;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['timesheet:read', 'timesheet:write'])]
    private ?string $notes = null;

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

    public function getProject(): Project
    {
        return $this->project;
    }

    public function setProject(Project $project): self
    {
        $this->project = $project;

        return $this;
    }

    public function getTask(): ?ProjectTask
    {
        return $this->task;
    }

    public function setTask(?ProjectTask $task): self
    {
        $this->task = $task;

        return $this;
    }

    public function getSubTask(): ?ProjectSubTask
    {
        return $this->subTask;
    }

    public function setSubTask(?ProjectSubTask $subTask): self
    {
        $this->subTask = $subTask;

        return $this;
    }

    public function getDate(): DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getHours(): string
    {
        return $this->hours;
    }

    public function setHours(string $hours): self
    {
        $this->hours = $hours;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;

        return $this;
    }
}
