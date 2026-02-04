<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interface\CompanyOwnedInterface;
use App\Repository\EmployeeLevelRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Niveaux d'emploi (1-12) définissant la progression de carrière.
 *
 * Structure des niveaux:
 * - 1, 2, 3: Junior
 * - 4, 5, 6: Expérimenté (Confirmé)
 * - 7, 8, 9: Senior
 * - 10, 11, 12: Lead / Expert
 */
#[ORM\Entity(repositoryClass: EmployeeLevelRepository::class)]
#[ORM\Table(name: 'employee_levels')]
#[ORM\Index(name: 'idx_employee_level_company', columns: ['company_id'])]
#[ORM\UniqueConstraint(name: 'unique_level_per_company', columns: ['company_id', 'level'])]
class EmployeeLevel implements CompanyOwnedInterface
{
    public const CATEGORY_JUNIOR      = 'junior';
    public const CATEGORY_EXPERIENCED = 'experienced';
    public const CATEGORY_SENIOR      = 'senior';
    public const CATEGORY_LEAD        = 'lead';

    public const CATEGORIES = [
        self::CATEGORY_JUNIOR      => 'Junior',
        self::CATEGORY_EXPERIENCED => 'Expérimenté',
        self::CATEGORY_SENIOR      => 'Senior',
        self::CATEGORY_LEAD        => 'Lead / Expert',
    ];

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

    /**
     * Niveau de 1 à 12.
     */
    #[ORM\Column(type: 'smallint')]
    #[Assert\Range(min: 1, max: 12, notInRangeMessage: 'Le niveau doit être entre {{ min }} et {{ max }}')]
    public int $level {
        get => $this->level;
        set {
            $this->level = $value;
        }
    }

    /**
     * Nom descriptif du niveau (ex: "Junior 1", "Senior 2", "Lead Developer").
     */
    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public string $name {
        get => $this->name;
        set {
            $this->name = $value;
        }
    }

    /**
     * Description des compétences/responsabilités attendues à ce niveau.
     */
    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $description = null {
        get => $this->description;
        set {
            $this->description = $value;
        }
    }

    /**
     * Salaire annuel brut minimum (en EUR).
     */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    public ?string $salaryMin = null {
        get => $this->salaryMin;
        set {
            $this->salaryMin = $value;
        }
    }

    /**
     * Salaire annuel brut maximum (en EUR).
     */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    public ?string $salaryMax = null {
        get => $this->salaryMax;
        set {
            $this->salaryMax = $value;
        }
    }

    /**
     * Salaire annuel brut cible/médian (en EUR).
     */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    public ?string $salaryTarget = null {
        get => $this->salaryTarget;
        set {
            $this->salaryTarget = $value;
        }
    }

    /**
     * TJM (Taux Journalier Moyen) cible pour ce niveau.
     */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    public ?string $targetTjm = null {
        get => $this->targetTjm;
        set {
            $this->targetTjm = $value;
        }
    }

    /**
     * Couleur pour l'affichage (format hex #RRGGBB).
     */
    #[ORM\Column(type: 'string', length: 7, nullable: true)]
    public ?string $color = null {
        get => $this->color;
        set {
            $this->color = $value;
        }
    }

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    public bool $active = true {
        get => $this->active;
        set {
            $this->active = $value;
        }
    }

    /**
     * Retourne la catégorie du niveau (junior, experienced, senior, lead).
     */
    public function getCategory(): string
    {
        return match (true) {
            $this->level <= 3 => self::CATEGORY_JUNIOR,
            $this->level <= 6 => self::CATEGORY_EXPERIENCED,
            $this->level <= 9 => self::CATEGORY_SENIOR,
            default           => self::CATEGORY_LEAD,
        };
    }

    /**
     * Retourne le label de la catégorie.
     */
    public function getCategoryLabel(): string
    {
        return self::CATEGORIES[$this->getCategory()];
    }

    /**
     * Retourne la fourchette salariale formatée.
     */
    public function getSalaryRange(): string
    {
        if ($this->salaryMin === null && $this->salaryMax === null) {
            return 'Non définie';
        }

        $min = $this->salaryMin ? number_format((float) $this->salaryMin, 0, ',', ' ').' €' : '?';
        $max = $this->salaryMax ? number_format((float) $this->salaryMax, 0, ',', ' ').' €' : '?';

        return "{$min} - {$max}";
    }

    /**
     * Vérifie si un salaire donné est dans la fourchette de ce niveau.
     */
    public function isSalaryInRange(float $annualSalary): ?bool
    {
        if ($this->salaryMin === null && $this->salaryMax === null) {
            return null; // Fourchette non définie
        }

        $min = $this->salaryMin !== null ? (float) $this->salaryMin : 0;
        $max = $this->salaryMax !== null ? (float) $this->salaryMax : PHP_FLOAT_MAX;

        return $annualSalary >= $min && $annualSalary <= $max;
    }

    /**
     * Retourne l'affichage formaté du niveau (ex: "Niveau 5 - Senior").
     */
    public function getFullLabel(): string
    {
        return sprintf('Niveau %d - %s', $this->level, $this->name);
    }

    public function __toString(): string
    {
        return $this->getFullLabel();
    }

    // CompanyOwnedInterface implementation
    public function getCompany(): Company
    {
        return $this->company;
    }

    public function setCompany(Company $company): self
    {
        $this->company = $company;

        return $this;
    }

    // Compatibility methods

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): self
    {
        $this->level = $level;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getSalaryMin(): ?string
    {
        return $this->salaryMin;
    }

    public function setSalaryMin(?string $salaryMin): self
    {
        $this->salaryMin = $salaryMin;

        return $this;
    }

    public function getSalaryMax(): ?string
    {
        return $this->salaryMax;
    }

    public function setSalaryMax(?string $salaryMax): self
    {
        $this->salaryMax = $salaryMax;

        return $this;
    }

    public function getSalaryTarget(): ?string
    {
        return $this->salaryTarget;
    }

    public function setSalaryTarget(?string $salaryTarget): self
    {
        $this->salaryTarget = $salaryTarget;

        return $this;
    }

    public function getTargetTjm(): ?string
    {
        return $this->targetTjm;
    }

    public function setTargetTjm(?string $targetTjm): self
    {
        $this->targetTjm = $targetTjm;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }
}
