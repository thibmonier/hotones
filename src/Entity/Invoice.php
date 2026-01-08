<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Entity\Interface\CompanyOwnedInterface;
use App\Repository\InvoiceRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Facture (Invoice).
 *
 * Générée automatiquement depuis :
 * - Devis forfait signés (via OrderPaymentSchedule)
 * - Temps régie saisis (calcul mensuel)
 */
#[ORM\Entity(repositoryClass: InvoiceRepository::class)]
#[ORM\Table(name: 'invoices', indexes: [
    new ORM\Index(name: 'idx_invoice_company', columns: ['company_id']),
    new ORM\Index(name: 'idx_invoice_number', columns: ['invoice_number']),
    new ORM\Index(name: 'idx_invoice_status', columns: ['status']),
    new ORM\Index(name: 'idx_invoice_issued_at', columns: ['issued_at']),
    new ORM\Index(name: 'idx_invoice_due_date', columns: ['due_date']),
    new ORM\Index(name: 'idx_invoice_paid_at', columns: ['paid_at']),
    new ORM\Index(name: 'idx_invoice_client', columns: ['client_id']),
])]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new Get(security: "is_granted('ROLE_USER')"),
        new GetCollection(security: "is_granted('ROLE_USER')"),
        new Post(security: "is_granted('ROLE_COMPTA')"),
        new Put(security: "is_granted('ROLE_COMPTA')"),
        new Patch(security: "is_granted('ROLE_COMPTA')"),
        new Delete(security: "is_granted('ROLE_ADMIN')"),
    ],
    normalizationContext: ['groups' => ['invoice:read']],
    denormalizationContext: ['groups' => ['invoice:write']],
    paginationItemsPerPage: 30,
)]
class Invoice implements CompanyOwnedInterface
{
    public const STATUS_DRAFT     = 'brouillon';
    public const STATUS_SENT      = 'envoyee';
    public const STATUS_PAID      = 'payee';
    public const STATUS_OVERDUE   = 'en_retard';
    public const STATUS_CANCELLED = 'annulee';

