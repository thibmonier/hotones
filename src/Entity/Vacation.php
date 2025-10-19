<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'vacations')]
class Vacation
{
    public const TYPE_PAID_LEAVE = 'conges_payes';
    public const TYPE_COMPENSATORY_REST = 'repos_compensateur';
    public const TYPE_EXCEPTIONAL_ABSENCE = 'absence_exceptionnelle';
    public const TYPE_SICK_LEAVE = 'arret_maladie';
    public const TYPE_TRAINING = 'formation';
    public const TYPE_OTHER = 'autre';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Contributor::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Contributor $contributor;

    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $startDate;

    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $endDate;

    #[ORM\Column(type: 'string', length: 50)]
    private string $type = self::TYPE_PAID_LEAVE;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $reason = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'pending'; // pending, approved, rejected

    // Nombre d'heures par jour d'absence (par défaut 8h = journée complète)
    #[ORM\Column(type: 'decimal', precision: 4, scale: 2)]
    private string $dailyHours = '8.00';

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $approvedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $approvedBy = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getContributor(): Contributor { return $this->contributor; }
    public function setContributor(Contributor $contributor): self { $this->contributor = $contributor; return $this; }

    public function getStartDate(): \DateTimeInterface { return $this->startDate; }
    public function setStartDate(\DateTimeInterface $startDate): self { $this->startDate = $startDate; return $this; }

    public function getEndDate(): \DateTimeInterface { return $this->endDate; }
    public function setEndDate(\DateTimeInterface $endDate): self { $this->endDate = $endDate; return $this; }

    public function getType(): string { return $this->type; }
    public function setType(string $type): self { $this->type = $type; return $this; }

    public function getReason(): ?string { return $this->reason; }
    public function setReason(?string $reason): self { $this->reason = $reason; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }

    public function getDailyHours(): string { return $this->dailyHours; }
    public function setDailyHours(string $dailyHours): self { $this->dailyHours = $dailyHours; return $this; }

    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $createdAt): self { $this->createdAt = $createdAt; return $this; }

    public function getApprovedAt(): ?\DateTimeInterface { return $this->approvedAt; }
    public function setApprovedAt(?\DateTimeInterface $approvedAt): self { $this->approvedAt = $approvedAt; return $this; }

    public function getApprovedBy(): ?User { return $this->approvedBy; }
    public function setApprovedBy(?User $approvedBy): self { $this->approvedBy = $approvedBy; return $this; }

    /**
     * Calcule le nombre total d'heures d'absence
     */
    public function getTotalHours(): string
    {
        $days = $this->getNumberOfDays();
        return bcmul((string)$days, $this->dailyHours, 2);
    }

    /**
     * Calcule le nombre de jours d'absence (incluant weekends)
     */
    public function getNumberOfDays(): int
    {
        $start = clone $this->startDate;
        $end = clone $this->endDate;
        $interval = $start->diff($end);
        
        return $interval->days + 1; // +1 pour inclure le dernier jour
    }

    /**
     * Calcule le nombre de jours ouvrés d'absence
     */
    public function getNumberOfWorkingDays(): int
    {
        $start = clone $this->startDate;
        $end = clone $this->endDate;
        $days = 0;

        while ($start <= $end) {
            // Exclure les weekends (samedi=6, dimanche=0)
            if (!in_array($start->format('w'), ['0', '6'])) {
                $days++;
            }
            $start->modify('+1 day');
        }

        return $days;
    }

    /**
     * Vérifie si une date donnée est en conflit avec cette absence
     */
    public function isConflictWith(\DateTimeInterface $date): bool
    {
        return $date >= $this->startDate && $date <= $this->endDate;
    }

    /**
     * Retourne le libellé du type d'absence
     */
    public function getTypeLabel(): string
    {
        return match($this->type) {
            self::TYPE_PAID_LEAVE => 'Congés payés',
            self::TYPE_COMPENSATORY_REST => 'Repos compensateur',
            self::TYPE_EXCEPTIONAL_ABSENCE => 'Absence exceptionnelle',
            self::TYPE_SICK_LEAVE => 'Arrêt maladie',
            self::TYPE_TRAINING => 'Formation',
            self::TYPE_OTHER => 'Autre',
            default => 'Non défini'
        };
    }

    /**
     * Retourne tous les types d'absence disponibles
     */
    public static function getAvailableTypes(): array
    {
        return [
            self::TYPE_PAID_LEAVE => 'Congés payés',
            self::TYPE_COMPENSATORY_REST => 'Repos compensateur',
            self::TYPE_EXCEPTIONAL_ABSENCE => 'Absence exceptionnelle',
            self::TYPE_SICK_LEAVE => 'Arrêt maladie',
            self::TYPE_TRAINING => 'Formation',
            self::TYPE_OTHER => 'Autre',
        ];
    }
}