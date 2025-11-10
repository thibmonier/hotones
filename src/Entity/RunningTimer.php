<?php

namespace App\Entity;

use App\Repository\RunningTimerRepository;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RunningTimerRepository::class)]
#[ORM\Table(name: 'running_timers')]
class RunningTimer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Contributor::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Contributor $contributor;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Project $project;

    #[ORM\ManyToOne(targetEntity: ProjectTask::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?ProjectTask $task = null;

    #[ORM\ManyToOne(targetEntity: ProjectSubTask::class)]
    #[ORM\JoinColumn(name: 'sub_task_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?ProjectSubTask $subTask = null;

    #[ORM\Column(type: 'datetime')]
    private DateTimeInterface $startedAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTimeInterface $stoppedAt = null;

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

    public function getStartedAt(): DateTimeInterface
    {
        return $this->startedAt;
    }

    public function setStartedAt(DateTimeInterface $startedAt): self
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getStoppedAt(): ?DateTimeInterface
    {
        return $this->stoppedAt;
    }

    public function setStoppedAt(?DateTimeInterface $stoppedAt): self
    {
        $this->stoppedAt = $stoppedAt;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->stoppedAt === null;
    }
}
