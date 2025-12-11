<?php

declare(strict_types=1);

namespace App\Entity;

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
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['name'], message: 'Cette compétence existe déjà')]
class Skill implements Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100, unique: true)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire')]
    #[Assert\Length(max: 100, maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères')]
    private ?string $name = null;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank(message: 'La catégorie est obligatoire')]
    #[Assert\Choice(
        choices: ['technique', 'soft_skill', 'methodologie', 'langue'],
        message: 'Catégorie invalide',
    )]
    private ?string $category = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $active = true;

    #[ORM\OneToMany(mappedBy: 'skill', targetEntity: ContributorSkill::class, orphanRemoval: true)]
    private Collection $contributorSkills;

    #[ORM\Column(type: 'datetime')]
    private ?DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'datetime')]
    private ?DateTimeInterface $updatedAt = null;

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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;

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

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
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

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
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

    public function setCreatedAt(DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function setUpdatedAt(DateTime $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
