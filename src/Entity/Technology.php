<?php

namespace App\Entity;

use App\Entity\Interface\CompanyOwnedInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: \App\Repository\TechnologyRepository::class)]
#[ORM\Table(name: 'technologies')]
#[ORM\Index(name: 'idx_technology_company', columns: ['company_id'])]
class Technology implements CompanyOwnedInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
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

    #[ORM\Column(type: 'string', length: 100)]
    public string $name = '' {
        get => $this->name;
        set {
            $this->name = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 50)]
    public string $category = '' {
        get => $this->category;
        set {
            $this->category = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 7, nullable: true)]
    public ?string $color = null {
        get => $this->color;
        set {
            $this->color = $value;
        }
    }

    #[ORM\Column(type: 'boolean')]
    public bool $active = true {
        get => $this->active;
        set {
            $this->active = $value;
        }
    }

    // Relation avec les projets
    #[ORM\ManyToMany(targetEntity: Project::class, mappedBy: 'technologies')]
    private Collection $projects;

    // Compétences associées à cette technologie
    #[ORM\ManyToMany(targetEntity: Skill::class)]
    #[ORM\JoinTable(name: 'technology_skills')]
    private Collection $skills;

    // Collaborateurs maîtrisant cette technologie
    #[ORM\OneToMany(targetEntity: ContributorTechnology::class, mappedBy: 'technology')]
    private Collection $contributorTechnologies;

    public function __construct()
    {
        $this->projects                = new ArrayCollection();
        $this->skills                  = new ArrayCollection();
        $this->contributorTechnologies = new ArrayCollection();
    }

    /** @return Collection<int, Project> */
    public function getProjects(): Collection
    {
        return $this->projects;
    }

    public function addProject(Project $project): self
    {
        if (!$this->projects->contains($project)) {
            $this->projects[] = $project;
            $project->addTechnology($this);
        }

        return $this;
    }

    public function removeProject(Project $project): self
    {
        if ($this->projects->removeElement($project)) {
            $project->removeTechnology($this);
        }

        return $this;
    }

    /** @return Collection<int, Skill> */
    public function getSkills(): Collection
    {
        return $this->skills;
    }

    public function addSkill(Skill $skill): self
    {
        if (!$this->skills->contains($skill)) {
            $this->skills->add($skill);
        }

        return $this;
    }

    public function removeSkill(Skill $skill): self
    {
        $this->skills->removeElement($skill);

        return $this;
    }

    /** @return Collection<int, ContributorTechnology> */
    public function getContributorTechnologies(): Collection
    {
        return $this->contributorTechnologies;
    }

    /**
     * Retourne le nombre de collaborateurs maîtrisant cette technologie.
     */
    public function getContributorCount(): int
    {
        return $this->contributorTechnologies->count();
    }

    /**
     * Retourne les collaborateurs experts sur cette technologie.
     *
     * @return Collection<int, ContributorTechnology>
     */
    public function getExperts(): Collection
    {
        return $this->contributorTechnologies->filter(
            fn (ContributorTechnology $ct) => $ct->getEffectiveLevel() >= ContributorTechnology::LEVEL_EXPERT,
        );
    }

    public function isActive(): ?bool
    {
        return $this->active;
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
     * With PHP 8.4 public private(set), prefer direct access: $technology->id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $technology->name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $technology->name = $value.
     */
    public function setName(string $value): self
    {
        $this->name = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $technology->category.
     */
    public function getCategory(): string
    {
        return $this->category;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $technology->category = $value.
     */
    public function setCategory(string $value): self
    {
        $this->category = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $technology->color.
     */
    public function getColor(): ?string
    {
        return $this->color;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $technology->color = $value.
     */
    public function setColor(?string $value): self
    {
        $this->color = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $technology->active.
     */
    public function getActive(): bool
    {
        return $this->active;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $technology->active = $value.
     */
    public function setActive(bool $value): self
    {
        $this->active = $value;

        return $this;
    }
}
