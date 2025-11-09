<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'project_technology_versions')]
#[ORM\UniqueConstraint(name: 'uniq_project_tech', columns: ['project_id', 'technology_id'])]
class ProjectTechnology
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'projectTechnologies')]
    #[ORM\JoinColumn(name: 'project_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Project $project;

    #[ORM\ManyToOne(targetEntity: Technology::class)]
    #[ORM\JoinColumn(name: 'technology_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Technology $technology;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $version = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

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

    public function getTechnology(): Technology
    {
        return $this->technology;
    }

    public function setTechnology(Technology $technology): self
    {
        $this->technology = $technology;

        return $this;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(?string $version): self
    {
        $this->version = $version;

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
