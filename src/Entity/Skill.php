<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interface\CompanyOwnedInterface;
use App\Repository\SkillRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SkillRepository::class)]
#[ORM\Table(name: 'skills')]
#[ORM\Index(name: 'idx_skill_company', columns: ['company_id'])]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['name'], message: 'Cette compétence existe déjà')]
class Skill implements Stringable, CompanyOwnedInterface
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

    #[ORM\Column(type: 'string', length: 100, unique: true)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire')]
    #[Assert\Length(max: 100, maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères')]
    public ?string $name = null {
        get => $this->name;
        set {
            $this->name = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank(message: 'La catégorie est obligatoire')]
    #[Assert\Choice(choices: ['technique', 'soft_skill', 'methodologie', 'langue'], message: 'Catégorie invalide')]
    public ?string $category = null {
        get => $this->category;
        set {
            $this->category = $value;
        }
    }

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $description = null {
        get => $this->description;
        set {
            $this->description = $value;
        }
    }

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    public bool $active = true {
        get => $this->active;
        set {
            $this->active = $value;
        }
    }

    #[ORM\Column(type: 'datetime')]
    public ?DateTimeInterface $createdAt = null {
        get => $this->createdAt;
        set {
            $this->createdAt = $value;
        }
    }

    #[ORM\Column(type: 'datetime')]
    public ?DateTimeInterface $updatedAt = null {
        get => $this->updatedAt;
        set {
            $this->updatedAt = $value;
        }
    }

    #[ORM\OneToMany(mappedBy: 'skill', targetEntity: ContributorSkill::class, orphanRemoval: true)]
    private Collection $contributorSkills;

    public function __construct()
    {
        $this->contributorSkills = new ArrayCollection();
        $this->createdAt         = new DateTime();
        $this->updatedAt         = new DateTime();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new DateTime();
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @return Collection<int, ContributorSkill>
     */
    public function getContributorSkills(): Collection
    {
        return $this->contributorSkills;
    }

    public function addContributorSkill(ContributorSkill $contributorSkill): self
    {
        if (!$this->contributorSkills->contains($contributorSkill)) {
            $this->contributorSkills->add($contributorSkill);
            $contributorSkill->setSkill($this);
        }

        return $this;
    }

    public function removeContributorSkill(ContributorSkill $contributorSkill): self
    {
        if ($this->contributorSkills->removeElement($contributorSkill)) {
            if ($contributorSkill->getSkill() === $this) {
                $contributorSkill->setSkill(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }

    /**
     * Retourne le libellé de la catégorie.
     */
    public function getCategoryLabel(): string
    {
        return match ($this->category) {
            'technique'    => 'Technique',
            'soft_skill'   => 'Soft Skill',
            'methodologie' => 'Méthodologie',
            'langue'       => 'Langue',
            default        => $this->category,
        };
    }

    /**
     * Retourne le nombre de contributeurs ayant cette compétence.
     */
    public function getContributorCount(): int
    {
        return $this->contributorSkills->count();
    }

    // ========== Compatibility methods ==========

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 public private(set), prefer direct access: $skill->id.
     */
    public function getId(): ?int
    {
        return $this->id;
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
     * With PHP 8.4 property hooks, prefer direct access: $skill->name.
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $skill->name = $value.
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $skill->category.
     */
    public function getCategory(): ?string
    {
        return $this->category;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $skill->category = $value.
     */
    public function setCategory(string $category): self
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $skill->description.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $skill->description = $value.
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $skill->active = $value.
     */
    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $skill->createdAt.
     */
    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $skill->createdAt = $value.
     */
    public function setCreatedAt(DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $skill->updatedAt.
     */
    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $skill->updatedAt = $value.
     */
    public function setUpdatedAt(DateTime $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
