<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'planning')]
class Planning
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Contributor::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Contributor $contributor;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Project $project;

    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $startDate;

    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $endDate;

    // Nombre d'heures planifiées par jour
    #[ORM\Column(type: 'decimal', precision: 4, scale: 2)]
    private string $dailyHours = '8.00';

    // Profil planifié pour cette période
    #[ORM\ManyToOne(targetEntity: Profile::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Profile $profile = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'planned'; // planned, confirmed, cancelled

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getProject(): Project
    {
        return $this->project;
    }
    public function setProject(Project $project): self
    {
        $this->project = $project;
        return $this;
    }

    public function getStartDate(): \DateTimeInterface
    {
        return $this->startDate;
    }
    public function setStartDate(\DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): \DateTimeInterface
    {
        return $this->endDate;
    }
    public function setEndDate(\DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getDailyHours(): string
    {
        return $this->dailyHours;
    }
    public function setDailyHours(string $dailyHours): self
    {
        $this->dailyHours = $dailyHours;
        return $this;
    }

    public function getProfile(): ?Profile
    {
        return $this->profile;
    }
    public function setProfile(?Profile $profile): self
    {
        $this->profile = $profile;
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

    public function getStatus(): string
    {
        return $this->status;
    }
    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }
    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }
    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * Calcule le nombre total d'heures planifiées
     */
    public function getTotalPlannedHours(): string
    {
        $days = $this->getNumberOfWorkingDays();
        return bcmul((string)$days, $this->dailyHours, 2);
    }

    /**
     * Calcule le nombre de jours ouvrés entre start et end
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
     * Calcule le coût projeté basé sur le CJM du contributeur
     */
    public function getProjectedCost(): string
    {
        $totalHours = $this->getTotalPlannedHours();
        $cjm = $this->contributor->getCjm();

        // Convertir heures en jours (8h = 1j) puis multiplier par CJM
        $days = bcdiv($totalHours, '8', 4);
        return bcmul($days, $cjm, 2);
    }

    /**
     * Vérifie si cette planification est active à une date donnée
     */
    public function isActiveAt(\DateTimeInterface $date): bool
    {
        return $date >= $this->startDate && $date <= $this->endDate;
    }
}
