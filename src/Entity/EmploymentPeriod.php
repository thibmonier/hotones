<?php

namespace App\Entity;

use App\Repository\EmploymentPeriodRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EmploymentPeriodRepository::class)]
#[ORM\Table(name: 'employment_periods')]
class EmploymentPeriod
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Contributor::class, inversedBy: 'employmentPeriods')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Contributor $contributor = null;

    // Salaire mensuel brut en EUR (ou net selon votre besoin)
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $salary = null;

    // Coût Journalier Moyen sur la période
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $cjm = null;

    // Taux Journalier Moyen (facturable client)
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $tjm = null;

    // Temps de travail hebdomadaire (par défaut 35h, peut aller jusqu'à 39h)
    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, options: ['default' => 35.00])]
    private string $weeklyHours = '35.00';

    // Pourcentage temps de travail (100%, 90%, 80% pour temps partiel)
    #[ORM\Column(name: 'work_time_percentage', type: 'decimal', precision: 5, scale: 2, options: ['default' => 100.00])]
    private string $workTimePercentage = '100.00';

    #[ORM\Column(type: 'date')]
    private DateTimeInterface $startDate;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?DateTimeInterface $endDate = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\ManyToMany(targetEntity: Profile::class)]
    #[ORM\JoinTable(name: 'employment_period_profiles')]
    private Collection $profiles;

    public function __construct()
    {
        $this->profiles = new ArrayCollection();
        // Initialiser pour éviter les erreurs d'accès avant initialisation dans les vues
        $this->startDate = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContributor(): ?Contributor
    {
        return $this->contributor;
    }

    public function setContributor(Contributor $contributor): self
    {
        $this->contributor = $contributor;

        return $this;
    }

    public function getSalary(): ?string
    {
        return $this->salary;
    }

    public function setSalary(?float $salary): self
    {
        $this->salary = $salary !== null ? (string) $salary : null;

        return $this;
    }

    public function getCjm(): ?string
    {
        return $this->cjm;
    }

    public function setCjm(?float $cjm): self
    {
        $this->cjm = $cjm !== null ? (string) $cjm : null;

        return $this;
    }

    public function getTjm(): ?string
    {
        return $this->tjm;
    }

    public function setTjm(?float $tjm): self
    {
        $this->tjm = $tjm !== null ? (string) $tjm : null;

        return $this;
    }

    public function getWeeklyHours(): string
    {
        return $this->weeklyHours;
    }

    public function setWeeklyHours(?float $weeklyHours): self
    {
        $this->weeklyHours = $weeklyHours !== null ? (string) $weeklyHours : '35.00';

        return $this;
    }

    public function getStartDate(): DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getWorkTimePercentage(): string
    {
        return $this->workTimePercentage;
    }

    public function setWorkTimePercentage(?float $workTimePercentage): self
    {
        $this->workTimePercentage = $workTimePercentage !== null ? (string) $workTimePercentage : '100.00';

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
                $this->setCjm($calculatedCjm);
            }
        }

        return $this;
    }
}
