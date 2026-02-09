<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interface\CompanyOwnedInterface;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: \App\Repository\ProjectTaskRepository::class)]
#[ORM\Table(name: 'project_tasks')]
#[ORM\Index(name: 'idx_projecttask_company', columns: ['company_id'])]
class ProjectTask implements CompanyOwnedInterface
{
    public const TYPE_AVV       = 'avv'; // Avant-vente
    public const TYPE_NON_VENDU = 'non_vendu'; // Non-vendu
    public const TYPE_REGULAR   = 'regular'; // Tâche normale (vendue)

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public private(set) ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private Company $company;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: false)]
    private Project $project;

    // Ligne budgétaire du devis dont provient cette tâche (null pour AVV/non-vendu)
    #[ORM\ManyToOne(targetEntity: OrderLine::class)]
    #[ORM\JoinColumn(name: 'order_line_id', nullable: true, onDelete: 'SET NULL')]
    private ?OrderLine $orderLine = null;

    #[ORM\Column(type: 'string', length: 255)]
    public string $name {
        get => $this->name;
        set {
            $this->name = $value;
        }
    }

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $description = null {
        get => $this->description;
        set {
            $this->description = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 20)]
    public string $type = self::TYPE_REGULAR {
        get => $this->type ?? self::TYPE_REGULAR;
        set {
            $this->type = $value;
        }
    }

    // Les tâches AVV et non-vendu sont automatiquement créées
    #[ORM\Column(type: 'boolean')]
    public bool $isDefault = false {
        get => $this->isDefault;
        set {
            $this->isDefault = $value;
        }
    }

    // Les tâches par défaut ne comptent pas dans la rentabilité
    #[ORM\Column(type: 'boolean')]
    public bool $countsForProfitability = true {
        get => $this->countsForProfitability;
        set {
            $this->countsForProfitability = $value;
        }
    }

    #[ORM\Column(type: 'integer')]
    public int $position = 0 {
        get => $this->position;
        set {
            $this->position = $value;
        }
    }

    #[ORM\Column(type: 'boolean')]
    public bool $active = true {
        get => $this->active;
        set {
            $this->active = $value;
        }
    }

    // Estimations de temps
    #[ORM\Column(name: 'estimated_hours_sold', type: 'integer', nullable: true)]
    public ?int $estimatedHoursSold = null {
        get => $this->estimatedHoursSold;
        set {
            $this->estimatedHoursSold = $value;
        }
    }

    // Heures réévaluées pendant le projet (private property for custom getter logic)
    #[ORM\Column(name: 'estimated_hours_revised', type: 'integer', nullable: true)]
    private ?int $estimatedHoursRevisedInternal = null;

    // Avancement de la tâche (0-100%)
    #[ORM\Column(name: 'progress_percentage', type: 'integer')]
    public int $progressPercentage = 0 {
        get => $this->progressPercentage ?? 0;
        set {
            $this->progressPercentage = $value;
        }
    }

    // Contributeur assigné à la tâche
    #[ORM\ManyToOne(targetEntity: Contributor::class)]
    #[ORM\JoinColumn(name: 'assigned_contributor_id', nullable: true)]
    private ?Contributor $assignedContributor = null;

    // Profil requis pour la tâche (dev, chef projet, etc.)
    #[ORM\ManyToOne(targetEntity: Profile::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Profile $requiredProfile = null;

    // Tarif journalier pour cette tâche (peut différer du TJM standard)
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    public ?string $dailyRate = null {
        get => $this->dailyRate;
        set {
            $this->dailyRate = $value;
        }
    }

    // Dates de début et fin prévues
    #[ORM\Column(type: 'date', nullable: true)]
    public ?DateTimeInterface $startDate = null {
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

    // Statut de la tâche
    #[ORM\Column(type: 'string', length: 20)]
    public string $status = 'not_started' {
        get => $this->status ?? 'not_started';
        set {
            $this->status = $value;
        }
    }

    // Note: Les temps sont liés au projet global, pas aux tâches spécifiques
    // pour simplifier le modèle actuel

    #[ORM\OneToMany(mappedBy: 'task', targetEntity: ProjectSubTask::class, orphanRemoval: true)]
    private Collection $subTasks;

    public function __construct()
    {
        $this->subTasks = new ArrayCollection();
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

    public function getOrderLine(): ?OrderLine
    {
        return $this->orderLine;
    }

    public function setOrderLine(?OrderLine $orderLine): self
    {
        $this->orderLine = $orderLine;

        return $this;
    }

    /**
     * Calcule le total des heures passées : timesheets sur cette tâche + timesheets sur ses sous-tâches.
     * Cohérence garantie : temps_tâche = temps_propre + somme(temps_sous_tâches).
     */
    public function getTotalHours(): string
    {
        $totalHours = '0.00';

        // 1. Temps passé directement sur la tâche (sans sous-tâche spécifiée)
        foreach ($this->project->getTimesheets() as $timesheet) {
            if ($timesheet->task && $timesheet->task->getId() === $this->getId() && $timesheet->subTask === null) {
                $totalHours = bcadd($totalHours, (string) $timesheet->hours, 2);
            }
        }

        // 2. Temps passé sur les sous-tâches
        foreach ($this->subTasks as $subTask) {
            $totalHours = bcadd($totalHours, (string) $subTask->getTimeSpentHours(), 2);
        }

        return $totalHours;
    }

    /**
     * Convertit les heures en jours (1j = 8h).
     */
    public function getTotalDays(): string
    {
        $totalHours = $this->getTotalHours();

        return bcdiv($totalHours, '8', 2);
    }

    /**
     * Retourne le libellé du type de tâche.
     */
    public function getTypeLabel(): string
    {
        return match ($this->getType()) {
            self::TYPE_AVV       => 'Avant-vente (AVV)',
            self::TYPE_NON_VENDU => 'Non-vendu',
            self::TYPE_REGULAR   => 'Tâche vendue',
            default              => 'Non défini',
        };
    }

    /**
     * Crée les tâches par défaut pour un projet.
     */
    public static function createDefaultTasks(Project $project): array
    {
        $tasks = [];

        // Tâche AVV
        $avvTask = new self();
        $avvTask->setProject($project);
        $avvTask->name                   = 'AVV - Avant-vente';
        $avvTask->description            = 'Temps passé en avant-vente (ne compte pas dans la rentabilité)';
        $avvTask->type                   = self::TYPE_AVV;
        $avvTask->isDefault              = true;
        $avvTask->countsForProfitability = false;
        $avvTask->position               = 1;
        $tasks[]                         = $avvTask;

        // Tâche Non-vendu
        $nonVenduTask = new self();
        $nonVenduTask->setProject($project);
        $nonVenduTask->name                   = 'Non-vendu';
        $nonVenduTask->description            = 'Temps passé non-vendu (ne compte pas dans la rentabilité)';
        $nonVenduTask->type                   = self::TYPE_NON_VENDU;
        $nonVenduTask->isDefault              = true;
        $nonVenduTask->countsForProfitability = false;
        $nonVenduTask->position               = 2;
        $tasks[]                              = $nonVenduTask;

        return $tasks;
    }

    // Getters et setters pour les relations
    public function getAssignedContributor(): ?Contributor
    {
        return $this->assignedContributor;
    }

    public function setAssignedContributor(?Contributor $assignedContributor): self
    {
        $this->assignedContributor = $assignedContributor;

        return $this;
    }

    public function getRequiredProfile(): ?Profile
    {
        return $this->requiredProfile;
    }

    public function setRequiredProfile(?Profile $requiredProfile): self
    {
        $this->requiredProfile = $requiredProfile;

        return $this;
    }

    /**
     * Retourne le temps révisé total : somme des temps révisés des sous-tâches + temps propre de la tâche.
     * Si aucune sous-tâche et aucun temps révisé propre, retourne null.
     */
    public function getEstimatedHoursRevised(): ?int
    {
        // Si on a des sous-tâches, on agrège leur temps
        if (!$this->subTasks->isEmpty()) {
            $totalFromSubTasks = '0';
            foreach ($this->subTasks as $subTask) {
                $totalFromSubTasks = bcadd($totalFromSubTasks, (string) $subTask->getInitialEstimatedHours(), 2);
            }

            // Ajouter le temps propre de la tâche si présent
            if ($this->estimatedHoursRevisedInternal !== null) {
                $totalFromSubTasks = bcadd($totalFromSubTasks, (string) $this->estimatedHoursRevisedInternal, 2);
            }

            return (int) round((float) $totalFromSubTasks);
        }

        // Pas de sous-tâches : retourner le temps révisé propre
        return $this->estimatedHoursRevisedInternal;
    }

    public function setEstimatedHoursRevised(?int $estimatedHoursRevised): self
    {
        $this->estimatedHoursRevisedInternal = $estimatedHoursRevised;

        return $this;
    }

    /**
     * Retourne le libellé du statut.
     */
    public function getStatusLabel(): string
    {
        return match ($this->getStatus()) {
            'not_started' => 'Non démarrée',
            'in_progress' => 'En cours',
            'completed'   => 'Terminée',
            'on_hold'     => 'En attente',
            default       => 'Non défini',
        };
    }

    /**
     * Calcule les heures restantes à passer.
     */
    public function getRemainingHours(): string
    {
        $estimatedHours = (string) ($this->getEstimatedHoursRevised() ?? $this->estimatedHoursSold ?? 0);
        $spentHours     = $this->getTotalHours();
        $remaining      = bcsub($estimatedHours, $spentHours, 2);

        return bccomp($remaining, '0') > 0 ? $remaining : '0.00';
    }

    /**
     * Calcule le montant vendu pour cette tâche.
     */
    public function getSoldAmount(): string
    {
        if (!$this->estimatedHoursSold || !$this->dailyRate) {
            return '0.00';
        }
        $days = bcdiv((string) $this->estimatedHoursSold, '8', 4);

        return bcmul($days, $this->dailyRate, 2);
    }

    /**
     * Calcule le coût estimé pour cette tâche.
     */
    public function getEstimatedCost(): string
    {
        $estimatedHoursRevised = $this->getEstimatedHoursRevised();
        if (!$this->assignedContributor || !$estimatedHoursRevised) {
            return '0.00';
        }
        $cjm = $this->assignedContributor->getCjm();
        if (!$cjm) {
            return '0.00';
        }
        $hourlyRate = bcdiv($cjm, '8', 4);

        return bcmul((string) $estimatedHoursRevised, $hourlyRate, 2);
    }

    /**
     * Calcule le coût réel pour cette tâche basé sur les temps passés.
     */
    public function getRealCost(): string
    {
        $totalCost = '0.00';
        $realHours = $this->getTotalHours();

        if (bccomp($realHours, '0', 2) <= 0) {
            return $totalCost;
        }

        // Calculer le coût basé sur les timesheets et les CJM des contributeurs
        foreach ($this->project->getTimesheets() as $timesheet) {
            if ($timesheet->getTask() && $timesheet->getTask()->getId() === $this->getId()) {
                $contributor = $timesheet->getContributor();
                $cjm         = $contributor->getCjm();

                if ($cjm) {
                    $hourlyRate = bcdiv((string) $cjm, '8', 4); // CJM / 8h
                    $timeCost   = bcmul((string) $timesheet->getHours(), $hourlyRate, 2);
                    $totalCost  = bcadd($totalCost, $timeCost, 2);
                }
            }
        }

        return $totalCost;
    }

    /**
     * Calcule la marge réelle de la tâche (CA vendu - coût réel).
     */
    public function getRealMargin(): string
    {
        $soldAmount = $this->getSoldAmount();
        $realCost   = $this->getRealCost();

        return bcsub($soldAmount, $realCost, 2);
    }

    /**
     * Calcule le taux de marge réel.
     */
    public function getRealMarginRate(): string
    {
        $soldAmount = $this->getSoldAmount();

        if (bccomp($soldAmount, '0', 2) <= 0) {
            return '0.00';
        }

        $realMargin = $this->getRealMargin();

        return bcmul(bcdiv($realMargin, $soldAmount, 4), '100', 2);
    }

    /** @return Collection<int, ProjectSubTask> */
    public function getSubTasks(): Collection
    {
        return $this->subTasks;
    }

    public function addSubTask(ProjectSubTask $subTask): self
    {
        if (!$this->subTasks->contains($subTask)) {
            $this->subTasks->add($subTask);
            $subTask->setTask($this);
        }

        return $this;
    }

    public function removeSubTask(ProjectSubTask $subTask): self
    {
        if ($this->subTasks->removeElement($subTask)) {
            if ($subTask->getTask() === $this) {
                // Keep project consistency handled in entity setter
            }
        }

        return $this;
    }

    /**
     * Retourne tous les types de tâche disponibles.
     */
    public static function getAvailableTypes(): array
    {
        return [
            self::TYPE_REGULAR   => 'Tâche vendue',
            self::TYPE_AVV       => 'Avant-vente (AVV)',
            self::TYPE_NON_VENDU => 'Non-vendu',
        ];
    }

    /**
     * Retourne tous les statuts disponibles.
     */
    public static function getAvailableStatuses(): array
    {
        return [
            'not_started' => 'Non démarrée',
            'in_progress' => 'En cours',
            'completed'   => 'Terminée',
            'on_hold'     => 'En attente',
        ];
    }

    public function isDefault(): ?bool
    {
        return $this->isDefault;
    }

    public function isCountsForProfitability(): ?bool
    {
        return $this->countsForProfitability;
    }

    public function isActive(): ?bool
    {
        return $this->active;
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

    // ==================== Compatibility methods ====================
    // These methods exist for backward compatibility with existing code.
    // With PHP 8.4/8.5 property hooks, prefer direct property access.
    // Example: $task->name instead of $task->getName()

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 public private(set), prefer direct access: $task->id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $task->name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $task->name = $value.
     */
    public function setName(string $value): self
    {
        $this->name = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $task->description.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $task->description = $value.
     */
    public function setDescription(?string $value): self
    {
        $this->description = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $task->type.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $task->type = $value.
     */
    public function setType(string $value): self
    {
        $this->type = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $task->isDefault.
     */
    public function getIsDefault(): bool
    {
        return $this->isDefault;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $task->isDefault = $value.
     */
    public function setIsDefault(bool $value): self
    {
        $this->isDefault = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $task->countsForProfitability.
     */
    public function getCountsForProfitability(): bool
    {
        return $this->countsForProfitability;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $task->countsForProfitability = $value.
     */
    public function setCountsForProfitability(bool $value): self
    {
        $this->countsForProfitability = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $task->position.
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $task->position = $value.
     */
    public function setPosition(int $value): self
    {
        $this->position = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $task->active.
     */
    public function getActive(): bool
    {
        return $this->active;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $task->active = $value.
     */
    public function setActive(bool $value): self
    {
        $this->active = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $task->estimatedHoursSold.
     */
    public function getEstimatedHoursSold(): ?int
    {
        return $this->estimatedHoursSold;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $task->estimatedHoursSold = $value.
     */
    public function setEstimatedHoursSold(?int $value): self
    {
        $this->estimatedHoursSold = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $task->progressPercentage.
     */
    public function getProgressPercentage(): int
    {
        return $this->progressPercentage;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $task->progressPercentage = $value.
     */
    public function setProgressPercentage(int $value): self
    {
        $this->progressPercentage = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $task->dailyRate.
     */
    public function getDailyRate(): ?string
    {
        return $this->dailyRate;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $task->dailyRate = $value.
     */
    public function setDailyRate(?string $value): self
    {
        $this->dailyRate = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $task->startDate.
     */
    public function getStartDate(): ?DateTimeInterface
    {
        return $this->startDate;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $task->startDate = $value.
     */
    public function setStartDate(?DateTimeInterface $value): self
    {
        $this->startDate = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $task->endDate.
     */
    public function getEndDate(): ?DateTimeInterface
    {
        return $this->endDate;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $task->endDate = $value.
     */
    public function setEndDate(?DateTimeInterface $value): self
    {
        $this->endDate = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $task->status.
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $task->status = $value.
     */
    public function setStatus(string $value): self
    {
        $this->status = $value;

        return $this;
    }
}
