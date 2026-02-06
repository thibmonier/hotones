<?php

namespace App\Entity;

use App\Entity\Interface\CompanyOwnedInterface;
use App\Repository\PlanningRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PlanningRepository::class)]
#[ORM\Table(name: 'planning')]
#[ORM\Index(name: 'idx_planning_company', columns: ['company_id'])]
class Planning implements CompanyOwnedInterface
{
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

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Project $project;

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

    // Nombre d'heures planifiées par jour
    #[ORM\Column(type: 'decimal', precision: 4, scale: 2)]
    public string $dailyHours = '8.00' {
        get => $this->dailyHours;
        set {
            $this->dailyHours = $value;
        }
    }

    // Profil planifié pour cette période
    #[ORM\ManyToOne(targetEntity: Profile::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Profile $profile = null;

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $notes = null {
        get => $this->notes;
        set {
            $this->notes = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 20)]
    public string $status = 'planned' { // planned, confirmed, cancelled
        get => $this->status;
        set {
            $this->status = $value;
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
    public ?DateTimeInterface $updatedAt = null {
        get => $this->updatedAt;
        set {
            $this->updatedAt = $value;
        }
    }

    // Compétences requises spécifiques à cette affectation (surcharge du projet)
    #[ORM\OneToMany(targetEntity: PlanningSkill::class, mappedBy: 'planning', cascade: ['persist', 'remove'])]
    private Collection $planningSkills;

    public function __construct()
    {
        $this->createdAt      = new DateTime();
        $this->planningSkills = new ArrayCollection();
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

    public function getProfile(): ?Profile
    {
        return $this->profile;
    }

    public function setProfile(?Profile $profile): self
    {
        $this->profile = $profile;

        return $this;
    }

    /**
     * Calcule le nombre total d'heures planifiées.
     */
    public function getTotalPlannedHours(): string
    {
        $days = $this->getNumberOfWorkingDays();

        return bcmul((string) $days, $this->dailyHours, 2);
    }

    /**
     * Calcule le nombre de jours ouvrés entre start et end.
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
     * Calcule le coût projeté basé sur le CJM du contributeur.
     */
    public function getProjectedCost(): string
    {
        $totalHours = $this->getTotalPlannedHours();
        $cjm        = $this->contributor->getCjm();

        // Convertir heures en jours (8h = 1j) puis multiplier par CJM
        $days = bcdiv($totalHours, '8', 4);

        return bcmul($days, (string) $cjm, 2);
    }

    /**
     * Vérifie si cette planification est active à une date donnée.
     */
    public function isActiveAt(DateTimeInterface $date): bool
    {
        return $date >= $this->startDate && $date <= $this->endDate;
    }

    // Gestion des compétences requises pour l'affectation

    /** @return Collection<int, PlanningSkill> */
    public function getPlanningSkills(): Collection
    {
        return $this->planningSkills;
    }

    public function addPlanningSkill(PlanningSkill $planningSkill): self
    {
        if (!$this->planningSkills->contains($planningSkill)) {
            $this->planningSkills->add($planningSkill);
            $planningSkill->setPlanning($this);
        }

        return $this;
    }

    public function removePlanningSkill(PlanningSkill $planningSkill): self
    {
        if ($this->planningSkills->removeElement($planningSkill)) {
            if ($planningSkill->getPlanning() === $this) {
                $planningSkill->setPlanning(null);
            }
        }

        return $this;
    }

    /**
     * Retourne les compétences obligatoires non satisfaites par le collaborateur.
     *
     * @return Collection<int, PlanningSkill>
     */
    public function getUnmetMandatorySkills(): Collection
    {
        return $this->planningSkills->filter(
            fn (PlanningSkill $ps) => $ps->isMandatory() && !$ps->isMetByAssignedContributor(),
        );
    }

    /**
     * Vérifie si le collaborateur satisfait toutes les compétences obligatoires.
     */
    public function contributorMeetsAllMandatorySkills(): bool
    {
        return $this->getUnmetMandatorySkills()->isEmpty();
    }

    /**
     * Retourne le score de compatibilité du collaborateur (0-100).
     * Basé sur le pourcentage de compétences satisfaites.
     */
    public function getContributorCompatibilityScore(): int
    {
        if ($this->planningSkills->isEmpty()) {
            return 100;
        }

        $met   = 0;
        $total = 0;

        foreach ($this->planningSkills as $planningSkill) {
            $weight = $planningSkill->isMandatory() ? 2 : 1;
            $total += $weight;

            if ($planningSkill->isMetByAssignedContributor()) {
                $met += $weight;
            }
        }

        return $total > 0 ? (int) round(($met / $total) * 100) : 100;
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
     * With PHP 8.4 public private(set), prefer direct access: $planning->id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $planning->startDate.
     */
    public function getStartDate(): DateTimeInterface
    {
        return $this->startDate;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $planning->startDate = $value.
     */
    public function setStartDate(DateTimeInterface $value): self
    {
        $this->startDate = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $planning->endDate.
     */
    public function getEndDate(): DateTimeInterface
    {
        return $this->endDate;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $planning->endDate = $value.
     */
    public function setEndDate(DateTimeInterface $value): self
    {
        $this->endDate = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $planning->dailyHours.
     */
    public function getDailyHours(): string
    {
        return $this->dailyHours;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $planning->dailyHours = $value.
     */
    public function setDailyHours(string $value): self
    {
        $this->dailyHours = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $planning->notes.
     */
    public function getNotes(): ?string
    {
        return $this->notes;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $planning->notes = $value.
     */
    public function setNotes(?string $value): self
    {
        $this->notes = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $planning->status.
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $planning->status = $value.
     */
    public function setStatus(string $value): self
    {
        $this->status = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $planning->createdAt.
     */
    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $planning->createdAt = $value.
     */
    public function setCreatedAt(DateTimeInterface $value): self
    {
        $this->createdAt = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $planning->updatedAt.
     */
    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $planning->updatedAt = $value.
     */
    public function setUpdatedAt(?DateTimeInterface $value): self
    {
        $this->updatedAt = $value;

        return $this;
    }
}
