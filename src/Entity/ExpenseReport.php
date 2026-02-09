<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interface\CompanyOwnedInterface;
use App\Repository\ExpenseReportRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ExpenseReportRepository::class)]
#[ORM\Table(name: 'expense_reports', indexes: [
    new ORM\Index(name: 'idx_expense_status', columns: ['status']),
    new ORM\Index(name: 'idx_expense_date', columns: ['expense_date']),
    new ORM\Index(name: 'idx_expense_contributor', columns: ['contributor_id']),
    new ORM\Index(name: 'idx_expensereport_company', columns: ['company_id']),
])]
#[ORM\HasLifecycleCallbacks]
class ExpenseReport implements CompanyOwnedInterface
{
    public const STATUS_DRAFT     = 'draft';
    public const STATUS_PENDING   = 'pending';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_REJECTED  = 'rejected';
    public const STATUS_PAID      = 'paid';

    public const STATUSES = [
        self::STATUS_DRAFT     => 'Brouillon',
        self::STATUS_PENDING   => 'En attente',
        self::STATUS_VALIDATED => 'Validé',
        self::STATUS_REJECTED  => 'Refusé',
        self::STATUS_PAID      => 'Remboursé',
    ];

    public const CATEGORY_TRANSPORT     = 'transport';
    public const CATEGORY_MEAL          = 'meal';
    public const CATEGORY_ACCOMMODATION = 'accommodation';
    public const CATEGORY_EQUIPMENT     = 'equipment';
    public const CATEGORY_TRAINING      = 'training';
    public const CATEGORY_OTHER         = 'other';

    public const CATEGORIES = [
        self::CATEGORY_TRANSPORT     => 'Transport',
        self::CATEGORY_MEAL          => 'Repas',
        self::CATEGORY_ACCOMMODATION => 'Hébergement',
        self::CATEGORY_EQUIPMENT     => 'Matériel',
        self::CATEGORY_TRAINING      => 'Formation',
        self::CATEGORY_OTHER         => 'Autre',
    ];

