<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CompanySettingsRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * Paramètres globaux de l'entreprise
 * Singleton : une seule instance en base de données.
 */
#[ORM\Entity(repositoryClass: CompanySettingsRepository::class)]
#[ORM\Table(name: 'company_settings')]
class CompanySettings
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Coefficient de coûts de structure
     * Généralement entre 1.3 et 1.4
     * Représente les coûts fixes de l'entreprise (locaux, équipements, etc.).
     */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 4)]
    private string $structureCostCoefficient = '1.35';

    /**
     * Coefficient de charges patronales
     * Généralement autour de 1.45
     * Représente les charges sociales patronales.
     */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 4)]
    private string $employerChargesCoefficient = '1.45';

    /**
     * Nombre de jours de congés payés par an
     * Défaut : 25 jours (légal en France).
     */
    #[ORM\Column]
    private int $annualPaidLeaveDays = 25;

    /**
     * Nombre de jours de RTT par an
     * Défaut : 10 jours.
     */
    #[ORM\Column]
    private int $annualRttDays = 10;

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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStructureCostCoefficient(): string
    {
        return $this->structureCostCoefficient;
    }

    public function setStructureCostCoefficient(string $structureCostCoefficient): self
    {
        $this->structureCostCoefficient = $structureCostCoefficient;

        return $this;
    }

    public function getEmployerChargesCoefficient(): string
    {
        return $this->employerChargesCoefficient;
    }

    public function setEmployerChargesCoefficient(string $employerChargesCoefficient): self
    {
        $this->employerChargesCoefficient = $employerChargesCoefficient;

        return $this;
    }

    /**
     * Calcule le coefficient de charge global
     * Coefficient global = coûts de structure × charges patronales.
     */
    public function getGlobalChargeCoefficient(): string
    {
        return bcmul($this->structureCostCoefficient, $this->employerChargesCoefficient, 4);
    }

    public function getAnnualPaidLeaveDays(): int
    {
        return $this->annualPaidLeaveDays;
    }

    public function setAnnualPaidLeaveDays(int $annualPaidLeaveDays): self
    {
        $this->annualPaidLeaveDays = $annualPaidLeaveDays;

        return $this;
    }

    public function getAnnualRttDays(): int
    {
        return $this->annualRttDays;
    }

    public function setAnnualRttDays(int $annualRttDays): self
    {
        $this->annualRttDays = $annualRttDays;

        return $this;
    }

    public function getUpdatedAt(): DateTimeInterface
    {
        return $this->updatedAt;
    }
}
