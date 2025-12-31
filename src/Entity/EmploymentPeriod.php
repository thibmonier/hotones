<?php

namespace App\Entity;

use App\Entity\Interface\CompanyOwnedInterface;
use App\Repository\EmploymentPeriodRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EmploymentPeriodRepository::class)]
#[ORM\Table(name: 'employment_periods')]
#[ORM\Index(name: 'idx_employment_period_company', columns: ['company_id'])]
class EmploymentPeriod implements CompanyOwnedInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public private(set) ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    public Company $company {
        get => $this->company;
        set {
            $this->company = $value;
        }
    }

    #[ORM\ManyToOne(targetEntity: Contributor::class, inversedBy: 'employmentPeriods')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public ?Contributor $contributor = null {
        get => $this->contributor;
        set {
            $this->contributor = $value;
        }
    }

    // Salaire mensuel brut en EUR (ou net selon votre besoin)
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    public ?string $salary = null {
        get => $this->salary;
        set {
            $this->salary = $value !== null ? (string) $value : null;
        }
    }

    // Coût Journalier Moyen sur la période
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    public ?string $cjm = null {
        get => $this->cjm;
        set {
            $this->cjm = $value !== null ? (string) $value : null;
        }
    }

    // Taux Journalier Moyen (facturable client)
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    public ?string $tjm = null {
        get => $this->tjm;
        set {
            $this->tjm = $value !== null ? (string) $value : null;
        }
    }

    // Temps de travail hebdomadaire (par défaut 35h, peut aller jusqu'à 39h)
    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, options: ['default' => 35.00])]
    public string $weeklyHours = '35.00' {
        get => $this->weeklyHours;
        set {
            $this->weeklyHours = $value !== null ? (string) $value : '35.00';
        }
    }

    // Pourcentage temps de travail (100%, 90%, 80% pour temps partiel)
    #[ORM\Column(name: 'work_time_percentage', type: 'decimal', precision: 5, scale: 2, options: ['default' => 100.00])]
    public string $workTimePercentage = '100.00' {
        get => $this->workTimePercentage;
        set {
            $this->workTimePercentage = $value !== null ? (string) $value : '100.00';
        }
    }

    #[ORM\Column(type: 'date')]
    public DateTimeInterface $startDate {
        get => $this->startDate;
        set {
            $this->startDate = $value;
        }
    }

    #[ORM\Column(type: 'date', nullable: true)]
    public ?DateTimeInterface $endDate = null {
        get => $this->endDate;
        set {
            $this->endDate = $value;
        }
    }

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $notes = null {
        get => $this->notes;
        set {
            $this->notes = $value;
        }
    }

    #[ORM\ManyToMany(targetEntity: Profile::class)]
    #[ORM\JoinTable(name: 'employment_period_profiles')]
    private Collection $profiles;

    public function __construct()
    {
        $this->profiles = new ArrayCollection();
        // Initialiser pour éviter les erreurs d'accès avant initialisation dans les vues
        $this->startDate = new DateTime();
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.5 property hooks, prefer direct access: $period->startDate.
     */
    public function getStartDate(): DateTimeInterface
    {
        return $this->startDate;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.5 property hooks, prefer direct access: $period->startDate = $date.
     */
    public function setStartDate(DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.5 property hooks, prefer direct access: $period->endDate.
     */
    public function getEndDate(): ?DateTimeInterface
    {
        return $this->endDate;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.5 property hooks, prefer direct access: $period->endDate = $date.
     */
    public function setEndDate(?DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * @return Collection<int, Profile>
     */
    public function getProfiles(): Collection
    {
        return $this->profiles;
    }

    public function addProfile(Profile $profile): self
    {
        if (!$this->profiles->contains($profile)) {
            $this->profiles->add($profile);
        }

        return $this;
    }

    public function removeProfile(Profile $profile): self
    {
        $this->profiles->removeElement($profile);

        return $this;
    }

    /**
     * Calcule le CJM effectif en prenant en compte le temps partiel.
     */
    public function getEffectiveDailyCost(): string
    {
        $baseCost     = floatval($this->cjm);
        $workingRatio = floatval($this->workTimePercentage) / 100;

        return number_format($baseCost * $workingRatio, 2, '.', '');
    }

    /**
     * Calcule le nombre de jours travaillés par semaine.
     */
    public function getWorkingDaysPerWeek(): float
    {
        $standardHourPerDay = 7; // 7h par jour standard
        $actualHours        = floatval($this->weeklyHours);
        $workingRatio       = floatval($this->workTimePercentage) / 100;

        return ($actualHours * $workingRatio) / $standardHourPerDay;
    }

    /**
     * Calcule le nombre d'heures par jour basé sur le temps de travail contractuel.
     * Formule: heures hebdomadaires / 5 jours (standard du lundi au vendredi).
     *
     * Exemples:
     * - 35h/semaine = 7h/jour
     * - 32h/semaine (4j) = 8h/jour (si configuré avec weeklyHours=32)
     * - 28h/semaine (4j) = 7h/jour (si configuré avec weeklyHours=28)
     * - 39h/semaine = 7.8h/jour
     */
    public function getHoursPerDay(): float
    {
        $actualHours    = floatval($this->weeklyHours);
        $workingRatio   = floatval($this->workTimePercentage) / 100;
        $effectiveHours = $actualHours * $workingRatio;

        // Par défaut, on divise par 5 jours (lundi-vendredi)
        // Pour un temps partiel sur 4 jours, ajuster weeklyHours en conséquence
        return $effectiveHours / 5;
    }

    /**
     * Vérifie si la période est active à une date donnée.
     */
    public function isActiveAt(DateTimeInterface $date): bool
    {
        if ($date < $this->startDate) {
            return false;
        }

        return $this->endDate === null || $date <= $this->endDate;
    }

    /**
     * Calcule automatiquement le CJM à partir du salaire mensuel brut.
     *
     * Formule : (Salaire brut + Charges patronales) / Nombre de jours travaillés par mois
     *
     * @param float|null $chargesRate Taux de charges patronales (par défaut 0.45 = 45%)
     *
     * @return float|null Le CJM calculé ou null si le salaire n'est pas défini
     */
    public function calculateCjmFromSalary(?float $chargesRate = 0.45): ?float
    {
        if (!$this->salary) {
            return null;
        }

        $salaryFloat        = floatval($this->salary);
        $weeklyHours        = floatval($this->weeklyHours);
        $workTimePercentage = floatval($this->workTimePercentage);

        // Charges sociales patronales (par défaut 45%)
        $totalCost = $salaryFloat * (1 + $chargesRate);

        // Nombre de jours travaillés par semaine (basé sur 7h/jour)
        $hoursPerDay = 7;
        $daysPerWeek = ($weeklyHours * ($workTimePercentage / 100)) / $hoursPerDay;

        // Nombre de jours travaillés par mois (en moyenne : 4.33 semaines par mois)
        $daysPerMonth = $daysPerWeek * 4.33;

        if ($daysPerMonth <= 0) {
            return null;
        }

        // CJM = Coût total / Jours travaillés par mois
        return round($totalCost / $daysPerMonth, 2);
    }

    /**
     * Applique le calcul automatique du CJM si un salaire est défini et que le CJM n'est pas renseigné.
     */
    public function autoCalculateCjm(): self
    {
        if ($this->salary && !$this->cjm) {
            $calculatedCjm = $this->calculateCjmFromSalary();
            if ($calculatedCjm !== null) {
                $this->cjm = (string) $calculatedCjm;
            }
        }

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
}
