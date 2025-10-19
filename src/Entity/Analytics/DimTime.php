<?php

declare(strict_types=1);

namespace App\Entity\Analytics;

use Doctrine\ORM\Mapping as ORM;

/**
 * Table de dimension temporelle pour le modèle en étoile
 * Permet l'analyse par année, trimestre, mois
 */
#[ORM\Entity]
#[ORM\Table(name: "dim_time")]
class DimTime
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(name: "date_value", type: "date", unique: true)]
    private \DateTimeInterface $date;

    #[ORM\Column(name: "year_value", type: "integer")]
    private int $year;

    #[ORM\Column(name: "quarter_value", type: "integer")]
    private int $quarter;

    #[ORM\Column(name: "month_value", type: "integer")]
    private int $month;

    #[ORM\Column(name: "period_year_month", type: "string", length: 20)]
    private string $yearMonth; // Format: "2025-01"

    #[ORM\Column(name: "period_year_quarter", type: "string", length: 20)]
    private string $yearQuarter; // Format: "2025-Q1"

    #[ORM\Column(type: "string", length: 50)]
    private string $monthName; // "Janvier 2025"

    #[ORM\Column(type: "string", length: 50)]
    private string $quarterName; // "Q1 2025"

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;
        
        // Auto-calcul des autres champs
        $this->year = (int) $date->format('Y');
        $this->month = (int) $date->format('n');
        $this->quarter = (int) ceil($this->month / 3);
        $this->yearMonth = $date->format('Y-m');
        $this->yearQuarter = $this->year . '-Q' . $this->quarter;
        
        $monthNames = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];
        
        $this->monthName = $monthNames[$this->month] . ' ' . $this->year;
        $this->quarterName = 'Q' . $this->quarter . ' ' . $this->year;
        
        return $this;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function getQuarter(): int
    {
        return $this->quarter;
    }

    public function getMonth(): int
    {
        return $this->month;
    }

    public function getYearMonth(): string
    {
        return $this->yearMonth;
    }

    public function getYearQuarter(): string
    {
        return $this->yearQuarter;
    }

    public function getMonthName(): string
    {
        return $this->monthName;
    }

    public function getQuarterName(): string
    {
        return $this->quarterName;
    }
}