    public const STATUS_OPTIONS = [
        self::STATUS_DRAFT     => 'Brouillon',
        self::STATUS_SENT      => 'Envoyée',
        self::STATUS_PAID      => 'Payée',
        self::STATUS_OVERDUE   => 'En retard',
        self::STATUS_CANCELLED => 'Annulée',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['invoice:read'])]
    public private(set) ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private Company $company;

    /**
     * Numéro unique de facture : F[année][mois][incrément].
     * Exemple : F202501001, F202501002, etc.
     */
    #[ORM\Column(type: 'string', length: 50, unique: true)]
    #[Groups(['invoice:read'])]
    public string $invoiceNumber {
        get => $this->invoiceNumber;
        set {
            $this->invoiceNumber = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 20)]
    #[Groups(['invoice:read', 'invoice:write'])]
    public string $status = self::STATUS_DRAFT {
        get => $this->status;
        set {
            $this->status = $value;
        }
    }

    /**
     * Date d'émission de la facture.
     */
    #[ORM\Column(type: 'date')]
    #[Groups(['invoice:read', 'invoice:write'])]
    public DateTimeInterface $issuedAt {
        get => $this->issuedAt;
        set {
            $this->issuedAt = $value;
        }
    }

    /**
     * Date d'échéance de paiement.
     */
    #[ORM\Column(type: 'date')]
    #[Groups(['invoice:read', 'invoice:write'])]
    public DateTimeInterface $dueDate {
        get => $this->dueDate;
        set {
            $this->dueDate = $value;
        }
    }

    /**
     * Date effective de paiement (null si non payée).
     */
    #[ORM\Column(type: 'date', nullable: true)]
    #[Groups(['invoice:read', 'invoice:write'])]
    public ?DateTimeInterface $paidAt = null {
        get => $this->paidAt;
        set {
            $this->paidAt = $value;
        }
    }

    /**
     * Montant total HT.
     */
    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    #[Groups(['invoice:read', 'invoice:write'])]
    public string $amountHt = '0.00' {
        get => $this->amountHt;
        set {
            $this->amountHt = $value;
        }
    }

    /**
     * Montant TVA.
     */
    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    #[Groups(['invoice:read', 'invoice:write'])]
    public string $amountTva = '0.00' {
        get => $this->amountTva;
        set {
            $this->amountTva = $value;
        }
    }

    /**
     * Taux de TVA appliqué (%).
     */
    #[ORM\Column(type: 'decimal', precision: 5, scale: 2)]
    #[Groups(['invoice:read', 'invoice:write'])]
    public string $tvaRate = '20.00' {
        get => $this->tvaRate;
        set {
            $this->tvaRate = $value;
        }
    }

    /**
     * Montant total TTC.
     */
    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    #[Groups(['invoice:read', 'invoice:write'])]
    public string $amountTtc = '0.00' {
        get => $this->amountTtc;
        set {
            $this->amountTtc = $value;
        }
    }

    /**
     * Notes internes (non affichées sur le PDF).
     */
    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['invoice:read', 'invoice:write'])]
    public ?string $internalNotes = null {
        get => $this->internalNotes;
        set {
            $this->internalNotes = $value;
        }
    }

    /**
     * Conditions de paiement (affichées sur le PDF).
     */
    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['invoice:read', 'invoice:write'])]
    public ?string $paymentTerms = null {
        get => $this->paymentTerms;
        set {
            $this->paymentTerms = $value;
        }
    }

    // Relations
    /**
     * Devis source (pour factures forfait).
     */
    #[ORM\ManyToOne(targetEntity: Order::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['invoice:read', 'invoice:write'])]
    private ?Order $order = null;

    /**
     * Projet associé.
     */
    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['invoice:read', 'invoice:write'])]
    private ?Project $project = null;

    /**
     * Client facturé.
     */
    #[ORM\ManyToOne(targetEntity: Client::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['invoice:read', 'invoice:write'])]
    private Client $client;

    /**
     * Lignes de facturation.
     *
     * @var Collection<int, InvoiceLine>
     */
    #[ORM\OneToMany(targetEntity: InvoiceLine::class, mappedBy: 'invoice', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['invoice:read', 'invoice:write'])]
    private Collection $lines;

    /**
     * Échéance forfait source (optionnel).
     */
    #[ORM\ManyToOne(targetEntity: OrderPaymentSchedule::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?OrderPaymentSchedule $paymentSchedule = null;

    // Timestamps
    #[ORM\Column(type: 'datetime')]
    public DateTimeInterface $createdAt {
        get => $this->createdAt;
        set {
            $this->createdAt = $value;
        }
    }

    #[ORM\Column(type: 'datetime')]
    public DateTimeInterface $updatedAt {
        get => $this->updatedAt;
        set {
            $this->updatedAt = $value;
        }
    }

    public function __construct()
    {
        $this->lines     = new ArrayCollection();
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
        $this->issuedAt  = new DateTime();
        $this->dueDate   = new DateTime()->modify('+30 days'); // Échéance par défaut : 30 jours
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        if (!isset($this->invoiceNumber)) {
            $this->invoiceNumber = $this->generateInvoiceNumber();
        }
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new DateTime();
    }

    /**
     * Génère un numéro de facture unique : F[année][mois][incrément].
     * Note : L'incrément devra être géré par le service pour garantir l'unicité.
     */
    private function generateInvoiceNumber(): string
    {
        $year  = $this->issuedAt->format('Y');
        $month = $this->issuedAt->format('m');

        return sprintf('F%s%s000', $year, $month);
    }

    /**
     * Calcule les montants TVA et TTC à partir du HT.
     */
    public function calculateAmounts(): self
    {
        $this->amountTva = bcmul($this->amountHt, bcdiv($this->tvaRate, '100', 4), 2);
        $this->amountTtc = bcadd($this->amountHt, $this->amountTva, 2);

        return $this;
    }

    /**
     * Vérifie si la facture est en retard.
     */
    public function isOverdue(): bool
    {
        if ($this->status === self::STATUS_PAID || $this->status === self::STATUS_CANCELLED) {
            return false;
        }

        return $this->dueDate < new DateTime();
    }

    /**
     * Marque la facture comme payée.
     */
    public function markAsPaid(?DateTimeInterface $paidAt = null): self
    {
        $this->status = self::STATUS_PAID;
        $this->paidAt = $paidAt ?? new DateTime();

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

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): self
    {
        $this->project = $project;

        return $this;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function setClient(Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @return Collection<int, InvoiceLine>
     */
    public function getLines(): Collection
    {
        return $this->lines;
    }

    public function addLine(InvoiceLine $line): self
    {
        if (!$this->lines->contains($line)) {
            $this->lines[] = $line;
            $line->setInvoice($this);
        }

        return $this;
    }

    public function removeLine(InvoiceLine $line): self
    {
        if ($this->lines->removeElement($line)) {
            if ($line->getInvoice() === $this) {
                $line->setInvoice(null);
            }
        }

        return $this;
    }

    public function getPaymentSchedule(): ?OrderPaymentSchedule
    {
        return $this->paymentSchedule;
    }

    public function setPaymentSchedule(?OrderPaymentSchedule $paymentSchedule): self
    {
        $this->paymentSchedule = $paymentSchedule;

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
     * With PHP 8.4 public private(set), prefer direct access: $invoice->id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $invoice->invoiceNumber.
     */
    public function getInvoiceNumber(): string
    {
        return $this->invoiceNumber;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $invoice->invoiceNumber = $value.
     */
    public function setInvoiceNumber(string $value): self
    {
        $this->invoiceNumber = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $invoice->status.
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $invoice->status = $value.
     */
    public function setStatus(string $value): self
    {
        $this->status = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $invoice->issuedAt.
     */
    public function getIssuedAt(): DateTimeInterface
    {
        return $this->issuedAt;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $invoice->issuedAt = $value.
     */
    public function setIssuedAt(DateTimeInterface $value): self
    {
        $this->issuedAt = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $invoice->dueDate.
     */
    public function getDueDate(): DateTimeInterface
    {
        return $this->dueDate;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $invoice->dueDate = $value.
     */
    public function setDueDate(DateTimeInterface $value): self
    {
        $this->dueDate = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $invoice->paidAt.
     */
    public function getPaidAt(): ?DateTimeInterface
    {
        return $this->paidAt;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $invoice->paidAt = $value.
     */
    public function setPaidAt(?DateTimeInterface $value): self
    {
        $this->paidAt = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $invoice->amountHt.
     */
    public function getAmountHt(): string
    {
        return $this->amountHt;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $invoice->amountHt = $value.
     */
    public function setAmountHt(string $value): self
    {
        $this->amountHt = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $invoice->amountTva.
     */
    public function getAmountTva(): string
    {
        return $this->amountTva;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $invoice->amountTva = $value.
     */
    public function setAmountTva(string $value): self
    {
        $this->amountTva = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $invoice->tvaRate.
     */
    public function getTvaRate(): string
    {
        return $this->tvaRate;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $invoice->tvaRate = $value.
     */
    public function setTvaRate(string $value): self
    {
        $this->tvaRate = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $invoice->amountTtc.
     */
    public function getAmountTtc(): string
    {
        return $this->amountTtc;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $invoice->amountTtc = $value.
     */
    public function setAmountTtc(string $value): self
    {
        $this->amountTtc = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $invoice->internalNotes.
     */
    public function getInternalNotes(): ?string
    {
        return $this->internalNotes;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $invoice->internalNotes = $value.
     */
    public function setInternalNotes(?string $value): self
    {
        $this->internalNotes = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $invoice->paymentTerms.
     */
    public function getPaymentTerms(): ?string
    {
        return $this->paymentTerms;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $invoice->paymentTerms = $value.
     */
    public function setPaymentTerms(?string $value): self
    {
        $this->paymentTerms = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $invoice->createdAt.
     */
    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $invoice->createdAt = $value.
     */
    public function setCreatedAt(DateTime $value): static
    {
        $this->createdAt = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $invoice->updatedAt.
     */
    public function getUpdatedAt(): DateTimeInterface
    {
        return $this->updatedAt;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $invoice->updatedAt = $value.
     */
    public function setUpdatedAt(DateTime $value): static
    {
        $this->updatedAt = $value;

        return $this;
    }
}
