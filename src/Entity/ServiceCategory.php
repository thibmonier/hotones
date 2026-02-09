<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interface\CompanyOwnedInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'service_categories')]
#[ORM\Index(name: 'idx_servicecategory_company', columns: ['company_id'])]
class ServiceCategory implements CompanyOwnedInterface
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

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    public ?string $description = null {
        get => $this->description;
        set {
            $this->description = $value;
        }
    }

    // Couleur hexadécimale pour l'affichage
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
    #[ORM\OneToMany(mappedBy: 'serviceCategory', targetEntity: Project::class)]
    private Collection $projects;

    public function __construct()
    {
        $this->projects = new ArrayCollection();
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
            $project->setServiceCategory($this);
        }

        return $this;
    }

    public function removeProject(Project $project): self
    {
        if ($this->projects->removeElement($project)) {
            if ($project->getServiceCategory() === $this) {
                $project->setServiceCategory(null);
            }
        }

        return $this;
    }

    public function isActive(): bool
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
     * With PHP 8.4 public private(set), prefer direct access: $serviceCategory->id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $serviceCategory->name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $serviceCategory->name = $value.
     */
    public function setName(string $value): self
    {
        $this->name = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $serviceCategory->description.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $serviceCategory->description = $value.
     */
    public function setDescription(?string $value): self
    {
        $this->description = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $serviceCategory->color.
     */
    public function getColor(): ?string
    {
        return $this->color;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $serviceCategory->color = $value.
     */
    public function setColor(?string $value): self
    {
        $this->color = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $serviceCategory->active.
     */
    public function getActive(): bool
    {
        return $this->active;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $serviceCategory->active = $value.
     */
    public function setActive(bool $value): self
    {
        $this->active = $value;

        return $this;
    }
}
