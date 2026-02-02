<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Entity\Interface\CompanyOwnedInterface;
use App\Repository\ProjectRepository;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ORM\Table(name: 'projects', indexes: [
    new ORM\Index(name: 'idx_project_status', columns: ['status']),
    new ORM\Index(name: 'idx_project_start_date', columns: ['start_date']),
    new ORM\Index(name: 'idx_project_end_date', columns: ['end_date']),
    new ORM\Index(name: 'idx_project_type', columns: ['project_type']),
    new ORM\Index(name: 'idx_project_service_category', columns: ['service_category_id']),
    new ORM\Index(name: 'idx_project_company', columns: ['company_id']),
    new ORM\Index(name: 'idx_project_boond_manager', columns: ['boond_manager_id']),
])]
#[ApiResource(
    operations: [
        new Get(security: "is_granted('ROLE_USER')"),
        new GetCollection(security: "is_granted('ROLE_USER')"),
        new Post(security: "is_granted('ROLE_CHEF_PROJET')"),
        new Put(security: "is_granted('ROLE_CHEF_PROJET')"),
        new Patch(security: "is_granted('ROLE_CHEF_PROJET')"),
        new Delete(security: "is_granted('ROLE_MANAGER')"),
    ],
    normalizationContext: ['groups' => ['project:read']],
    denormalizationContext: ['groups' => ['project:write']],
    paginationItemsPerPage: 30,
)]
class Project implements CompanyOwnedInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['project:read'])]
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

    #[ORM\Column(type: 'string', length: 180)]
    #[Groups(['project:read', 'project:write'])]
    public string $name {
        get => $this->name;
        set {
            $this->name = $value;
        }
    }

    #[ORM\ManyToOne(targetEntity: Client::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['project:read', 'project:write'])]
    public ?Client $client = null {
        get => $this->client;
        set {
            $this->client = $value;
        }
    }

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['project:read', 'project:write'])]
    public ?string $description = null {
        get => $this->description;
        set {
            $this->description = $value;
        }
    }

    // Achats sur le projet (fournitures ou renfort externes)
    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true)]
    #[Groups(['project:read', 'project:write'])]
    public ?string $purchasesAmount = null {
        get => $this->purchasesAmount;
        set {
            $this->purchasesAmount = $value;
        }
    }

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['project:read', 'project:write'])]
    public ?string $purchasesDescription = null {
        get => $this->purchasesDescription;
        set {
            $this->purchasesDescription = $value;
        }
    }

    #[ORM\Column(type: 'date', nullable: true)]
    #[Groups(['project:read', 'project:write'])]
    public ?DateTimeInterface $startDate = null {
        get => $this->startDate;
        set {
            $this->startDate = $value;
        }
    }

    #[ORM\Column(type: 'date', nullable: true)]
    #[Groups(['project:read', 'project:write'])]
    public ?DateTimeInterface $endDate = null {
        get => $this->endDate;
        set {
            $this->endDate = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 20)]
    #[Groups(['project:read', 'project:write'])]
    public string $status = 'active' { // active, completed, cancelled
        get => $this->status;
        set {
            $this->status = $value;
        }
    }

    // Type de projet (interne/externe)
    #[ORM\Column(type: 'boolean')]
    #[Groups(['project:read', 'project:write'])]
    public bool $isInternal = false {
        get => $this->isInternal;
        set {
            $this->isInternal = $value;
        }
    }

    // Type de projet (forfait ou régie)
    #[ORM\Column(type: 'string', length: 20)]
    #[Groups(['project:read', 'project:write'])]
    public string $projectType = 'forfait' { // forfait, regie
        get => $this->projectType;
        set {
            $this->projectType = $value;
        }
    }

    // Rôles projet - références vers User
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    public ?User $keyAccountManager = null { // Commercial en charge du projet
        get => $this->keyAccountManager;
        set {
            $this->keyAccountManager = $value;
        }
    }

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    public ?User $projectManager = null { // Chef de projet
        get => $this->projectManager;
        set {
            $this->projectManager = $value;
        }
    }

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    public ?User $projectDirector = null { // Directeur de projet
        get => $this->projectDirector;
        set {
            $this->projectDirector = $value;
        }
    }

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    public ?User $salesPerson = null { // Commercial ayant identifié le projet
        get => $this->salesPerson;
        set {
            $this->salesPerson = $value;
        }
    }

    // Relations
    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'project', cascade: ['persist', 'remove'])]
    private Collection $orders;

    // Technologies utilisées dans le projet (relation historique sans version)
    #[ORM\ManyToMany(targetEntity: Technology::class, inversedBy: 'projects')]
    #[ORM\JoinTable(name: 'project_technologies')]
    private Collection $technologies;

    // Technologies avec version par projet (nouvelle relation)
    #[ORM\OneToMany(mappedBy: 'project', targetEntity: ProjectTechnology::class, cascade: ['persist', 'remove'])]
    private Collection $projectTechnologies;

    // Catégorie de service (Brand, E-commerce, etc.)
    #[ORM\ManyToOne(targetEntity: ServiceCategory::class, inversedBy: 'projects')]
    #[ORM\JoinColumn(nullable: true)]
    public ?ServiceCategory $serviceCategory = null {
        get => $this->serviceCategory;
        set {
            $this->serviceCategory = $value;
        }
    }

    // Tâches du projet
    #[ORM\OneToMany(targetEntity: ProjectTask::class, mappedBy: 'project', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $tasks;

    // Temps passés sur le projet
    #[ORM\OneToMany(targetEntity: Timesheet::class, mappedBy: 'project', cascade: ['remove'])]
    private Collection $timesheets;

    // Liens et accès techniques (Sprint 9)
    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $repoLinks = null { // Liens gestionnaires de sources (un par ligne)
        get => $this->repoLinks;
        set {
            $this->repoLinks = $value;
        }
    }

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $envLinks = null { // Liens environnements (un par ligne)
        get => $this->envLinks;
        set {
            $this->envLinks = $value;
        }
    }

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $dbAccess = null { // Informations d'accès BDD
        get => $this->dbAccess;
        set {
            $this->dbAccess = $value;
        }
    }

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $sshAccess = null { // Informations d'accès SSH
        get => $this->sshAccess;
        set {
            $this->sshAccess = $value;
        }
    }

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $ftpAccess = null { // Informations d'accès FTP
        get => $this->ftpAccess;
        set {
            $this->ftpAccess = $value;
        }
    }

    /**
     * ID du projet dans BoondManager pour la synchronisation.
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    public ?int $boondManagerId = null {
        get => $this->boondManagerId;
        set {
            $this->boondManagerId = $value;
        }
    }

    public function __construct()
    {
        $this->orders              = new ArrayCollection();
        $this->technologies        = new ArrayCollection();
        $this->projectTechnologies = new ArrayCollection();
        $this->tasks               = new ArrayCollection();
        $this->timesheets          = new ArrayCollection();
    }

    /**
     * Alias pour getKeyAccountManager().
     */
    public function getKam(): ?User
    {
        return $this->keyAccountManager;
    }

    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Order $order): self
    {
        if (!$this->orders->contains($order)) {
            $this->orders[] = $order;
            $order->project = $this;
        }

        return $this;
    }

    public function removeOrder(Order $order): self
    {
        if ($this->orders->removeElement($order)) {
            if ($order->project === $this) {
                $order->project = null;
            }
        }

        return $this;
    }

    public function getTechnologies(): Collection
    {
        return $this->technologies;
    }

    public function getProjectTechnologies(): Collection
    {
        return $this->projectTechnologies;
    }

    public function addProjectTechnology(ProjectTechnology $pt): self
    {
        if (!$this->projectTechnologies->contains($pt)) {
            $this->projectTechnologies[] = $pt;
            $pt->setProject($this);
        }

        return $this;
    }

    public function removeProjectTechnology(ProjectTechnology $pt): self
    {
        if ($this->projectTechnologies->removeElement($pt)) {
            if ($pt->getProject() === $this) {
                $pt->setProject($this); // keep relation consistent or set null if nullable
            }
        }

        return $this;
    }

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

    // Gestion des tâches
    public function getTasks(): Collection
    {
        return $this->tasks;
    }

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

    /**
     * Calcule le CA total du projet = somme des montants des devis signés/validés.
     * Seuls les devis avec statut 'signe', 'gagne' ou 'termine' sont comptabilisés.
     */
    public function getTotalSoldAmount(): string
    {
        $total         = '0';
        $validStatuses = ['signe', 'gagne', 'termine'];

        foreach ($this->orders as $order) {
            if (in_array($order->status, $validStatuses, true)) {
                $total = bcadd($total, (string) $order->totalAmount, 2);
            }
        }

        return $total;
    }

    public function getTotalSoldDays(): string
    {
        $total = '0';
        foreach ($this->orders as $order) {
            foreach ($order->getTasks() as $task) {
                $total = bcadd($total, (string) $task->getSoldDays(), 2);
            }
        }

        return $total;
    }

    // === Métriques basées sur les tâches ===

    /**
     * Calcule le total des heures vendues sur les tâches.
     */
    public function getTotalTasksSoldHours(): string
    {
        $total = '0';
        foreach ($this->tasks as $task) {
            if ($task->getEstimatedHoursSold() && $task->getCountsForProfitability() && $task->getType() === ProjectTask::TYPE_REGULAR) {
                $total = bcadd($total, (string) $task->getEstimatedHoursSold(), 2);
            }
        }

        return $total;
    }

    /**
     * Nombre de jours budgétés sur le projet (1 jour = 8h), basé sur les heures vendues.
     */
    public function calculateBudgetedDays(): float
    {
        return (float) bcdiv($this->getTotalTasksSoldHours(), '8', 2);
    }

    /**
     * Calcule le total des heures estimées révisées.
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
     * Calcule le total des heures passées sur les tâches.
     */
    public function getTotalTasksSpentHours(): string
    {
        $total = '0';
        foreach ($this->tasks as $task) {
            if ($task->getCountsForProfitability() && $task->getType() === ProjectTask::TYPE_REGULAR) {
                $total = bcadd($total, (string) $task->getTotalHours(), 2);
            }
        }

        return $total;
    }

    /**
     * Calcule le total des heures restantes à passer.
     */
    public function getTotalRemainingHours(): string
    {
        $total = '0';
        foreach ($this->tasks as $task) {
            if ($task->getCountsForProfitability() && $task->getType() === ProjectTask::TYPE_REGULAR) {
                $total = bcadd($total, (string) $task->getRemainingHours(), 2);
            }
        }

        return $total;
    }

    /**
     * Nombre de jours restants (1 jour = 8h), basé sur les heures restantes.
     */
    public function calculateRemainingDays(): float
    {
        return (float) bcdiv($this->getTotalRemainingHours(), '8', 2);
    }

    /**
     * Calcule le montant total vendu via les tâches.
     */
    public function getTotalTasksSoldAmount(): string
    {
        $total = '0';
        foreach ($this->tasks as $task) {
            if ($task->getCountsForProfitability() && $task->getType() === ProjectTask::TYPE_REGULAR) {
                $total = bcadd($total, (string) $task->getSoldAmount(), 2);
            }
        }

        return $total;
    }

    /**
     * Calcule le coût estimé total des tâches.
     */
    public function getTotalTasksEstimatedCost(): string
    {
        $total = '0';
        foreach ($this->tasks as $task) {
            if ($task->getCountsForProfitability() && $task->getType() === ProjectTask::TYPE_REGULAR) {
                $total = bcadd($total, (string) $task->getEstimatedCost(), 2);
            }
        }

        return $total;
    }

    /**
     * Calcule la marge brute cible (basée sur les tâches vendues).
     */
    public function getTargetGrossMargin(): string
    {
        $soldAmount    = $this->getTotalTasksSoldAmount();
        $estimatedCost = $this->getTotalTasksEstimatedCost();

        return bcsub($soldAmount, $estimatedCost, 2);
    }

    /**
     * Calcule le pourcentage de marge cible.
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
     * Retourne les intervenants du projet avec leurs heures.
     */
    public function getProjectContributorsWithHours(): array
    {
        $contributors = [];

        // Récupérer les contributeurs des tâches
        foreach ($this->tasks as $task) {
            if ($task->getAssignedContributor() && $task->getCountsForProfitability() && $task->getType() === ProjectTask::TYPE_REGULAR) {
                $contributor   = $task->getAssignedContributor();
                $contributorId = $contributor->id;

                if (!isset($contributors[$contributorId])) {
                    $contributors[$contributorId] = [
                        'contributor'     => $contributor,
                        'spent_hours'     => '0',
                        'remaining_hours' => '0',
                        'estimated_hours' => '0',
                        'tasks'           => [],
                    ];
                }

                $contributors[$contributorId]['spent_hours'] = bcadd(
                    $contributors[$contributorId]['spent_hours'],
                    (string) $task->getTotalHours(),
                    2,
                );

                $contributors[$contributorId]['remaining_hours'] = bcadd(
                    $contributors[$contributorId]['remaining_hours'],
                    (string) $task->getRemainingHours(),
                    2,
                );

                $estimatedHours                                  = $task->getEstimatedHoursRevised() ?? $task->getEstimatedHoursSold() ?? '0';
                $contributors[$contributorId]['estimated_hours'] = bcadd(
                    $contributors[$contributorId]['estimated_hours'],
                    $estimatedHours,
                    2,
                );

                $contributors[$contributorId]['tasks'][] = $task;
            }
        }

        return array_values($contributors);
    }

    /**
     * Calcule le pourcentage d'avancement global du projet.
     */
    public function getGlobalProgress(): string
    {
        if ($this->tasks->isEmpty()) {
            return '0.00';
        }

        $totalWeight      = '0';
        $weightedProgress = '0';

        foreach ($this->tasks as $task) {
            if ($task->getCountsForProfitability() && $task->getType() === ProjectTask::TYPE_REGULAR) {
                $hours    = $task->getEstimatedHoursRevised() ?? $task->getEstimatedHoursSold() ?? '1';
                $weight   = $hours;
                $progress = $task->getProgressPercentage();

                $totalWeight      = bcadd($totalWeight, $weight, 2);
                $weightedProgress = bcadd($weightedProgress, bcmul($weight, $progress, 4), 2);
            }
        }

        if (bccomp($totalWeight, '0', 2) <= 0) {
            return '0.00';
        }

        return bcdiv($weightedProgress, $totalWeight, 2);
    }

    /**
     * Calcule le total des heures réellement passées sur le projet.
     */
    public function getTotalRealHours(): string
    {
        $total = '0';
        foreach ($this->tasks as $task) {
            if ($task->getCountsForProfitability() && $task->getType() === ProjectTask::TYPE_REGULAR) {
                $total = bcadd($total, (string) $task->getTotalHours(), 2);
            }
        }

        return $total;
    }

    /**
     * Calcule le coût réel total du projet.
     */
    public function getTotalRealCost(): string
    {
        $total = '0';
        foreach ($this->tasks as $task) {
            if ($task->getCountsForProfitability() && $task->getType() === ProjectTask::TYPE_REGULAR) {
                $total = bcadd($total, (string) $task->getRealCost(), 2);
            }
        }

        return $total;
    }

    /**
     * Calcule la marge réelle totale du projet.
     */
    public function getTotalRealMargin(): string
    {
        $soldAmount = $this->getTotalTasksSoldAmount();
        $realCost   = $this->getTotalRealCost();

        return bcsub($soldAmount, $realCost, 2);
    }

    /**
     * Calcule le taux de marge réel du projet.
     */
    public function getRealMarginPercentage(): string
    {
        $soldAmount = $this->getTotalTasksSoldAmount();

        if (bccomp($soldAmount, '0', 2) <= 0) {
            return '0.00';
        }

        $realMargin = $this->getTotalRealMargin();

        return bcmul(bcdiv($realMargin, $soldAmount, 4), '100', 2);
    }

    /**
     * Compare les performances prévisionnelles vs réelles.
     */
    public function getPerformanceComparison(): array
    {
        $targetHours  = $this->getTotalTasksRevisedHours();
        $realHours    = $this->getTotalRealHours();
        $targetCost   = $this->getTotalTasksEstimatedCost();
        $realCost     = $this->getTotalRealCost();
        $targetMargin = $this->getTargetGrossMargin();
        $realMargin   = $this->getTotalRealMargin();

        // Calcul des écarts
        $hoursVariance  = bcsub($realHours, $targetHours, 2);
        $costVariance   = bcsub($realCost, $targetCost, 2);
        $marginVariance = bcsub($realMargin, $targetMargin, 2);

        // Calcul des pourcentages d'écart
        $hoursVariancePercent = bccomp($targetHours, '0', 2) > 0 ?
            bcmul(bcdiv($hoursVariance, $targetHours, 4), '100', 2) : '0.00';
        $costVariancePercent = bccomp($targetCost, '0', 2) > 0 ?
            bcmul(bcdiv($costVariance, $targetCost, 4), '100', 2) : '0.00';
        $marginVariancePercent = bccomp($targetMargin, '0', 2) > 0 ?
            bcmul(bcdiv($marginVariance, $targetMargin, 4), '100', 2) : '0.00';

        return [
            'target_hours'           => $targetHours,
            'real_hours'             => $realHours,
            'hours_variance'         => $hoursVariance,
            'hours_variance_percent' => $hoursVariancePercent,

            'target_cost'           => $targetCost,
            'real_cost'             => $realCost,
            'cost_variance'         => $costVariance,
            'cost_variance_percent' => $costVariancePercent,

            'target_margin'           => $targetMargin,
            'real_margin'             => $realMargin,
            'margin_variance'         => $marginVariance,
            'margin_variance_percent' => $marginVariancePercent,
        ];
    }

    // Gestion des temps passés (timesheets)
    public function getTimesheets(): Collection
    {
        return $this->timesheets;
    }

    public function addTimesheet(Timesheet $timesheet): self
    {
        if (!$this->timesheets->contains($timesheet)) {
            $this->timesheets[] = $timesheet;
            $timesheet->project = $this;
        }

        return $this;
    }

    public function removeTimesheet(Timesheet $timesheet): self
    {
        $this->timesheets->removeElement($timesheet);

        return $this;
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
     * With PHP 8.4 property hooks, prefer direct access: $project->name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $project->name = $value.
     */
    public function setName(string $value): self
    {
        $this->name = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $project->client.
     */
    public function getClient(): ?Client
    {
        return $this->client;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $project->client = $value.
     */
    public function setClient(?Client $value): self
    {
        $this->client = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $project->description.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $project->description = $value.
     */
    public function setDescription(?string $value): self
    {
        $this->description = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $project->purchasesAmount.
     */
    public function getPurchasesAmount(): ?string
    {
        return $this->purchasesAmount;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $project->purchasesAmount = $value.
     */
    public function setPurchasesAmount(?string $value): self
    {
        $this->purchasesAmount = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $project->purchasesDescription.
     */
    public function getPurchasesDescription(): ?string
    {
        return $this->purchasesDescription;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $project->purchasesDescription = $value.
     */
    public function setPurchasesDescription(?string $value): self
    {
        $this->purchasesDescription = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $project->startDate.
     */
    public function getStartDate(): ?DateTimeInterface
    {
        return $this->startDate;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $project->startDate = $value.
     */
    public function setStartDate(?DateTimeInterface $value): self
    {
        $this->startDate = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $project->endDate.
     */
    public function getEndDate(): ?DateTimeInterface
    {
        return $this->endDate;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $project->endDate = $value.
     */
    public function setEndDate(?DateTimeInterface $value): self
    {
        $this->endDate = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $project->status.
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $project->status = $value.
     */
    public function setStatus(string $value): self
    {
        $this->status = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $project->isInternal.
     */
    public function getIsInternal(): bool
    {
        return $this->isInternal;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $project->isInternal = $value.
     */
    public function setIsInternal(bool $value): self
    {
        $this->isInternal = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $project->projectType.
     */
    public function getProjectType(): string
    {
        return $this->projectType;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $project->projectType = $value.
     */
    public function setProjectType(string $value): self
    {
        $this->projectType = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $project->keyAccountManager.
     */
    public function getKeyAccountManager(): ?User
    {
        return $this->keyAccountManager;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $project->keyAccountManager = $value.
     */
    public function setKeyAccountManager(?User $value): self
    {
        $this->keyAccountManager = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $project->projectManager.
     */
    public function getProjectManager(): ?User
    {
        return $this->projectManager;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $project->projectManager = $value.
     */
    public function setProjectManager(?User $value): self
    {
        $this->projectManager = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $project->projectDirector.
     */
    public function getProjectDirector(): ?User
    {
        return $this->projectDirector;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $project->projectDirector = $value.
     */
    public function setProjectDirector(?User $value): self
    {
        $this->projectDirector = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $project->salesPerson.
     */
    public function getSalesPerson(): ?User
    {
        return $this->salesPerson;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $project->salesPerson = $value.
     */
    public function setSalesPerson(?User $value): self
    {
        $this->salesPerson = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $project->serviceCategory.
     */
    public function getServiceCategory(): ?ServiceCategory
    {
        return $this->serviceCategory;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $project->serviceCategory = $value.
     */
    public function setServiceCategory(?ServiceCategory $value): self
    {
        $this->serviceCategory = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $project->repoLinks.
     */
    public function getRepoLinks(): ?string
    {
        return $this->repoLinks;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $project->repoLinks = $value.
     */
    public function setRepoLinks(?string $value): self
    {
        $this->repoLinks = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $project->envLinks.
     */
    public function getEnvLinks(): ?string
    {
        return $this->envLinks;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $project->envLinks = $value.
     */
    public function setEnvLinks(?string $value): self
    {
        $this->envLinks = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $project->dbAccess.
     */
    public function getDbAccess(): ?string
    {
        return $this->dbAccess;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $project->dbAccess = $value.
     */
    public function setDbAccess(?string $value): self
    {
        $this->dbAccess = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $project->sshAccess.
     */
    public function getSshAccess(): ?string
    {
        return $this->sshAccess;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $project->sshAccess = $value.
     */
    public function setSshAccess(?string $value): self
    {
        $this->sshAccess = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $project->ftpAccess.
     */
    public function getFtpAccess(): ?string
    {
        return $this->ftpAccess;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $project->ftpAccess = $value.
     */
    public function setFtpAccess(?string $value): self
    {
        $this->ftpAccess = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 public private(set), prefer direct access: $project->id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $project->isInternal.
     */
    public function isInternal(): bool
    {
        return $this->isInternal;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $project->boondManagerId.
     */
    public function getBoondManagerId(): ?int
    {
        return $this->boondManagerId;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $project->boondManagerId = $value.
     */
    public function setBoondManagerId(?int $value): self
    {
        $this->boondManagerId = $value;

        return $this;
    }
}
