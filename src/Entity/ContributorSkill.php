<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ContributorSkillRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ContributorSkillRepository::class)]
#[ORM\Table(name: 'contributor_skills')]
#[ORM\UniqueConstraint(name: 'contributor_skill_unique', columns: ['contributor_id', 'skill_id'])]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(
    fields: ['contributor', 'skill'],
    message: 'Ce contributeur possède déjà cette compétence',
)]
class ContributorSkill
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

    #[ORM\ManyToOne(targetEntity: Contributor::class, inversedBy: 'contributorSkills')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'Le contributeur est obligatoire')]
    private ?Contributor $contributor = null;

    #[ORM\ManyToOne(targetEntity: Skill::class, inversedBy: 'contributorSkills')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'La compétence est obligatoire')]
    private ?Skill $skill = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotNull(message: 'Le niveau d\'auto-évaluation est obligatoire')]
    #[Assert\Choice(
        choices: [self::LEVEL_BEGINNER, self::LEVEL_INTERMEDIATE, self::LEVEL_CONFIRMED, self::LEVEL_EXPERT],
        message: 'Niveau invalide',
    )]
    private ?int $selfAssessmentLevel = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Choice(
        choices: [self::LEVEL_BEGINNER, self::LEVEL_INTERMEDIATE, self::LEVEL_CONFIRMED, self::LEVEL_EXPERT],
        message: 'Niveau invalide',
    )]
    private ?int $managerAssessmentLevel = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?DateTimeInterface $dateAcquired = null;

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

    public function getContributor(): ?Contributor
    {
        return $this->contributor;
    }

    public function setContributor(?Contributor $contributor): self
    {
        $this->contributor = $contributor;

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

    public function getSelfAssessmentLevel(): ?int
    {
        return $this->selfAssessmentLevel;
    }

    public function setSelfAssessmentLevel(?int $selfAssessmentLevel): self
    {
        $this->selfAssessmentLevel = $selfAssessmentLevel;

        return $this;
    }

    public function getManagerAssessmentLevel(): ?int
    {
        return $this->managerAssessmentLevel;
    }

    public function setManagerAssessmentLevel(?int $managerAssessmentLevel): self
    {
        $this->managerAssessmentLevel = $managerAssessmentLevel;

        return $this;
    }

    public function getDateAcquired(): ?DateTimeInterface
    {
        return $this->dateAcquired;
    }

    public function setDateAcquired(?DateTimeInterface $dateAcquired): self
    {
        $this->dateAcquired = $dateAcquired;

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
     * Retourne le niveau effectif (évaluation manager si disponible, sinon auto-évaluation).
     */
    public function getEffectiveLevel(): int
    {
        return $this->managerAssessmentLevel ?? $this->selfAssessmentLevel ?? self::LEVEL_BEGINNER;
    }

    /**
     * Retourne le libellé du niveau effectif.
     */
    public function getEffectiveLevelLabel(): string
    {
        return self::LEVELS[$this->getEffectiveLevel()] ?? 'N/A';
    }

    /**
     * Retourne le libellé du niveau d'auto-évaluation.
     */
    public function getSelfAssessmentLevelLabel(): string
    {
        return $this->selfAssessmentLevel ? self::LEVELS[$this->selfAssessmentLevel] : 'N/A';
    }

    /**
     * Retourne le libellé du niveau d'évaluation manager.
     */
    public function getManagerAssessmentLevelLabel(): string
    {
        return $this->managerAssessmentLevel ? self::LEVELS[$this->managerAssessmentLevel] : 'Non évalué';
    }

    /**
     * Indique s'il y a un écart entre l'auto-évaluation et l'évaluation manager.
     */
    public function hasAssessmentGap(): bool
    {
        return $this->managerAssessmentLevel !== null
            && $this->selfAssessmentLevel    !== null
            && $this->managerAssessmentLevel !== $this->selfAssessmentLevel;
    }

    /**
     * Retourne l'écart d'évaluation (manager - self).
     */
    public function getAssessmentGap(): ?int
    {
        if ($this->managerAssessmentLevel === null || $this->selfAssessmentLevel === null) {
            return null;
        }

        return $this->managerAssessmentLevel - $this->selfAssessmentLevel;
    }

    /**
     * Retourne la couleur badge selon le niveau effectif.
     */
    public function getLevelBadgeClass(): string
    {
        return match ($this->getEffectiveLevel()) {
            self::LEVEL_EXPERT       => 'success',
            self::LEVEL_CONFIRMED    => 'primary',
            self::LEVEL_INTERMEDIATE => 'info',
            self::LEVEL_BEGINNER     => 'secondary',
            default                  => 'secondary',
        };
    }
}
