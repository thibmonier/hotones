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
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: \App\Repository\OrderRepository::class)]
#[ORM\Table(name: 'orders', indexes: [
    new ORM\Index(name: 'idx_order_project', columns: ['project_id']),
    new ORM\Index(name: 'idx_order_status', columns: ['status']),
    new ORM\Index(name: 'idx_order_created_at', columns: ['created_at']),
    new ORM\Index(name: 'idx_order_validated_at', columns: ['validated_at']),
    new ORM\Index(name: 'idx_order_company', columns: ['company_id']),
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
    normalizationContext: ['groups' => ['order:read']],
    denormalizationContext: ['groups' => ['order:write']],
    paginationItemsPerPage: 30,
)]
class Order implements CompanyOwnedInterface
{
    public const STATUS_OPTIONS = [
        'a_signer'  => 'À signer',
        'gagne'     => 'Gagné',
        'signe'     => 'Signé',
        'perdu'     => 'Perdu',
        'termine'   => 'Terminé',
        'standby'   => 'Standby',
        'abandonne' => 'Abandonné',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['order:read'])]
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

    #[ORM\Column(type: 'string', length: 180, nullable: true)]
    #[Groups(['order:read', 'order:write'])]
    public ?string $name = null {
        get => $this->name;
        set {
            $this->name = $value;
        }
    }

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['order:read', 'order:write'])]
    public ?string $description = null {
        get => $this->description;
        set {
            $this->description = $value;
        }
    }

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, nullable: true)]
    #[Groups(['order:read', 'order:write'])]
    public ?string $contingencyPercentage = null {
        get => $this->contingencyPercentage;
        set {
            $this->contingencyPercentage = $value;
        }
    }

    #[ORM\Column(type: 'date', nullable: true)]
    #[Groups(['order:read', 'order:write'])]
    public ?DateTimeInterface $validUntil = null {
        get => $this->validUntil;
        set {
            $this->validUntil = $value;
        }
    }

    // Numéro unique du devis D[année][mois][numéro incrémental]
    #[ORM\Column(type: 'string', length: 50, unique: true)]
    #[Groups(['order:read'])]
    public string $orderNumber {
        get => $this->orderNumber;
        set {
            $this->orderNumber = $value;
        }
    }

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $notes = null {
        get => $this->notes;
        set {
            $this->notes = $value;
        }
    }

    // Contingence (retenue sur la marge)
    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true)]
    public ?string $contingenceAmount = null {
        get => $this->contingenceAmount;
        set {
            $this->contingenceAmount = $value;
        }
    }

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $contingenceReason = null {
        get => $this->contingenceReason;
        set {
            $this->contingenceReason = $value;
        }
    }

    // Relations
    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: true)]
    public ?Project $project = null {
        get => $this->project;
        set {
            $this->project = $value;
        }
    }

    // Montant total HT du devis
    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true)]
    public ?string $totalAmount = '0.00' {
        get => $this->totalAmount ?? '0.00';
        set {
            $this->totalAmount = $value ?? '0.00';
        }
    }

    #[ORM\Column(type: 'date')]
    public DateTimeInterface $createdAt {
        get => $this->createdAt;
        set {
            $this->createdAt = $value;
        }
    }

    #[ORM\Column(type: 'date', nullable: true)]
    public ?DateTimeInterface $validatedAt = null {
        get => $this->validatedAt;
        set {
            $this->validatedAt = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 20)]
    public string $status = 'a_signer' { // a_signer, gagne, signe, perdu, termine, standby, abandonne
        get => $this->status;
        set {
            $oldStatus    = $this->status;
            $this->status = $value;

            // Définir automatiquement validated_at lors du passage à un statut validé
            if ($oldStatus !== $value && in_array($value, ['signe', 'gagne', 'termine'], true) && $this->validatedAt === null) {
                $this->validatedAt = new DateTime();
            }
        }
    }

    // Type de contractualisation du devis: forfait (échéancier) ou regie (temps passé)
    #[ORM\Column(type: 'string', length: 20, options: ['default' => 'forfait'])]
    public string $contractType = 'forfait' { // forfait, regie
        get => $this->contractType;
        set {
            $this->contractType = $value;
        }
    }

    // Relation vers les tâches du devis (ancienne structure)
    #[ORM\OneToMany(targetEntity: OrderTask::class, mappedBy: 'order', cascade: ['persist', 'remove'])]
    private Collection $tasks;

    // Relation vers les sections du devis (nouvelle structure)
    #[ORM\OneToMany(targetEntity: OrderSection::class, mappedBy: 'order', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $sections;

    // Échéancier de paiement (si contrat au forfait)
    #[ORM\OneToMany(targetEntity: OrderPaymentSchedule::class, mappedBy: 'order', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['billingDate' => 'ASC'])]
    private Collection $paymentSchedules;

    // Gestion des notes de frais refacturables
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    public bool $expensesRebillable = false {
        get => $this->expensesRebillable;
        set {
            $this->expensesRebillable = $value;
        }
    }

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, options: ['default' => '0.00'])]
    public string $expenseManagementFeeRate = '0.00' {
        get => $this->expenseManagementFeeRate;
        set {
            $this->expenseManagementFeeRate = $value;
        }
    }

    #[ORM\OneToMany(targetEntity: ExpenseReport::class, mappedBy: 'order')]
    private Collection $expenseReports;

    public function __construct()
    {
        $this->tasks            = new ArrayCollection();
        $this->sections         = new ArrayCollection();
        $this->paymentSchedules = new ArrayCollection();
        $this->expenseReports   = new ArrayCollection();
        $this->createdAt        = new DateTime();
    }

    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function addTask(OrderTask $task): self
    {
        if (!$this->tasks->contains($task)) {
            $this->tasks[] = $task;
            $task->setOrder($this);
        }

        return $this;
    }

    public function removeTask(OrderTask $task): self
    {
        if ($this->tasks->removeElement($task)) {
            if ($task->getOrder() === $this) {
                $task->setOrder(null);
            }
        }

        return $this;
    }

    public function getSections(): Collection
    {
        return $this->sections;
    }

    public function addSection(OrderSection $section): self
    {
        if (!$this->sections->contains($section)) {
            $this->sections[] = $section;
            $section->setOrder($this);
        }

        return $this;
    }

    public function removeSection(OrderSection $section): self
    {
        if ($this->sections->removeElement($section)) {
            if ($section->getOrder() === $this) {
                $section->setOrder(null);
            }
        }

        return $this;
    }

    /**
     * Calcule le montant total du devis à partir des sections.
     */
    public function getPaymentSchedules(): Collection
    {
        return $this->paymentSchedules;
    }

    public function addPaymentSchedule(OrderPaymentSchedule $schedule): self
    {
        if (!$this->paymentSchedules->contains($schedule)) {
            $this->paymentSchedules[] = $schedule;
            $schedule->setOrder($this);
        }

        return $this;
    }

    public function removePaymentSchedule(OrderPaymentSchedule $schedule): self
    {
        if ($this->paymentSchedules->removeElement($schedule)) {
            if ($schedule->getOrder() === $this) {
                $schedule->setOrder($this);
            }
        }

        return $this;
    }

    /**
     * Vérifie si la somme des échéances couvre 100% du total du devis.
     * Retourne [isValid(bool), totalScheduled(string)].
     */
    public function validatePaymentScheduleCoverage(): array
    {
        $total     = $this->calculateTotalFromSections();
        $scheduled = '0.00';
        foreach ($this->paymentSchedules as $s) {
            $scheduled = bcadd($scheduled, $s->computeAmount($total), 2);
        }

        return [bccomp($scheduled, $total, 2) === 0, $scheduled];
    }

    public function calculateTotalFromSections(): string
    {
        $total = '0';
        foreach ($this->sections as $section) {
            $total = bcadd($total, $section->getTotalAmount(), 2);
        }

        return $total;
    }

    /**
     * Génère un numéro de devis unique.
     */
    public static function generateOrderNumber(DateTimeInterface $date): string
    {
        $year  = $date->format('Y');
        $month = $date->format('m');

        // TODO: Implémenter la logique incrémentale en base
        // Pour l'instant, utilisation d'un timestamp pour l'unicité
        $increment = $date->format('His'); // Heures/minutes/secondes comme increment temporaire

        return "D{$year}{$month}{$increment}";
    }

    /**
     * Retourne un titre pour le devis (nom ou numéro de commande).
     */
    public function getTitle(): string
    {
        return $this->name ?: $this->orderNumber;
    }

    /**
     * Retourne la référence du devis (numéro de commande).
     */
    public function getReference(): string
    {
        return $this->orderNumber;
    }

    /**
     * Calcule le total du devis (alias pour compatibilité).
     */
    public function calculateTotal(): float
    {
        return (float) $this->calculateTotalFromSections();
    }

    // === Gestion des notes de frais ===

    public function getExpenseReports(): Collection
    {
        return $this->expenseReports;
    }

    public function addExpenseReport(ExpenseReport $expenseReport): self
    {
        if (!$this->expenseReports->contains($expenseReport)) {
            $this->expenseReports[] = $expenseReport;
            $expenseReport->setOrder($this);
        }

        return $this;
    }

    public function removeExpenseReport(ExpenseReport $expenseReport): self
    {
        if ($this->expenseReports->removeElement($expenseReport)) {
            if ($expenseReport->getOrder() === $this) {
                $expenseReport->setOrder(null);
            }
        }

        return $this;
    }

    /**
     * Calcule le total des frais validés rattachés à ce devis.
     */
    public function getTotalValidatedExpenses(): string
    {
        $total = '0.00';
        foreach ($this->expenseReports as $expense) {
            if ($expense->getStatus() === ExpenseReport::STATUS_VALIDATED || $expense->getStatus() === ExpenseReport::STATUS_PAID) {
                $total = bcadd($total, $expense->getAmountTTC(), 2);
            }
        }

        return $total;
    }

    /**
     * Calcule le montant total refacturable (frais + frais de gestion).
     */
    public function getTotalRebillableExpenses(): string
    {
        if (!$this->expensesRebillable) {
            return '0.00';
        }

        $total         = $this->getTotalValidatedExpenses();
        $feeMultiplier = bcadd('1', bcdiv($this->expenseManagementFeeRate, '100', 4), 4);

        return bcmul($total, $feeMultiplier, 2);
    }

    /**
     * Calcule les frais de gestion totaux.
     */
    public function getTotalManagementFees(): string
    {
        if (!$this->expensesRebillable) {
            return '0.00';
        }

        return bcsub($this->getTotalRebillableExpenses(), $this->getTotalValidatedExpenses(), 2);
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
     * With PHP 8.4 property hooks, prefer direct access: $order->id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $order->name.
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $order->name = $value.
     */
    public function setName(?string $value): self
    {
        $this->name = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $order->description.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $order->description = $value.
     */
    public function setDescription(?string $value): self
    {
        $this->description = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $order->contingencyPercentage.
     */
    public function getContingencyPercentage(): ?string
    {
        return $this->contingencyPercentage;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $order->contingencyPercentage = $value.
     */
    public function setContingencyPercentage(?string $value): self
    {
        $this->contingencyPercentage = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $order->validUntil.
     */
    public function getValidUntil(): ?DateTimeInterface
    {
        return $this->validUntil;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $order->validUntil = $value.
     */
    public function setValidUntil(?DateTimeInterface $value): self
    {
        $this->validUntil = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $order->orderNumber.
     */
    public function getOrderNumber(): string
    {
        return $this->orderNumber;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $order->orderNumber = $value.
     */
    public function setOrderNumber(string $value): self
    {
        $this->orderNumber = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $order->notes.
     */
    public function getNotes(): ?string
    {
        return $this->notes;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $order->notes = $value.
     */
    public function setNotes(?string $value): self
    {
        $this->notes = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $order->contingenceAmount.
     */
    public function getContingenceAmount(): ?string
    {
        return $this->contingenceAmount;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $order->contingenceAmount = $value.
     */
    public function setContingenceAmount(?string $value): self
    {
        $this->contingenceAmount = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $order->contingenceReason.
     */
    public function getContingenceReason(): ?string
    {
        return $this->contingenceReason;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $order->contingenceReason = $value.
     */
    public function setContingenceReason(?string $value): self
    {
        $this->contingenceReason = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $order->project.
     */
    public function getProject(): ?Project
    {
        return $this->project;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $order->project = $value.
     */
    public function setProject(?Project $value): self
    {
        $this->project = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $order->totalAmount.
     */
    public function getTotalAmount(): ?string
    {
        return $this->totalAmount;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $order->totalAmount = $value.
     */
    public function setTotalAmount(?string $value): self
    {
        $this->totalAmount = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $order->createdAt.
     */
    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $order->createdAt = $value.
     */
    public function setCreatedAt(DateTimeInterface $value): self
    {
        $this->createdAt = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $order->validatedAt.
     */
    public function getValidatedAt(): ?DateTimeInterface
    {
        return $this->validatedAt;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $order->validatedAt = $value.
     */
    public function setValidatedAt(?DateTimeInterface $value): self
    {
        $this->validatedAt = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $order->status.
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $order->status = $value.
     */
    public function setStatus(string $value): self
    {
        $this->status = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $order->contractType.
     */
    public function getContractType(): string
    {
        return $this->contractType;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $order->contractType = $value.
     */
    public function setContractType(string $value): self
    {
        $this->contractType = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $order->expensesRebillable.
     */
    public function getExpensesRebillable(): bool
    {
        return $this->expensesRebillable;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $order->expensesRebillable = $value.
     */
    public function setExpensesRebillable(bool $value): self
    {
        $this->expensesRebillable = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $order->expenseManagementFeeRate.
     */
    public function getExpenseManagementFeeRate(): string
    {
        return $this->expenseManagementFeeRate;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $order->expenseManagementFeeRate = $value.
     */
    public function setExpenseManagementFeeRate(string $value): self
    {
        $this->expenseManagementFeeRate = $value;

        return $this;
    }
}
