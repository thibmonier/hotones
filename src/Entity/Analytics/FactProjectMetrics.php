<?php

declare(strict_types=1);

namespace App\Entity\Analytics;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Order;
use App\Entity\Project;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Table de faits centrale pour les métriques de projets
 * Contient les KPIs agrégés par période et dimensions.
 */
#[ORM\Entity]
#[ORM\Table(name: 'fact_project_metrics')]
#[ORM\UniqueConstraint(
    name: 'unique_fact_metrics',
    columns: ['dim_time_id', 'dim_project_type_id', 'dim_project_manager_id', 'dim_sales_person_id', 'dim_project_director_id', 'granularity', 'project_id', 'order_id'],
)]
#[ApiResource(
    operations: [
        new Get(security: "is_granted('ROLE_MANAGER')"),
        new GetCollection(security: "is_granted('ROLE_MANAGER')"),
    ],
    normalizationContext: ['groups' => ['metrics:read']],
    paginationItemsPerPage: 50,
)]
class FactProjectMetrics
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['metrics:read'])]
    private ?int $id = null;

    // Clés étrangères vers les dimensions
    #[ORM\ManyToOne(targetEntity: DimTime::class)]
    #[ORM\JoinColumn(nullable: false)]
    private DimTime $dimTime;

    #[ORM\ManyToOne(targetEntity: DimProjectType::class)]
    #[ORM\JoinColumn(nullable: false)]
    private DimProjectType $dimProjectType;

    #[ORM\ManyToOne(targetEntity: DimContributor::class)]
    #[ORM\JoinColumn(nullable: true)] // Chef de projet peut être null
    private ?DimContributor $dimProjectManager = null;

    #[ORM\ManyToOne(targetEntity: DimContributor::class)]
    #[ORM\JoinColumn(nullable: true)] // Commercial peut être null
    private ?DimContributor $dimSalesPerson = null;

    #[ORM\ManyToOne(targetEntity: DimContributor::class)]
    #[ORM\JoinColumn(nullable: true)] // Directeur peut être null
    private ?DimContributor $dimProjectDirector = null;

    // Références optionnelles vers les entités sources
    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Project $project = null;

    #[ORM\ManyToOne(targetEntity: Order::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Order $order = null;

    // KPIs - Métriques de base
    #[ORM\Column(type: 'integer')]
    #[Groups(['metrics:read'])]
    private int $projectCount = 0; // Nombre de projets

    #[ORM\Column(type: 'integer')]
    #[Groups(['metrics:read'])]
    private int $activeProjectCount = 0; // Projets actifs

    #[ORM\Column(type: 'integer')]
    #[Groups(['metrics:read'])]
    private int $completedProjectCount = 0; // Projets terminés

    #[ORM\Column(type: 'integer')]
    #[Groups(['metrics:read'])]
    private int $orderCount = 0; // Nombre de devis

    #[ORM\Column(type: 'integer')]
    #[Groups(['metrics:read'])]
    private int $pendingOrderCount = 0; // Devis en attente de signature

    #[ORM\Column(type: 'integer')]
    #[Groups(['metrics:read'])]
    private int $wonOrderCount = 0; // Devis gagnés

    #[ORM\Column(type: 'integer')]
    #[Groups(['metrics:read'])]
    private int $signedOrderCount = 0; // Devis signés (statut signed)

    #[ORM\Column(type: 'integer')]
    #[Groups(['metrics:read'])]
    private int $lostOrderCount = 0; // Devis perdus (statut lost)

    #[ORM\Column(type: 'integer')]
    #[Groups(['metrics:read'])]
    private int $contributorCount = 0; // Nombre de contributeurs

    // KPIs - Métriques financières
    #[ORM\Column(type: 'decimal', precision: 15, scale: 2)]
    #[Groups(['metrics:read'])]
    private string $totalRevenue = '0.00'; // CA total

    #[ORM\Column(type: 'decimal', precision: 15, scale: 2)]
    #[Groups(['metrics:read'])]
    private string $totalCosts = '0.00'; // Coûts totaux

    #[ORM\Column(type: 'decimal', precision: 15, scale: 2)]
    #[Groups(['metrics:read'])]
    private string $grossMargin = '0.00'; // Marge brute (CA - Coûts)

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2)]
    #[Groups(['metrics:read'])]
    private string $marginPercentage = '0.00'; // Pourcentage de marge

    #[ORM\Column(type: 'decimal', precision: 15, scale: 2)]
    #[Groups(['metrics:read'])]
    private string $pendingRevenue = '0.00'; // CA potentiel (devis en attente)

    #[ORM\Column(type: 'decimal', precision: 15, scale: 2)]
    #[Groups(['metrics:read'])]
    private string $averageOrderValue = '0.00'; // Valeur moyenne des devis

    // KPIs - Métriques de temps
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Groups(['metrics:read'])]
    private string $totalSoldDays = '0.00'; // Jours vendus total

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Groups(['metrics:read'])]
    private string $totalWorkedDays = '0.00'; // Jours travaillés réels

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2)]
    #[Groups(['metrics:read'])]
    private string $utilizationRate = '0.00'; // Taux d'occupation (%)

    // Métadonnées
    #[ORM\Column(type: 'datetime')]
    #[Groups(['metrics:read'])]
    private DateTimeInterface $calculatedAt;

    #[ORM\Column(type: 'string', length: 50)]
    #[Groups(['metrics:read'])]
    private string $granularity; // monthly, quarterly, yearly

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

    public function getDimProjectType(): DimProjectType
    {
        return $this->dimProjectType;
    }

    public function setDimProjectType(DimProjectType $dimProjectType): self
    {
        $this->dimProjectType = $dimProjectType;

        return $this;
    }

    public function getDimProjectManager(): ?DimContributor
    {
        return $this->dimProjectManager;
    }

    public function setDimProjectManager(?DimContributor $dimProjectManager): self
    {
        $this->dimProjectManager = $dimProjectManager;

        return $this;
    }

    public function getDimSalesPerson(): ?DimContributor
    {
        return $this->dimSalesPerson;
    }

    public function setDimSalesPerson(?DimContributor $dimSalesPerson): self
    {
        $this->dimSalesPerson = $dimSalesPerson;

        return $this;
    }

    public function getDimProjectDirector(): ?DimContributor
    {
        return $this->dimProjectDirector;
    }

    public function setDimProjectDirector(?DimContributor $dimProjectDirector): self
    {
        $this->dimProjectDirector = $dimProjectDirector;

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

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): self
    {
        $this->order = $order;

        return $this;
    }

    // Getters/Setters pour les KPIs
    public function getProjectCount(): int
    {
        return $this->projectCount;
    }

    public function setProjectCount(int $projectCount): self
    {
        $this->projectCount = $projectCount;

        return $this;
    }

    public function getActiveProjectCount(): int
    {
        return $this->activeProjectCount;
    }

    public function setActiveProjectCount(int $activeProjectCount): self
    {
        $this->activeProjectCount = $activeProjectCount;

        return $this;
    }

    public function getCompletedProjectCount(): int
    {
        return $this->completedProjectCount;
    }

    public function setCompletedProjectCount(int $completedProjectCount): self
    {
        $this->completedProjectCount = $completedProjectCount;

        return $this;
    }

    public function getOrderCount(): int
    {
        return $this->orderCount;
    }

    public function setOrderCount(int $orderCount): self
    {
        $this->orderCount = $orderCount;

        return $this;
    }

    public function getPendingOrderCount(): int
    {
        return $this->pendingOrderCount;
    }

    public function setPendingOrderCount(int $pendingOrderCount): self
    {
        $this->pendingOrderCount = $pendingOrderCount;

        return $this;
    }

    public function getWonOrderCount(): int
    {
        return $this->wonOrderCount;
    }

    public function setWonOrderCount(int $wonOrderCount): self
    {
        $this->wonOrderCount = $wonOrderCount;

        return $this;
    }

    public function getSignedOrderCount(): int
    {
        return $this->signedOrderCount;
    }

    public function setSignedOrderCount(int $signedOrderCount): self
    {
        $this->signedOrderCount = $signedOrderCount;

        return $this;
    }

    public function getLostOrderCount(): int
    {
        return $this->lostOrderCount;
    }

    public function setLostOrderCount(int $lostOrderCount): self
    {
        $this->lostOrderCount = $lostOrderCount;

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

    public function getTotalRevenue(): string
    {
        return $this->totalRevenue;
    }

    public function setTotalRevenue(string $totalRevenue): self
    {
        $this->totalRevenue = $totalRevenue;

        return $this;
    }

    public function getTotalCosts(): string
    {
        return $this->totalCosts;
    }

    public function setTotalCosts(string $totalCosts): self
    {
        $this->totalCosts = $totalCosts;

        return $this;
    }

    public function getGrossMargin(): string
    {
        return $this->grossMargin;
    }

    public function setGrossMargin(string $grossMargin): self
    {
        $this->grossMargin = $grossMargin;

        return $this;
    }

    public function getMarginPercentage(): string
    {
        return $this->marginPercentage;
    }

    public function setMarginPercentage(string $marginPercentage): self
    {
        $this->marginPercentage = $marginPercentage;

        return $this;
    }

    public function getPendingRevenue(): string
    {
        return $this->pendingRevenue;
    }

    public function setPendingRevenue(string $pendingRevenue): self
    {
        $this->pendingRevenue = $pendingRevenue;

        return $this;
    }

    public function getAverageOrderValue(): string
    {
        return $this->averageOrderValue;
    }

    public function setAverageOrderValue(string $averageOrderValue): self
    {
        $this->averageOrderValue = $averageOrderValue;

        return $this;
    }

    public function getTotalSoldDays(): string
    {
        return $this->totalSoldDays;
    }

    public function setTotalSoldDays(string $totalSoldDays): self
    {
        $this->totalSoldDays = $totalSoldDays;

        return $this;
    }

    public function getTotalWorkedDays(): string
    {
        return $this->totalWorkedDays;
    }

    public function setTotalWorkedDays(string $totalWorkedDays): self
    {
        $this->totalWorkedDays = $totalWorkedDays;

        return $this;
    }

    public function getUtilizationRate(): string
    {
        return $this->utilizationRate;
    }

    public function setUtilizationRate(string $utilizationRate): self
    {
        $this->utilizationRate = $utilizationRate;

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

    /**
     * Calcule automatiquement la marge et le pourcentage.
     */
    public function calculateMargins(): self
    {
        $this->grossMargin = bcsub($this->totalRevenue, $this->totalCosts, 2);

        if (bccomp($this->totalRevenue, '0', 2) > 0) {
            $this->marginPercentage = bcmul(
                bcdiv($this->grossMargin, $this->totalRevenue, 4),
                '100',
                2,
            );
        } else {
            $this->marginPercentage = '0.00';
        }

        return $this;
    }
}
