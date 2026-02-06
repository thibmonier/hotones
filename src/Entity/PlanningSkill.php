<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interface\CompanyOwnedInterface;
use App\Repository\PlanningSkillRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Compétence requise pour une affectation de planning.
 * Permet de surcharger les compétences du projet pour une affectation spécifique
 * ou d'ajouter des compétences additionnelles.
 */
#[ORM\Entity(repositoryClass: PlanningSkillRepository::class)]
#[ORM\Table(name: 'planning_skills')]
#[ORM\UniqueConstraint(name: 'planning_skill_unique', columns: ['planning_id', 'skill_id'])]
#[ORM\Index(name: 'idx_planningskill_company', columns: ['company_id'])]
#[ORM\Index(name: 'idx_planningskill_planning', columns: ['planning_id'])]
#[ORM\Index(name: 'idx_planningskill_skill', columns: ['skill_id'])]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(
    fields: ['planning', 'skill'],
    message: 'Cette compétence est déjà requise pour cette affectation',
)]
class PlanningSkill implements CompanyOwnedInterface
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

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private Company $company;

    #[ORM\ManyToOne(targetEntity: Planning::class, inversedBy: 'planningSkills')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'L\'affectation est obligatoire')]
    private ?Planning $planning = null;

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

    /**
     * Indique si cette compétence est obligatoire pour l'affectation.
     * Si false, c'est une compétence souhaitée mais non bloquante.
     */
    #[ORM\Column(type: 'boolean')]
    private bool $mandatory = true;

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

    public function getPlanning(): ?Planning
    {
        return $this->planning;
    }

    public function setPlanning(?Planning $planning): self
    {
        $this->planning = $planning;

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

    public function isMandatory(): bool
    {
        return $this->mandatory;
    }

    public function setMandatory(bool $mandatory): self
    {
        $this->mandatory = $mandatory;

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

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    /**
     * Vérifie si le collaborateur de l'affectation satisfait cette exigence.
     */
    public function isMetByAssignedContributor(): bool
    {
        $contributor = $this->planning?->getContributor();
        if (!$contributor) {
            return false;
        }

        foreach ($contributor->getContributorSkills() as $contributorSkill) {
            if ($contributorSkill->getSkill()?->getId() === $this->skill?->getId()) {
                return $contributorSkill->getEffectiveLevel() >= $this->requiredLevel;
            }
        }

        return false;
    }

    /**
     * Retourne le niveau du collaborateur pour cette compétence, ou null si non possédée.
     */
    public function getContributorLevel(): ?int
    {
        $contributor = $this->planning?->getContributor();
        if (!$contributor) {
            return null;
        }

        foreach ($contributor->getContributorSkills() as $contributorSkill) {
            if ($contributorSkill->getSkill()?->getId() === $this->skill?->getId()) {
                return $contributorSkill->getEffectiveLevel();
            }
        }

        return null;
    }

    /**
     * Retourne l'écart entre le niveau requis et le niveau du collaborateur.
     * Positif = collaborateur au-dessus, négatif = collaborateur en-dessous.
     */
    public function getLevelGap(): ?int
    {
        $contributorLevel = $this->getContributorLevel();
        if ($contributorLevel === null) {
            return null;
        }

        return $contributorLevel - $this->requiredLevel;
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

    /**
     * Retourne la couleur badge selon si l'exigence est satisfaite.
     */
    public function getStatusBadgeClass(): string
    {
        if ($this->isMetByAssignedContributor()) {
            return 'success';
        }

        return $this->mandatory ? 'danger' : 'warning';
    }
}
