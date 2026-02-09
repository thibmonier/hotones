<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interface\CompanyOwnedInterface;
use App\Repository\VacationRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: VacationRepository::class)]
#[ORM\Table(name: 'vacations')]
#[ORM\Index(name: 'idx_vacation_company', columns: ['company_id'])]
class Vacation implements CompanyOwnedInterface
{
    public const TYPE_PAID_LEAVE          = 'conges_payes';
    public const TYPE_COMPENSATORY_REST   = 'repos_compensateur';
    public const TYPE_EXCEPTIONAL_ABSENCE = 'absence_exceptionnelle';
    public const TYPE_SICK_LEAVE          = 'arret_maladie';
    public const TYPE_TRAINING            = 'formation';
    public const TYPE_OTHER               = 'autre';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public private(set) ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private Company $company;

    #[ORM\ManyToOne(targetEntity: Contributor::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Contributor $contributor;

    #[ORM\Column(type: 'date')]
    public DateTimeInterface $startDate {
        get => $this->startDate;
        set {
            $this->startDate = $value;
        }
    }

    #[ORM\Column(type: 'date')]
    public DateTimeInterface $endDate {
        get => $this->endDate;
        set {
            $this->endDate = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 50)]
    public string $type = self::TYPE_PAID_LEAVE {
        get => $this->type;
        set {
            $this->type = $value;
        }
    }

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $reason = null {
        get => $this->reason;
        set {
            $this->reason = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 20)]
    public string $status = 'pending' {
        get => $this->status;
        set {
            $this->status = $value;
        }
    }

    // Nombre d'heures par jour d'absence (par défaut 8h = journée complète)
    #[ORM\Column(type: 'decimal', precision: 4, scale: 2)]
    public string $dailyHours = '8.00' {
        get => $this->dailyHours;
        set {
            $this->dailyHours = $value;
        }
    }

    #[ORM\Column(type: 'datetime')]
    public DateTimeInterface $createdAt {
        get => $this->createdAt;
        set {
            $this->createdAt = $value;
        }
    }

    #[ORM\Column(type: 'datetime', nullable: true)]
    public ?DateTimeInterface $approvedAt = null {
        get => $this->approvedAt;
        set {
            $this->approvedAt = $value;
        }
    }

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $approvedBy = null;

    public function __construct()
    {
        $this->createdAt = new DateTime();
    }

    public function getContributor(): Contributor
    {
        return $this->contributor;
    }

    public function setContributor(Contributor $contributor): self
    {
        $this->contributor = $contributor;

        return $this;
    }

    public function getApprovedBy(): ?User
    {
        return $this->approvedBy;
    }

    public function setApprovedBy(?User $approvedBy): self
    {
        $this->approvedBy = $approvedBy;

        return $this;
    }

    /**
     * Calcule le nombre total d'heures d'absence.
     */
    public function getTotalHours(): string
    {
        $days = $this->getNumberOfDays();

        return bcmul((string) $days, $this->dailyHours, 2);
    }

    /**
     * Calcule le nombre de jours d'absence (incluant weekends).
     */
    public function getNumberOfDays(): int
    {
        $start    = clone $this->startDate;
        $end      = clone $this->endDate;
        $interval = $start->diff($end);

        return $interval->days + 1; // +1 pour inclure le dernier jour
    }

    /**
     * Calcule le nombre de jours ouvrés d'absence.
     */
    public function getNumberOfWorkingDays(): int
    {
        $start = clone $this->startDate;
        $end   = clone $this->endDate;
        $days  = 0;

        while ($start <= $end) {
            // Exclure les weekends (samedi=6, dimanche=0)
            if (!in_array($start->format('w'), ['0', '6'], true)) {
                ++$days;
            }
            $start->modify('+1 day');
        }

        return $days;
    }

    /**
     * Vérifie si une date donnée est en conflit avec cette absence.
     */
    public function isConflictWith(DateTimeInterface $date): bool
    {
        return $date >= $this->startDate && $date <= $this->endDate;
    }

    /**
     * Retourne le libellé du type d'absence.
     */
    public function getTypeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_PAID_LEAVE          => 'Congés payés',
            self::TYPE_COMPENSATORY_REST   => 'Repos compensateur',
            self::TYPE_EXCEPTIONAL_ABSENCE => 'Absence exceptionnelle',
            self::TYPE_SICK_LEAVE          => 'Arrêt maladie',
            self::TYPE_TRAINING            => 'Formation',
            self::TYPE_OTHER               => 'Autre',
            default                        => 'Non défini',
        };
    }

    /**
     * Retourne tous les types d'absence disponibles.
     */
    public static function getAvailableTypes(): array
    {
        return [
            self::TYPE_PAID_LEAVE          => 'Congés payés',
            self::TYPE_COMPENSATORY_REST   => 'Repos compensateur',
            self::TYPE_EXCEPTIONAL_ABSENCE => 'Absence exceptionnelle',
            self::TYPE_SICK_LEAVE          => 'Arrêt maladie',
            self::TYPE_TRAINING            => 'Formation',
            self::TYPE_OTHER               => 'Autre',
        ];
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
     * With PHP 8.4 public private(set), prefer direct access: $vacation->id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $vacation->startDate.
     */
    public function getStartDate(): DateTimeInterface
    {
        return $this->startDate;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $vacation->startDate = $value.
     */
    public function setStartDate(DateTimeInterface $value): self
    {
        $this->startDate = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $vacation->endDate.
     */
    public function getEndDate(): DateTimeInterface
    {
        return $this->endDate;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $vacation->endDate = $value.
     */
    public function setEndDate(DateTimeInterface $value): self
    {
        $this->endDate = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $vacation->type.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $vacation->type = $value.
     */
    public function setType(string $value): self
    {
        $this->type = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $vacation->reason.
     */
    public function getReason(): ?string
    {
        return $this->reason;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $vacation->reason = $value.
     */
    public function setReason(?string $value): self
    {
        $this->reason = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $vacation->status.
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $vacation->status = $value.
     */
    public function setStatus(string $value): self
    {
        $this->status = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $vacation->dailyHours.
     */
    public function getDailyHours(): string
    {
        return $this->dailyHours;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $vacation->dailyHours = $value.
     */
    public function setDailyHours(string $value): self
    {
        $this->dailyHours = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $vacation->createdAt.
     */
    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $vacation->createdAt = $value.
     */
    public function setCreatedAt(DateTimeInterface $value): self
    {
        $this->createdAt = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $vacation->approvedAt.
     */
    public function getApprovedAt(): ?DateTimeInterface
    {
        return $this->approvedAt;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $vacation->approvedAt = $value.
     */
    public function setApprovedAt(?DateTimeInterface $value): self
    {
        $this->approvedAt = $value;

        return $this;
    }
}
