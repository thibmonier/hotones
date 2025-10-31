<?php

namespace App\Entity;

use DateTimeInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\ProjectTaskRepository::class)]
#[ORM\Table(name: 'project_tasks')]
class ProjectTask
{
    public const TYPE_AVV       = 'avv'; // Avant-vente
    public const TYPE_NON_VENDU = 'non_vendu'; // Non-vendu
    public const TYPE_REGULAR   = 'regular'; // Tâche normale (vendue)

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: false)]
    private Project $project;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $type = self::TYPE_REGULAR;

    // Les tâches AVV et non-vendu sont automatiquement créées
    #[ORM\Column(type: 'boolean')]
    private bool $isDefault = false;

    // Les tâches par défaut ne comptent pas dans la rentabilité
    #[ORM\Column(type: 'boolean')]
    private bool $countsForProfitability = true;

    #[ORM\Column(type: 'integer')]
    private int $position = 0;

    #[ORM\Column(type: 'boolean')]
    private bool $active = true;

    // Estimations de temps
    #[ORM\Column(name: 'estimated_hours_sold', type: 'integer', nullable: true)]
    private ?int $estimatedHoursSold = null; // Heures vendues au client

    #[ORM\Column(name: 'estimated_hours_revised', type: 'integer', nullable: true)]
    private ?int $estimatedHoursRevised = null; // Heures réévaluées pendant le projet

    // Avancement de la tâche (0-100%)
    #[ORM\Column(name: 'progress_percentage', type: 'integer')]
    private int $progressPercentage = 0;

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
    private ?string $dailyRate = null;

    // Dates de début et fin prévues
    #[ORM\Column(type: 'date', nullable: true)]
    private ?DateTimeInterface $startDate = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?DateTimeInterface $endDate = null;

    // Statut de la tâche
    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'not_started'; // not_started, in_progress, completed, on_hold

    // Note: Les temps sont liés au projet global, pas aux tâches spécifiques
    // pour simplifier le modèle actuel

    #[ORM\OneToMany(mappedBy: 'task', targetEntity: ProjectSubTask::class, orphanRemoval: true)]
    private Collection $subTasks;

    public function __construct()
    {
        $this->subTasks = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getType(): string
    {
        return $this->type ?? self::TYPE_REGULAR;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getIsDefault(): bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(bool $isDefault): self
    {
        $this->isDefault = $isDefault;

        return $this;
    }

    public function getCountsForProfitability(): bool
    {
        return $this->countsForProfitability;
    }

    public function setCountsForProfitability(bool $countsForProfitability): self
    {
        $this->countsForProfitability = $countsForProfitability;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Calcule le total des heures passées sur cette tâche via les timesheets.
     */
    public function getTotalHours(): string
    {
        // Récupérer tous les timesheets liés à cette tâche
        $totalHours = '0.00';

        // Les timesheets sont liés au projet, on filtre ceux qui ont cette tâche
        foreach ($this->project->getTimesheets() as $timesheet) {
            if ($timesheet->getTask() && $timesheet->getTask()->getId() === $this->getId()) {
                $totalHours = bcadd($totalHours, $timesheet->getHours(), 2);
            }
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
            default              => 'Non défini'
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
        $avvTask->setName('AVV - Avant-vente');
        $avvTask->setDescription('Temps passé en avant-vente (ne compte pas dans la rentabilité)');
        $avvTask->setType(self::TYPE_AVV);
        $avvTask->setIsDefault(true);
        $avvTask->setCountsForProfitability(false);
        $avvTask->setPosition(1);
        $tasks[] = $avvTask;

        // Tâche Non-vendu
        $nonVenduTask = new self();
        $nonVenduTask->setProject($project);
        $nonVenduTask->setName('Non-vendu');
        $nonVenduTask->setDescription('Temps passé non-vendu (ne compte pas dans la rentabilité)');
        $nonVenduTask->setType(self::TYPE_NON_VENDU);
        $nonVenduTask->setIsDefault(true);
        $nonVenduTask->setCountsForProfitability(false);
        $nonVenduTask->setPosition(2);
        $tasks[] = $nonVenduTask;

        return $tasks;
    }

    // Getters et setters pour les nouvelles propriétés
    public function getEstimatedHoursSold(): ?int
    {
        return $this->estimatedHoursSold;
    }

    public function setEstimatedHoursSold(?int $estimatedHoursSold): self
    {
        $this->estimatedHoursSold = $estimatedHoursSold;

        return $this;
    }

    public function getEstimatedHoursRevised(): ?int
    {
        return $this->estimatedHoursRevised;
    }

    public function setEstimatedHoursRevised(?int $estimatedHoursRevised): self
    {
        $this->estimatedHoursRevised = $estimatedHoursRevised;

        return $this;
    }

    public function getProgressPercentage(): int
    {
        return $this->progressPercentage ?? 0;
    }

    public function setProgressPercentage(int $progressPercentage): self
    {
        $this->progressPercentage = $progressPercentage;

        return $this;
    }

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

    public function getDailyRate(): ?string
    {
        return $this->dailyRate;
    }

    public function setDailyRate(?string $dailyRate): self
    {
        $this->dailyRate = $dailyRate;

        return $this;
    }

    public function getStartDate(): ?DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?DateTimeInterface $startDate): self
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

    public function getStatus(): string
    {
        return $this->status ?? 'not_started';
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

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
            default       => 'Non défini'
        };
    }

    /**
     * Calcule les heures restantes à passer.
     */
    public function getRemainingHours(): string
    {
        $estimatedHours = (string) ($this->estimatedHoursRevised ?? $this->estimatedHoursSold ?? 0);
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
        if (!$this->assignedContributor || !$this->estimatedHoursRevised) {
            return '0.00';
        }
        $cjm = $this->assignedContributor->getCjm();
        if (!$cjm) {
            return '0.00';
        }
        $hourlyRate = bcdiv($cjm, '8', 4);

        return bcmul((string) $this->estimatedHoursRevised, $hourlyRate, 2);
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
                    $hourlyRate = bcdiv($cjm, '8', 4); // CJM / 8h
                    $timeCost   = bcmul($timesheet->getHours(), $hourlyRate, 2);
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
}