    public const CATEGORY_ICONS = [
        self::CATEGORY_TRANSPORT     => 'mdi mdi-car',
        self::CATEGORY_MEAL          => 'mdi mdi-food',
        self::CATEGORY_ACCOMMODATION => 'mdi mdi-bed',
        self::CATEGORY_EQUIPMENT     => 'mdi mdi-desktop-mac',
        self::CATEGORY_TRAINING      => 'mdi mdi-school',
        self::CATEGORY_OTHER         => 'mdi mdi-file-document',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private Company $company;

    #[ORM\ManyToOne(targetEntity: Contributor::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'Le contributeur est obligatoire')]
    private Contributor $contributor;

    #[ORM\Column(type: 'date')]
    #[Assert\NotNull(message: 'La date du frais est obligatoire')]
    private DateTimeInterface $expenseDate;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank(message: 'La catégorie est obligatoire')]
    #[Assert\Choice(choices: [
        self::CATEGORY_TRANSPORT,
        self::CATEGORY_MEAL,
        self::CATEGORY_ACCOMMODATION,
        self::CATEGORY_EQUIPMENT,
        self::CATEGORY_TRAINING,
        self::CATEGORY_OTHER,
    ])]
    private string $category;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'La description est obligatoire')]
    #[Assert\Length(min: 3, max: 1000)]
    private string $description;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotNull(message: 'Le montant HT est obligatoire')]
    #[Assert\Positive(message: 'Le montant HT doit être positif')]
    private string $amountHT;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2)]
    #[Assert\NotNull]
    #[Assert\Choice(choices: ['0.00', '5.50', '10.00', '20.00'])]
    private string $vatRate = '20.00';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $amountTTC;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Project $project = null;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'expenseReports')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Order $order = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = self::STATUS_DRAFT;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $filePath = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $validator = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTimeInterface $validatedAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $validationComment = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTimeInterface $paidAt = null;

    #[ORM\Column(type: 'datetime')]
    private DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime')]
    private DateTimeInterface $updatedAt;

    public function __construct()
    {
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new DateTime();
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

    public function getExpenseDate(): DateTimeInterface
    {
        return $this->expenseDate;
    }

    public function setExpenseDate(DateTimeInterface $expenseDate): self
    {
        $this->expenseDate = $expenseDate;

        return $this;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getCategoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function getCategoryIcon(): string
    {
        return self::CATEGORY_ICONS[$this->category] ?? 'mdi mdi-file';
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getAmountHT(): string
    {
        return $this->amountHT;
    }

    public function setAmountHT(string $amountHT): self
    {
        $this->amountHT = $amountHT;
        $this->calculateAmountTTC();

        return $this;
    }

    public function getVatRate(): string
    {
        return $this->vatRate;
    }

    public function setVatRate(string $vatRate): self
    {
        $this->vatRate = $vatRate;
        $this->calculateAmountTTC();

        return $this;
    }

    public function getAmountTTC(): string
    {
        return $this->amountTTC;
    }

    /**
     * Calcule automatiquement le montant TTC à partir du HT et du taux de TVA.
     */
    private function calculateAmountTTC(): void
    {
        if (isset($this->amountHT) && isset($this->vatRate)) {
            $vatMultiplier   = bcadd('1', bcdiv($this->vatRate, '100', 4), 4);
            $this->amountTTC = bcmul($this->amountHT, $vatMultiplier, 2);
        }
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): self
    {
        $this->project = $project;

        return $this;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): self
    {
        $this->order = $order;

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

    public function getStatusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT     => 'badge-secondary',
            self::STATUS_PENDING   => 'badge-warning',
            self::STATUS_VALIDATED => 'badge-success',
            self::STATUS_REJECTED  => 'badge-danger',
            self::STATUS_PAID      => 'badge-info',
            default                => 'badge-light',
        };
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(?string $filePath): self
    {
        $this->filePath = $filePath;

        return $this;
    }

    public function getValidator(): ?User
    {
        return $this->validator;
    }

    public function setValidator(?User $validator): self
    {
        $this->validator = $validator;

        return $this;
    }

    public function getValidatedAt(): ?DateTimeInterface
    {
        return $this->validatedAt;
    }

    public function setValidatedAt(?DateTimeInterface $validatedAt): self
    {
        $this->validatedAt = $validatedAt;

        return $this;
    }

    public function getValidationComment(): ?string
    {
        return $this->validationComment;
    }

    public function setValidationComment(?string $validationComment): self
    {
        $this->validationComment = $validationComment;

        return $this;
    }

    public function getPaidAt(): ?DateTimeInterface
    {
        return $this->paidAt;
    }

    public function setPaidAt(?DateTimeInterface $paidAt): self
    {
        $this->paidAt = $paidAt;

        return $this;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Vérifie si le frais est modifiable.
     * Seuls les frais en brouillon peuvent être modifiés.
     */
    public function isEditable(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Vérifie si le frais peut être soumis pour validation.
     */
    public function canBeSubmitted(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Vérifie si le frais peut être validé.
     */
    public function canBeValidated(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Vérifie si le frais peut être rejeté.
     */
    public function canBeRejected(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Vérifie si le frais peut être marqué comme payé.
     */
    public function canBeMarkedAsPaid(): bool
    {
        return $this->status === self::STATUS_VALIDATED;
    }

    /**
     * Vérifie si le frais est refacturable au client.
     */
    public function isRebillable(): bool
    {
        return $this->order !== null && $this->order->expensesRebillable;
    }

    /**
     * Calcule le montant refacturable avec frais de gestion.
     */
    public function getRebillableAmount(): string
    {
        if (!$this->isRebillable()) {
            return '0.00';
        }

        $feeRate       = $this->order->getExpenseManagementFeeRate();
        $feeMultiplier = bcadd('1', bcdiv($feeRate, '100', 4), 4);

        return bcmul($this->amountTTC, $feeMultiplier, 2);
    }

    /**
     * Calcule les frais de gestion.
     */
    public function getManagementFee(): string
    {
        if (!$this->isRebillable()) {
            return '0.00';
        }

        return bcsub($this->getRebillableAmount(), $this->amountTTC, 2);
    }

    public function setAmountTTC(string $amountTTC): static
    {
        $this->amountTTC = $amountTTC;

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
}
