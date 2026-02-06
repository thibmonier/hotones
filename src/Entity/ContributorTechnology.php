<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interface\CompanyOwnedInterface;
use App\Repository\ContributorTechnologyRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Technologie maîtrisée par un collaborateur.
 * Permet de tracer les compétences techniques sur les langages, frameworks, outils, etc.
 */
#[ORM\Entity(repositoryClass: ContributorTechnologyRepository::class)]
#[ORM\Table(name: 'contributor_technologies')]
#[ORM\UniqueConstraint(name: 'contributor_technology_unique', columns: ['contributor_id', 'technology_id'])]
#[ORM\Index(name: 'idx_contributortechnology_company', columns: ['company_id'])]
#[ORM\Index(name: 'idx_contributortechnology_contributor', columns: ['contributor_id'])]
#[ORM\Index(name: 'idx_contributortechnology_technology', columns: ['technology_id'])]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(
    fields: ['contributor', 'technology'],
    message: 'Ce contributeur possède déjà cette technologie',
)]
class ContributorTechnology implements CompanyOwnedInterface
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

    // Contextes d'utilisation
    public const CONTEXT_PROFESSIONAL = 'professional';
    public const CONTEXT_PERSONAL     = 'personal';
    public const CONTEXT_TRAINING     = 'training';
    public const CONTEXT_ACADEMIC     = 'academic';

    public const CONTEXTS = [
        self::CONTEXT_PROFESSIONAL => 'Professionnel',
        self::CONTEXT_PERSONAL     => 'Personnel',
        self::CONTEXT_TRAINING     => 'Formation',
        self::CONTEXT_ACADEMIC     => 'Académique',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private Company $company;

    #[ORM\ManyToOne(targetEntity: Contributor::class, inversedBy: 'contributorTechnologies')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'Le contributeur est obligatoire')]
    private ?Contributor $contributor = null;

    #[ORM\ManyToOne(targetEntity: Technology::class, inversedBy: 'contributorTechnologies')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'La technologie est obligatoire')]
    private ?Technology $technology = null;

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

    /**
     * Années d'expérience avec cette technologie.
     */
    #[ORM\Column(type: 'decimal', precision: 4, scale: 1, nullable: true)]
    #[Assert\PositiveOrZero]
    private ?string $yearsOfExperience = null;

    /**
     * Date de première utilisation de la technologie.
     */
    #[ORM\Column(type: 'date', nullable: true)]
    private ?DateTimeInterface $firstUsedDate = null;

    /**
     * Date de dernière utilisation de la technologie.
     */
    #[ORM\Column(type: 'date', nullable: true)]
    private ?DateTimeInterface $lastUsedDate = null;

    /**
     * Contexte principal d'utilisation.
     */
    #[ORM\Column(type: 'string', length: 20)]
    private string $primaryContext = self::CONTEXT_PROFESSIONAL;

    /**
     * Indique si le collaborateur souhaite continuer à utiliser cette technologie.
     */
    #[ORM\Column(type: 'boolean')]
    private bool $wantsToUse = true;

    /**
     * Indique si le collaborateur souhaite monter en compétence sur cette technologie.
     */
    #[ORM\Column(type: 'boolean')]
    private bool $wantsToImprove = false;

    /**
     * Version spécifique maîtrisée (ex: PHP 8.3, React 18, etc.).
     */
    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $versionUsed = null;

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

    public function getContributor(): ?Contributor
    {
        return $this->contributor;
    }

    public function setContributor(?Contributor $contributor): self
    {
        $this->contributor = $contributor;

        return $this;
    }

    public function getTechnology(): ?Technology
    {
        return $this->technology;
    }

    public function setTechnology(?Technology $technology): self
    {
        $this->technology = $technology;

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

    public function getYearsOfExperience(): ?string
    {
        return $this->yearsOfExperience;
    }

    public function setYearsOfExperience(?string $yearsOfExperience): self
    {
        $this->yearsOfExperience = $yearsOfExperience;

        return $this;
    }

    public function getFirstUsedDate(): ?DateTimeInterface
    {
        return $this->firstUsedDate;
    }

    public function setFirstUsedDate(?DateTimeInterface $firstUsedDate): self
    {
        $this->firstUsedDate = $firstUsedDate;

        return $this;
    }

    public function getLastUsedDate(): ?DateTimeInterface
    {
        return $this->lastUsedDate;
    }

    public function setLastUsedDate(?DateTimeInterface $lastUsedDate): self
    {
        $this->lastUsedDate = $lastUsedDate;

        return $this;
    }

    public function getPrimaryContext(): string
    {
        return $this->primaryContext;
    }

    public function setPrimaryContext(string $primaryContext): self
    {
        $this->primaryContext = $primaryContext;

        return $this;
    }

    public function getPrimaryContextLabel(): string
    {
        return self::CONTEXTS[$this->primaryContext] ?? 'N/A';
    }

    public function wantsToUse(): bool
    {
        return $this->wantsToUse;
    }

    public function setWantsToUse(bool $wantsToUse): self
    {
        $this->wantsToUse = $wantsToUse;

        return $this;
    }

    public function wantsToImprove(): bool
    {
        return $this->wantsToImprove;
    }

    public function setWantsToImprove(bool $wantsToImprove): self
    {
        $this->wantsToImprove = $wantsToImprove;

        return $this;
    }

    public function getVersionUsed(): ?string
    {
        return $this->versionUsed;
    }

    public function setVersionUsed(?string $versionUsed): self
    {
        $this->versionUsed = $versionUsed;

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

    /**
     * Indique si la technologie est considérée comme "récente" (utilisée dans les 6 derniers mois).
     */
    public function isRecent(): bool
    {
        if ($this->lastUsedDate === null) {
            return false;
        }

        $sixMonthsAgo = new DateTime('-6 months');

        return $this->lastUsedDate >= $sixMonthsAgo;
    }

    /**
     * Indique si la technologie est considérée comme "obsolète" (non utilisée depuis plus de 2 ans).
     */
    public function isObsolete(): bool
    {
        if ($this->lastUsedDate === null) {
            return false;
        }

        $twoYearsAgo = new DateTime('-2 years');

        return $this->lastUsedDate < $twoYearsAgo;
    }

    /**
     * Calcule le score de pertinence pour le staffing.
     * Prend en compte : niveau, récence, volonté d'utiliser.
     */
    public function getStaffingScore(): int
    {
        $score = $this->getEffectiveLevel() * 25; // 25-100 points selon niveau

        // Bonus si récent
        if ($this->isRecent()) {
            $score += 20;
        }

        // Malus si obsolète
        if ($this->isObsolete()) {
            $score -= 30;
        }

        // Bonus si veut utiliser
        if ($this->wantsToUse) {
            $score += 10;
        }

        return max(0, min(100, $score));
    }
}
