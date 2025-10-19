<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'employment_periods')]
class EmploymentPeriod
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Contributor::class, inversedBy: 'employmentPeriods')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Contributor $contributor;

    // Salaire mensuel brut en EUR (ou net selon votre besoin)
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $salary;

    // Coût Journalier Moyen sur la période
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $cjm;

    // Temps de travail hebdomadaire (par défaut 35h, peut aller jusqu'à 39h)
    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, options: ['default' => 35.00])]
    private string $weeklyHours = '35.00';

    // Pourcentage temps de travail (100%, 90%, 80% pour temps partiel)
    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, options: ['default' => 100.00])]
    private string $workingTimePercentage = '100.00';

    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $startDate;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    public function getId(): ?int { return $this->id; }

    public function getContributor(): Contributor { return $this->contributor; }
    public function setContributor(Contributor $contributor): self { $this->contributor = $contributor; return $this; }

    public function getSalary(): string { return $this->salary; }
    public function setSalary(string $salary): self { $this->salary = $salary; return $this; }

    public function getCjm(): string { return $this->cjm; }
    public function setCjm(string $cjm): self { $this->cjm = $cjm; return $this; }

    public function getWeeklyHours(): string { return $this->weeklyHours; }
    public function setWeeklyHours(string $weeklyHours): self { $this->weeklyHours = $weeklyHours; return $this; }

    public function getStartDate(): \DateTimeInterface { return $this->startDate; }
    public function setStartDate(\DateTimeInterface $startDate): self { $this->startDate = $startDate; return $this; }

    public function getEndDate(): ?\DateTimeInterface { return $this->endDate; }
    public function setEndDate(?\DateTimeInterface $endDate): self { $this->endDate = $endDate; return $this; }

    public function getWorkingTimePercentage(): string { return $this->workingTimePercentage; }
    public function setWorkingTimePercentage(string $workingTimePercentage): self { $this->workingTimePercentage = $workingTimePercentage; return $this; }

    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $notes): self { $this->notes = $notes; return $this; }

    /**
     * Calcule le CJM effectif en prenant en compte le temps partiel
     */
    public function getEffectiveDailyCost(): string
    {
        $baseCost = floatval($this->cjm);
        $workingRatio = floatval($this->workingTimePercentage) / 100;
        return number_format($baseCost * $workingRatio, 2, '.', '');
    }

    /**
     * Calcule le nombre de jours travaillés par semaine
     */
    public function getWorkingDaysPerWeek(): float
    {
        $standardHourPerDay = 7; // 7h par jour standard
        $actualHours = floatval($this->weeklyHours);
        $workingRatio = floatval($this->workingTimePercentage) / 100;
        
        return ($actualHours * $workingRatio) / $standardHourPerDay;
    }

    /**
     * Vérifie si la période est active à une date donnée
     */
    public function isActiveAt(\DateTimeInterface $date): bool
    {
        if ($date < $this->startDate) {
            return false;
        }
        
        return $this->endDate === null || $date <= $this->endDate;
    }
}
