<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interface\CompanyOwnedInterface;
use App\Repository\ProjectSkillRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Compétence requise pour un projet.
 * Permet de définir les compétences nécessaires pour travailler sur un projet
 * avec un niveau minimum requis.
 */
#[ORM\Entity(repositoryClass: ProjectSkillRepository::class)]
#[ORM\Table(name: 'project_skills')]
#[ORM\UniqueConstraint(name: 'project_skill_unique', columns: ['project_id', 'skill_id'])]
#[ORM\Index(name: 'idx_projectskill_company', columns: ['company_id'])]
#[ORM\Index(name: 'idx_projectskill_project', columns: ['project_id'])]
#[ORM\Index(name: 'idx_projectskill_skill', columns: ['skill_id'])]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(
    fields: ['project', 'skill'],
    message: 'Cette compétence est déjà requise pour ce projet',
)]
class ProjectSkill implements CompanyOwnedInterface
{
    public const LEVEL_BEGINNER     = 1;
    public const LEVEL_INTERMEDIATE = 2;
    public const LEVEL_CONFIRMED    = 3;
    public const LEVEL_EXPERT       = 4;

    public const LEVELS = [
        self::LEVEL_BEGINNER     => 'Débutant',
        self::LEVEL_INTERMEDIATE => 'Intermédiaire',
        self::LEVEL_CONFIRMED    => 'Confirmé',
        self::LEVEL_EXPERT       => 'Expert',
    ];

    public const PRIORITY_LOW      = 1;
    public const PRIORITY_MEDIUM   = 2;
    public const PRIORITY_HIGH     = 3;
    public const PRIORITY_CRITICAL = 4;

    public const PRIORITIES = [
        self::PRIORITY_LOW      => 'Optionnel',
        self::PRIORITY_MEDIUM   => 'Souhaitable',
        self::PRIORITY_HIGH     => 'Important',
        self::PRIORITY_CRITICAL => 'Indispensable',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private Company $company;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'projectSkills')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'Le projet est obligatoire')]
    private ?Project $project = null;

    #[ORM\ManyToOne(targetEntity: Skill::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'La compétence est obligatoire')]
    private ?Skill $skill = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotNull(message: 'Le niveau minimum requis est obligatoire')]
    #[Assert\Choice(
        choices: [self::LEVEL_BEGINNER, self::LEVEL_INTERMEDIATE, self::LEVEL_CONFIRMED, self::LEVEL_EXPERT],
        message: 'Niveau invalide',
    )]
    private int $requiredLevel = self::LEVEL_INTERMEDIATE;

    #[ORM\Column(type: 'integer')]
    #[Assert\Choice(
        choices: [self::PRIORITY_LOW, self::PRIORITY_MEDIUM, self::PRIORITY_HIGH, self::PRIORITY_CRITICAL],
        message: 'Priorité invalide',
    )]
    private int $priority = self::PRIORITY_MEDIUM;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'datetime')]
    private ?DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'datetime')]
    private ?DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
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

    public function getCompany(): Company
    {
        return $this->company;
    }

    public function setCompany(Company $company): self
    {
        $this->company = $company;

        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): self
    {
        $this->project = $project;

        return $this;
    }

    public function getSkill(): ?Skill
    {
        return $this->skill;
    }

    public function setSkill(?Skill $skill): self
    {
        $this->skill = $skill;

        return $this;
    }

    public function getRequiredLevel(): int
    {
        return $this->requiredLevel;
    }

    public function setRequiredLevel(int $requiredLevel): self
    {
        $this->requiredLevel = $requiredLevel;

        return $this;
    }

    public function getRequiredLevelLabel(): string
    {
        return self::LEVELS[$this->requiredLevel] ?? 'N/A';
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    public function getPriorityLabel(): string
    {
        return self::PRIORITIES[$this->priority] ?? 'N/A';
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

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    /**
     * Vérifie si un contributeur satisfait cette exigence de compétence.
     */
    public function isMetByContributor(Contributor $contributor): bool
    {
        foreach ($contributor->getContributorSkills() as $contributorSkill) {
            if ($contributorSkill->getSkill()?->getId() === $this->skill?->getId()) {
                return $contributorSkill->getEffectiveLevel() >= $this->requiredLevel;
            }
        }

        return false;
    }

    /**
     * Retourne la couleur badge selon la priorité.
     */
    public function getPriorityBadgeClass(): string
    {
        return match ($this->priority) {
            self::PRIORITY_CRITICAL => 'danger',
            self::PRIORITY_HIGH     => 'warning',
            self::PRIORITY_MEDIUM   => 'primary',
            self::PRIORITY_LOW      => 'secondary',
            default                 => 'secondary',
        };
    }

    /**
     * Retourne la couleur badge selon le niveau requis.
     */
    public function getLevelBadgeClass(): string
    {
        return match ($this->requiredLevel) {
            self::LEVEL_EXPERT       => 'danger',
            self::LEVEL_CONFIRMED    => 'warning',
            self::LEVEL_INTERMEDIATE => 'info',
            self::LEVEL_BEGINNER     => 'secondary',
            default                  => 'secondary',
        };
    }
}
