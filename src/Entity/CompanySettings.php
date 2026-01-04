<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interface\CompanyOwnedInterface;
use App\Repository\CompanySettingsRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Paramètres globaux de l'entreprise
 * Singleton : une seule instance en base de données.
 */
#[ORM\Entity(repositoryClass: CompanySettingsRepository::class)]
#[ORM\Table(name: 'company_settings')]
#[ORM\Index(name: 'idx_companysettings_company', columns: ['company_id'])]
class CompanySettings implements CompanyOwnedInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public private(set) ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private Company $company;

    /**
     * Coefficient de coûts de structure
     * Généralement entre 1.3 et 1.4
     * Représente les coûts fixes de l'entreprise (locaux, équipements, etc.).
     */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 4)]
    public string $structureCostCoefficient = '1.35' {
        get => $this->structureCostCoefficient;
        set {
            $this->structureCostCoefficient = $value;
        }
    }

    /**
     * Coefficient de charges patronales
     * Généralement autour de 1.45
     * Représente les charges sociales patronales.
     */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 4)]
    public string $employerChargesCoefficient = '1.45' {
        get => $this->employerChargesCoefficient;
        set {
            $this->employerChargesCoefficient = $value;
        }
    }

    /**
     * Nombre de jours de congés payés par an
     * Défaut : 25 jours (légal en France).
     */
    #[ORM\Column]
    public int $annualPaidLeaveDays = 25 {
        get => $this->annualPaidLeaveDays;
        set {
            $this->annualPaidLeaveDays = $value;
        }
    }

    /**
     * Nombre de jours de RTT par an
     * Défaut : 10 jours.
     */
    #[ORM\Column]
    public int $annualRttDays = 10 {
        get => $this->annualRttDays;
        set {
            $this->annualRttDays = $value;
        }
    }

    /**
     * Dates de mise à jour.
     */
    #[ORM\Column(type: 'datetime')]
    private DateTimeInterface $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new DateTime();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new DateTime();
    }

    /**
     * Calcule le coefficient de charge global
     * Coefficient global = coûts de structure × charges patronales.
     */
    public function getGlobalChargeCoefficient(): string
    {
        return bcmul($this->structureCostCoefficient, $this->employerChargesCoefficient, 4);
    }

    public function getUpdatedAt(): DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
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
     * With PHP 8.4 public private(set), prefer direct access: $settings->id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $settings->structureCostCoefficient.
     */
    public function getStructureCostCoefficient(): string
    {
        return $this->structureCostCoefficient;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $settings->structureCostCoefficient = $value.
     */
    public function setStructureCostCoefficient(string $value): self
    {
        $this->structureCostCoefficient = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $settings->employerChargesCoefficient.
     */
    public function getEmployerChargesCoefficient(): string
    {
        return $this->employerChargesCoefficient;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $settings->employerChargesCoefficient = $value.
     */
    public function setEmployerChargesCoefficient(string $value): self
    {
        $this->employerChargesCoefficient = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $settings->annualPaidLeaveDays.
     */
    public function getAnnualPaidLeaveDays(): int
    {
        return $this->annualPaidLeaveDays;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $settings->annualPaidLeaveDays = $value.
     */
    public function setAnnualPaidLeaveDays(int $value): self
    {
        $this->annualPaidLeaveDays = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $settings->annualRttDays.
     */
    public function getAnnualRttDays(): int
    {
        return $this->annualRttDays;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $settings->annualRttDays = $value.
     */
    public function setAnnualRttDays(int $value): self
    {
        $this->annualRttDays = $value;

        return $this;
    }
}
