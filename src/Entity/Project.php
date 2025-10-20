<?php

namespace App\Entity;

use App\Repository\ProjectRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ORM\Table(name: 'projects')]
class Project
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 180)]
    private string $name;

    #[ORM\Column(type: 'string', length: 180, nullable: true)]
    private ?string $client = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    // Achats sur le projet (fournitures ou renfort externes)
    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true)]
    private ?string $purchasesAmount = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $purchasesDescription = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'active'; // active, completed, cancelled

    // Type de projet (interne/externe)
    #[ORM\Column(type: 'boolean')]
    private bool $isInternal = false;

    // Type de projet (forfait ou régie)
    #[ORM\Column(type: 'string', length: 20)]
    private string $projectType = 'forfait'; // forfait, regie

    // Rôles projet - références vers User
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $keyAccountManager = null; // Commercial en charge du projet

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $projectManager = null; // Chef de projet

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $projectDirector = null; // Directeur de projet

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $salesPerson = null; // Commercial ayant identifié le projet

    // Relations
    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'project', cascade: ['persist', 'remove'])]
    private Collection $orders;

    // Technologies utilisées dans le projet
    #[ORM\ManyToMany(targetEntity: Technology::class, inversedBy: 'projects')]
    #[ORM\JoinTable(name: 'project_technologies')]
    private Collection $technologies;

    // Catégorie de service (Brand, E-commerce, etc.)
    #[ORM\ManyToOne(targetEntity: ServiceCategory::class, inversedBy: 'projects')]
    #[ORM\JoinColumn(nullable: true)]
    private ?ServiceCategory $serviceCategory = null;

    // Tâches du projet
    #[ORM\OneToMany(targetEntity: ProjectTask::class, mappedBy: 'project', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $tasks;

    public function __construct()
    {
        $this->orders = new ArrayCollection();
        $this->technologies = new ArrayCollection();
        $this->tasks = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }

    public function getClient(): ?string { return $this->client; }
    public function setClient(?string $client): self { $this->client = $client; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }

    public function getPurchasesAmount(): ?string { return $this->purchasesAmount; }
    public function setPurchasesAmount(?string $purchasesAmount): self { $this->purchasesAmount = $purchasesAmount; return $this; }

    public function getPurchasesDescription(): ?string { return $this->purchasesDescription; }
    public function setPurchasesDescription(?string $purchasesDescription): self { $this->purchasesDescription = $purchasesDescription; return $this; }

    public function getStartDate(): ?\DateTimeInterface { return $this->startDate; }
    public function setStartDate(?\DateTimeInterface $startDate): self { $this->startDate = $startDate; return $this; }

    public function getEndDate(): ?\DateTimeInterface { return $this->endDate; }
    public function setEndDate(?\DateTimeInterface $endDate): self { $this->endDate = $endDate; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }

    public function getIsInternal(): bool { return $this->isInternal; }
    public function setIsInternal(bool $isInternal): self { $this->isInternal = $isInternal; return $this; }

    public function getProjectType(): string { return $this->projectType; }
    public function setProjectType(string $projectType): self { $this->projectType = $projectType; return $this; }

    public function getKeyAccountManager(): ?User { return $this->keyAccountManager; }
    public function setKeyAccountManager(?User $keyAccountManager): self { $this->keyAccountManager = $keyAccountManager; return $this; }

    public function getProjectManager(): ?User { return $this->projectManager; }
    public function setProjectManager(?User $projectManager): self { $this->projectManager = $projectManager; return $this; }

    public function getProjectDirector(): ?User { return $this->projectDirector; }
    public function setProjectDirector(?User $projectDirector): self { $this->projectDirector = $projectDirector; return $this; }

    public function getSalesPerson(): ?User { return $this->salesPerson; }
    public function setSalesPerson(?User $salesPerson): self { $this->salesPerson = $salesPerson; return $this; }

    public function getOrders(): Collection { return $this->orders; }
    public function addOrder(Order $order): self
    {
        if (!$this->orders->contains($order)) {
            $this->orders[] = $order;
            $order->setProject($this);
        }
        return $this;
    }
    public function removeOrder(Order $order): self
    {
        if ($this->orders->removeElement($order)) {
            if ($order->getProject() === $this) {
                $order->setProject(null);
            }
        }
        return $this;
    }

    public function getTechnologies(): Collection { return $this->technologies; }
    public function addTechnology(Technology $technology): self
    {
        if (!$this->technologies->contains($technology)) {
            $this->technologies[] = $technology;
        }
        return $this;
    }
    public function removeTechnology(Technology $technology): self
    {
        $this->technologies->removeElement($technology);
        return $this;
    }

    public function getServiceCategory(): ?ServiceCategory { return $this->serviceCategory; }
    public function setServiceCategory(?ServiceCategory $serviceCategory): self { $this->serviceCategory = $serviceCategory; return $this; }

    // Gestion des tâches
    public function getTasks(): Collection { return $this->tasks; }
    public function addTask(ProjectTask $task): self
    {
        if (!$this->tasks->contains($task)) {
            $this->tasks[] = $task;
            $task->setProject($this);
        }
        return $this;
    }
    public function removeTask(ProjectTask $task): self
    {
        if ($this->tasks->removeElement($task)) {
            if ($task->getProject() === $this) {
                $task->setProject(null);
            }
        }
        return $this;
    }

    // Méthodes de calcul pour la rentabilité consolidée
    public function getTotalSoldAmount(): string
    {
        $total = '0';
        foreach ($this->orders as $order) {
            $total = bcadd($total, $order->getTotalAmount(), 2);
        }
        return $total;
    }

    public function getTotalSoldDays(): string
    {
        $total = '0';
        foreach ($this->orders as $order) {
            foreach ($order->getTasks() as $task) {
                $total = bcadd($total, $task->getSoldDays(), 2);
            }
        }
        return $total;
    }

    // === Métriques basées sur les tâches ===

    /**
     * Calcule le total des heures vendues sur les tâches
     */
    public function getTotalTasksSoldHours(): string
    {
        $total = '0';
        foreach ($this->tasks as $task) {
            if ($task->getEstimatedHoursSold() && $task->getCountsForProfitability() && $task->getType() === ProjectTask::TYPE_REGULAR) {
                $total = bcadd($total, $task->getEstimatedHoursSold(), 2);
            }
        }
        return $total;
    }

    /**
     * Calcule le total des heures estimées révisées
     */
    public function getTotalTasksRevisedHours(): string
    {
        $total = '0';
        foreach ($this->tasks as $task) {
            $hours = $task->getEstimatedHoursRevised() ?? $task->getEstimatedHoursSold() ?? '0';
            if ($task->getCountsForProfitability() && $task->getType() === ProjectTask::TYPE_REGULAR) {
                $total = bcadd($total, $hours, 2);
            }
        }
        return $total;
    }

    /**
     * Calcule le total des heures passées sur les tâches
     */
    public function getTotalTasksSpentHours(): string
    {
        $total = '0';
        foreach ($this->tasks as $task) {
            if ($task->getCountsForProfitability() && $task->getType() === ProjectTask::TYPE_REGULAR) {
                $total = bcadd($total, $task->getTotalHours(), 2);
            }
        }
        return $total;
    }

    /**
     * Calcule le total des heures restantes à passer
     */
    public function getTotalRemainingHours(): string
    {
        $total = '0';
        foreach ($this->tasks as $task) {
            if ($task->getCountsForProfitability() && $task->getType() === ProjectTask::TYPE_REGULAR) {
                $total = bcadd($total, $task->getRemainingHours(), 2);
            }
        }
        return $total;
    }

    /**
     * Calcule le montant total vendu via les tâches
     */
    public function getTotalTasksSoldAmount(): string
    {
        $total = '0';
        foreach ($this->tasks as $task) {
            if ($task->getCountsForProfitability() && $task->getType() === ProjectTask::TYPE_REGULAR) {
                $total = bcadd($total, $task->getSoldAmount(), 2);
            }
        }
        return $total;
    }

    /**
     * Calcule le coût estimé total des tâches
     */
    public function getTotalTasksEstimatedCost(): string
    {
        $total = '0';
        foreach ($this->tasks as $task) {
            if ($task->getCountsForProfitability() && $task->getType() === ProjectTask::TYPE_REGULAR) {
                $total = bcadd($total, $task->getEstimatedCost(), 2);
            }
        }
        return $total;
    }

    /**
     * Calcule la marge brute cible (basée sur les tâches vendues)
     */
    public function getTargetGrossMargin(): string
    {
        $soldAmount = $this->getTotalTasksSoldAmount();
        $estimatedCost = $this->getTotalTasksEstimatedCost();
        return bcsub($soldAmount, $estimatedCost, 2);
    }

    /**
     * Calcule le pourcentage de marge cible
     */
    public function getTargetMarginPercentage(): string
    {
        $soldAmount = $this->getTotalTasksSoldAmount();
        if (bccomp($soldAmount, '0', 2) <= 0) {
            return '0.00';
        }
        $margin = $this->getTargetGrossMargin();
        return bcmul(bcdiv($margin, $soldAmount, 4), '100', 2);
    }

    /**
     * Retourne les intervenants du projet avec leurs heures
     */
    public function getProjectContributorsWithHours(): array
    {
        $contributors = [];

        // Récupérer les contributeurs des tâches
        foreach ($this->tasks as $task) {
            if ($task->getAssignedContributor() && $task->getCountsForProfitability() && $task->getType() === ProjectTask::TYPE_REGULAR) {
                $contributor = $task->getAssignedContributor();
                $contributorId = $contributor->getId();

                if (!isset($contributors[$contributorId])) {
                    $contributors[$contributorId] = [
                        'contributor' => $contributor,
                        'spent_hours' => '0',
                        'remaining_hours' => '0',
                        'estimated_hours' => '0',
                        'tasks' => []
                    ];
                }

                $contributors[$contributorId]['spent_hours'] = bcadd(
                    $contributors[$contributorId]['spent_hours'],
                    $task->getTotalHours(),
                    2
                );

                $contributors[$contributorId]['remaining_hours'] = bcadd(
                    $contributors[$contributorId]['remaining_hours'],
                    $task->getRemainingHours(),
                    2
                );

                $estimatedHours = $task->getEstimatedHoursRevised() ?? $task->getEstimatedHoursSold() ?? '0';
                $contributors[$contributorId]['estimated_hours'] = bcadd(
                    $contributors[$contributorId]['estimated_hours'],
                    $estimatedHours,
                    2
                );

                $contributors[$contributorId]['tasks'][] = $task;
            }
        }

        return array_values($contributors);
    }

    /**
     * Calcule le pourcentage d'avancement global du projet
     */
    public function getGlobalProgress(): string
    {
        if ($this->tasks->isEmpty()) {
            return '0.00';
        }

        $totalWeight = '0';
        $weightedProgress = '0';

        foreach ($this->tasks as $task) {
            if ($task->getCountsForProfitability() && $task->getType() === ProjectTask::TYPE_REGULAR) {
                $hours = $task->getEstimatedHoursRevised() ?? $task->getEstimatedHoursSold() ?? '1';
                $weight = $hours;
                $progress = $task->getProgressPercentage();

                $totalWeight = bcadd($totalWeight, $weight, 2);
                $weightedProgress = bcadd($weightedProgress, bcmul($weight, $progress, 4), 2);
            }
        }

        if (bccomp($totalWeight, '0', 2) <= 0) {
            return '0.00';
        }

        return bcdiv($weightedProgress, $totalWeight, 2);
    }
}
