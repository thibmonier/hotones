<?php

declare(strict_types=1);

namespace App\Entity\Analytics;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Contributor;
use App\Repository\StaffingMetricsRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Table de faits pour les métriques de staffing
 * Contient les taux de staffing et TACE agrégés par période et dimensions.
 */
#[ORM\Entity(repositoryClass: StaffingMetricsRepository::class)]
#[ORM\Table(name: 'fact_staffing_metrics')]
#[ORM\UniqueConstraint(
    name: 'unique_staffing_metrics',
    columns: ['dim_time_id', 'dim_profile_id', 'contributor_id', 'granularity'],
)]
#[ApiResource(
    operations: [
        new Get(security: "is_granted('ROLE_MANAGER')"),
        new GetCollection(security: "is_granted('ROLE_MANAGER')"),
    ],
    normalizationContext: ['groups' => ['staffing:read']],
    paginationItemsPerPage: 50,
)]
class FactStaffingMetrics
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['staffing:read'])]
    private ?int $id = null;

    // Clés étrangères vers les dimensions
    #[ORM\ManyToOne(targetEntity: DimTime::class)]
    #[ORM\JoinColumn(nullable: false)]
    private DimTime $dimTime;

    #[ORM\ManyToOne(targetEntity: DimProfile::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?DimProfile $dimProfile = null;

    // Référence optionnelle vers le contributeur
    #[ORM\ManyToOne(targetEntity: Contributor::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Contributor $contributor = null;

    // Métriques de temps
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Groups(['staffing:read'])]
    private string $availableDays = '0.00'; // Jours disponibles (hors congés)

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Groups(['staffing:read'])]
    private string $workedDays = '0.00'; // Jours travaillés réels (hors congés)

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Groups(['staffing:read'])]
    private string $staffedDays = '0.00'; // Jours staffés sur missions

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Groups(['staffing:read'])]
    private string $vacationDays = '0.00'; // Jours de congés

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Groups(['staffing:read'])]
    private string $plannedDays = '0.00'; // Jours planifiés (futur)

    // KPIs calculés
    #[ORM\Column(type: 'decimal', precision: 5, scale: 2)]
    #[Groups(['staffing:read'])]
    private string $staffingRate = '0.00'; // Taux de staffing (%)

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2)]
    #[Groups(['staffing:read'])]
    private string $tace = '0.00'; // Taux d'Activité Congés Exclus (%)

    // Métadonnées
    #[ORM\Column(type: 'datetime')]
    #[Groups(['staffing:read'])]
    private DateTimeInterface $calculatedAt;

    #[ORM\Column(type: 'string', length: 50)]
    #[Groups(['staffing:read'])]
    private string $granularity; // weekly, monthly, quarterly

    #[ORM\Column(type: 'integer')]
    #[Groups(['staffing:read'])]
    private int $contributorCount = 0; // Nombre de contributeurs dans cette agrégation

    public function __construct()
    {
        $this->calculatedAt = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDimTime(): DimTime
    {
        return $this->dimTime;
    }

    public function setDimTime(DimTime $dimTime): self
    {
        $this->dimTime = $dimTime;

        return $this;
    }

    public function getDimProfile(): ?DimProfile
    {
        return $this->dimProfile;
    }

    public function setDimProfile(?DimProfile $dimProfile): self
    {
        $this->dimProfile = $dimProfile;

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

    public function getAvailableDays(): string
    {
        return $this->availableDays;
    }

    public function setAvailableDays(string $availableDays): self
    {
        $this->availableDays = $availableDays;

        return $this;
    }

    public function getWorkedDays(): string
    {
        return $this->workedDays;
    }

    public function setWorkedDays(string $workedDays): self
    {
        $this->workedDays = $workedDays;

        return $this;
    }

    public function getStaffedDays(): string
    {
        return $this->staffedDays;
    }

    public function setStaffedDays(string $staffedDays): self
    {
        $this->staffedDays = $staffedDays;

        return $this;
    }

    public function getVacationDays(): string
    {
        return $this->vacationDays;
    }

    public function setVacationDays(string $vacationDays): self
    {
        $this->vacationDays = $vacationDays;

        return $this;
    }

    public function getPlannedDays(): string
    {
        return $this->plannedDays;
    }

    public function setPlannedDays(string $plannedDays): self
    {
        $this->plannedDays = $plannedDays;

        return $this;
    }

    public function getStaffingRate(): string
    {
        return $this->staffingRate;
    }

    public function setStaffingRate(string $staffingRate): self
    {
        $this->staffingRate = $staffingRate;

        return $this;
    }

    public function getTace(): string
    {
        return $this->tace;
    }

    public function setTace(string $tace): self
    {
        $this->tace = $tace;

        return $this;
    }

    public function getCalculatedAt(): DateTimeInterface
    {
        return $this->calculatedAt;
    }

    public function setCalculatedAt(DateTimeInterface $calculatedAt): self
    {
        $this->calculatedAt = $calculatedAt;

        return $this;
    }

    public function getGranularity(): string
    {
        return $this->granularity;
    }

    public function setGranularity(string $granularity): self
    {
        $this->granularity = $granularity;

        return $this;
    }

    public function getContributorCount(): int
    {
        return $this->contributorCount;
    }

    public function setContributorCount(int $contributorCount): self
    {
        $this->contributorCount = $contributorCount;

        return $this;
    }

    /**
     * Calcule automatiquement le taux de staffing et le TACE.
     */
    public function calculateMetrics(): self
    {
        // Taux de staffing = (Temps staffé / Temps disponible) × 100
        if (bccomp($this->availableDays, '0', 2) > 0) {
            $this->staffingRate = bcmul(
                bcdiv($this->staffedDays, $this->availableDays, 4),
                '100',
                2,
            );
        } else {
            $this->staffingRate = '0.00';
        }

        // TACE = (Jours produits / Jours travaillés hors congés) × 100
        // Jours travaillés = Jours travaillés réels (sans congés)
        if (bccomp($this->workedDays, '0', 2) > 0) {
            $this->tace = bcmul(
                bcdiv($this->staffedDays, $this->workedDays, 4),
                '100',
                2,
            );
        } else {
            $this->tace = '0.00';
        }

        return $this;
    }
}
