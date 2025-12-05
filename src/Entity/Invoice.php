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
use App\Repository\InvoiceRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Facture (Invoice).
 *
 * Générée automatiquement depuis :
 * - Devis forfait signés (via OrderPaymentSchedule)
 * - Temps régie saisis (calcul mensuel)
 */
#[ORM\Entity(repositoryClass: InvoiceRepository::class)]
#[ORM\Table(name: 'invoices', indexes: [
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
class Invoice
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
    private ?int $id = null;

    /**
     * Numéro unique de facture : F[année][mois][incrément].
     * Exemple : F202501001, F202501002, etc.
     */
    #[ORM\Column(type: 'string', length: 50, unique: true)]
    #[Groups(['invoice:read'])]
    private string $invoiceNumber;

    #[ORM\Column(type: 'string', length: 20)]
    #[Groups(['invoice:read', 'invoice:write'])]
    private string $status = self::STATUS_DRAFT;

    /**
     * Date d'émission de la facture.
     */
    #[ORM\Column(type: 'date')]
    #[Groups(['invoice:read', 'invoice:write'])]
    private DateTimeInterface $issuedAt;

    /**
     * Date d'échéance de paiement.
     */
    #[ORM\Column(type: 'date')]
    #[Groups(['invoice:read', 'invoice:write'])]
    private DateTimeInterface $dueDate;

    /**
     * Date effective de paiement (null si non payée).
     */
    #[ORM\Column(type: 'date', nullable: true)]
    #[Groups(['invoice:read', 'invoice:write'])]
    private ?DateTimeInterface $paidAt = null;

    /**
     * Montant total HT.
     */
    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    #[Groups(['invoice:read', 'invoice:write'])]
    private string $amountHt = '0.00';

    /**
     * Montant TVA.
     */
    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    #[Groups(['invoice:read', 'invoice:write'])]
    private string $amountTva = '0.00';

    /**
     * Taux de TVA appliqué (%).
     */
    #[ORM\Column(type: 'decimal', precision: 5, scale: 2)]
    #[Groups(['invoice:read', 'invoice:write'])]
    private string $tvaRate = '20.00';

    /**
     * Montant total TTC.
     */
    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    #[Groups(['invoice:read', 'invoice:write'])]
    private string $amountTtc = '0.00';

    /**
     * Notes internes (non affichées sur le PDF).
     */
    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['invoice:read', 'invoice:write'])]
    private ?string $internalNotes = null;

    /**
     * Conditions de paiement (affichées sur le PDF).
     */
    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['invoice:read', 'invoice:write'])]
    private ?string $paymentTerms = null;

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
    private DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime')]
    private DateTimeInterface $updatedAt;

    public function __construct()
    {
        $this->lines     = new ArrayCollection();
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
        $this->issuedAt  = new DateTime();
        $this->dueDate   = (new DateTime())->modify('+30 days'); // Échéance par défaut : 30 jours
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

    // Getters & Setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInvoiceNumber(): string
    {
        return $this->invoiceNumber;
    }

    public function setInvoiceNumber(string $invoiceNumber): self
    {
        $this->invoiceNumber = $invoiceNumber;

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

    public function getIssuedAt(): DateTimeInterface
    {
        return $this->issuedAt;
    }

    public function setIssuedAt(DateTimeInterface $issuedAt): self
    {
        $this->issuedAt = $issuedAt;

        return $this;
    }

    public function getDueDate(): DateTimeInterface
    {
        return $this->dueDate;
    }

    public function setDueDate(DateTimeInterface $dueDate): self
    {
        $this->dueDate = $dueDate;

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

    public function getAmountHt(): string
    {
        return $this->amountHt;
    }

    public function setAmountHt(string $amountHt): self
    {
        $this->amountHt = $amountHt;

        return $this;
    }

    public function getAmountTva(): string
    {
        return $this->amountTva;
    }

    public function setAmountTva(string $amountTva): self
    {
        $this->amountTva = $amountTva;

        return $this;
    }

    public function getTvaRate(): string
    {
        return $this->tvaRate;
    }

    public function setTvaRate(string $tvaRate): self
    {
        $this->tvaRate = $tvaRate;

        return $this;
    }

    public function getAmountTtc(): string
    {
        return $this->amountTtc;
    }

    public function setAmountTtc(string $amountTtc): self
    {
        $this->amountTtc = $amountTtc;

        return $this;
    }

    public function getInternalNotes(): ?string
    {
        return $this->internalNotes;
    }

    public function setInternalNotes(?string $internalNotes): self
    {
        $this->internalNotes = $internalNotes;

        return $this;
    }

    public function getPaymentTerms(): ?string
    {
        return $this->paymentTerms;
    }

    public function setPaymentTerms(?string $paymentTerms): self
    {
        $this->paymentTerms = $paymentTerms;

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

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeInterface
    {
        return $this->updatedAt;
    }
}
