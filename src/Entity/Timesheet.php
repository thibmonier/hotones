<?php

namespace App\Entity;

use App\Repository\TimesheetRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TimesheetRepository::class)]
#[ORM\Table(name: 'timesheets')]
class Timesheet
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

    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $date;

    // Durée en heures (ex: 7.5)
    #[ORM\Column(type: 'decimal', precision: 5, scale: 2)]
    private string $hours;

    #[ORM\Column(type: 'text', nullable: true)]
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

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }
    public function setDate(\DateTimeInterface $date): self
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
