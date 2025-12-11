<?php

namespace App\Entity;

use App\Repository\ContributorSatisfactionRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

/**
 * Satisfaction mensuelle d'un collaborateur.
 * Saisie par le collaborateur lui-même chaque mois.
 */
#[ORM\Entity(repositoryClass: ContributorSatisfactionRepository::class)]
#[ORM\Table(name: 'contributor_satisfactions')]
#[ORM\UniqueConstraint(name: 'unique_contributor_period', columns: ['contributor_id', 'year', 'month'])]
#[ORM\HasLifecycleCallbacks]
class ContributorSatisfaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Contributor::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Contributor $contributor = null;

    /**
     * Année de la période évaluée.
     */
    #[ORM\Column(type: Types::INTEGER)]
    private int $year;

    /**
     * Mois de la période évaluée (1-12).
     */
    #[ORM\Column(type: Types::INTEGER)]
    private int $month;

    /**
     * Score de satisfaction global (1-5).
     * 1: Très insatisfait
     * 2: Insatisfait
     * 3: Neutre
     * 4: Satisfait
     * 5: Très satisfait.
     */
    #[ORM\Column(type: Types::INTEGER)]
    private int $overallScore;

    /**
     * Score concernant les projets/missions (1-5).
     */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $projectsScore = null;

    /**
     * Score concernant l'équipe/management (1-5).
     */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $teamScore = null;

    /**
     * Score concernant l'environnement de travail (1-5).
     */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $workEnvironmentScore = null;

    /**
     * Score concernant l'équilibre vie pro/perso (1-5).
     */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $workLifeBalanceScore = null;

    /**
     * Commentaire libre du collaborateur.
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comment = null;

    /**
     * Points positifs relevés par le collaborateur.
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $positivePoints = null;

    /**
     * Points d'amélioration suggérés par le collaborateur.
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $improvementPoints = null;

    /**
     * Date de saisie de la satisfaction.
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $submittedAt = null;

    /**
     * Date de création de l'enregistrement.
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $createdAt = null;

    /**
     * Date de dernière modification.
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt   = new DateTime();
        $this->updatedAt   = new DateTime();
        $this->submittedAt = new DateTime();
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        if ($this->createdAt === null) {
            $this->createdAt = new DateTime();
        }
        $this->updatedAt = new DateTime();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
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

    public function getYear(): int
    {
        return $this->year;
    }

    public function setYear(int $year): self
    {
        if ($year < 2000 || $year > 2100) {
            throw new InvalidArgumentException('L\'année doit être entre 2000 et 2100');
        }

        $this->year = $year;

        return $this;
    }

    public function getMonth(): int
    {
        return $this->month;
    }

    public function setMonth(int $month): self
    {
        if ($month < 1 || $month > 12) {
            throw new InvalidArgumentException('Le mois doit être entre 1 et 12');
        }

        $this->month = $month;

        return $this;
    }

    /**
     * Retourne le libellé du mois en français.
     */
    public function getMonthLabel(): string
    {
        return match ($this->month) {
            1       => 'Janvier',
            2       => 'Février',
            3       => 'Mars',
            4       => 'Avril',
            5       => 'Mai',
            6       => 'Juin',
            7       => 'Juillet',
            8       => 'Août',
            9       => 'Septembre',
            10      => 'Octobre',
            11      => 'Novembre',
            12      => 'Décembre',
            default => '',
        };
    }

    /**
     * Retourne la période au format "Mois Année" (ex: "Janvier 2024").
     */
    public function getPeriodLabel(): string
    {
        return $this->getMonthLabel().' '.$this->year;
    }

    public function getOverallScore(): int
    {
        return $this->overallScore;
    }

    public function setOverallScore(int $score): self
    {
        if ($score < 1 || $score > 5) {
            throw new InvalidArgumentException('Le score doit être entre 1 et 5');
        }

        $this->overallScore = $score;

        return $this;
    }

    public function getProjectsScore(): ?int
    {
        return $this->projectsScore;
    }

    public function setProjectsScore(?int $score): self
    {
        if ($score !== null && ($score < 1 || $score > 5)) {
            throw new InvalidArgumentException('Le score doit être entre 1 et 5');
        }

        $this->projectsScore = $score;

        return $this;
    }

    public function getTeamScore(): ?int
    {
        return $this->teamScore;
    }

    public function setTeamScore(?int $score): self
    {
        if ($score !== null && ($score < 1 || $score > 5)) {
            throw new InvalidArgumentException('Le score doit être entre 1 et 5');
        }

        $this->teamScore = $score;

        return $this;
    }

    public function getWorkEnvironmentScore(): ?int
    {
        return $this->workEnvironmentScore;
    }

    public function setWorkEnvironmentScore(?int $score): self
    {
        if ($score !== null && ($score < 1 || $score > 5)) {
            throw new InvalidArgumentException('Le score doit être entre 1 et 5');
        }

        $this->workEnvironmentScore = $score;

        return $this;
    }

    public function getWorkLifeBalanceScore(): ?int
    {
        return $this->workLifeBalanceScore;
    }

    public function setWorkLifeBalanceScore(?int $score): self
    {
        if ($score !== null && ($score < 1 || $score > 5)) {
            throw new InvalidArgumentException('Le score doit être entre 1 et 5');
        }

        $this->workLifeBalanceScore = $score;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getPositivePoints(): ?string
    {
        return $this->positivePoints;
    }

    public function setPositivePoints(?string $positivePoints): self
    {
        $this->positivePoints = $positivePoints;

        return $this;
    }

    public function getImprovementPoints(): ?string
    {
        return $this->improvementPoints;
    }

    public function setImprovementPoints(?string $improvementPoints): self
    {
        $this->improvementPoints = $improvementPoints;

        return $this;
    }

    public function getSubmittedAt(): ?DateTimeInterface
    {
        return $this->submittedAt;
    }

    public function setSubmittedAt(DateTimeInterface $submittedAt): self
    {
        $this->submittedAt = $submittedAt;

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
     * Retourne le libellé du score (Très insatisfait, Insatisfait, etc.).
     */
    public function getScoreLabel(int $score): string
    {
        return match ($score) {
            1       => 'Très insatisfait',
            2       => 'Insatisfait',
            3       => 'Neutre',
            4       => 'Satisfait',
            5       => 'Très satisfait',
            default => 'Non évalué',
        };
    }

    /**
     * Retourne le libellé du score global.
     */
    public function getOverallScoreLabel(): string
    {
        return $this->getScoreLabel($this->overallScore);
    }

    /**
     * Calcule le score moyen de toutes les dimensions.
     */
    public function getAverageScore(): float
    {
        $scores = array_filter([
            $this->overallScore,
            $this->projectsScore,
            $this->teamScore,
            $this->workEnvironmentScore,
            $this->workLifeBalanceScore,
        ]);

        if (empty($scores)) {
            return 0.0;
        }

        return array_sum($scores) / count($scores);
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
