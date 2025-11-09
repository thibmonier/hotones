<?php

namespace App\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\OrderRepository::class)]
#[ORM\Table(name: 'orders', indexes: [
    new ORM\Index(name: 'idx_order_project', columns: ['project_id']),
    new ORM\Index(name: 'idx_order_status', columns: ['status']),
    new ORM\Index(name: 'idx_order_created_at', columns: ['created_at']),
])]
class Order
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
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 180, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, nullable: true)]
    private ?string $contingencyPercentage = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?DateTimeInterface $validUntil = null;

    // Numéro unique du devis D[année][mois][numéro incrémental]
    #[ORM\Column(type: 'string', length: 50, unique: true)]
    private string $orderNumber;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    // Contingence (retenue sur la marge)
    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true)]
    private ?string $contingenceAmount = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $contingenceReason = null;

    // Relations
    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project = null;
    // Montant total HT du devis
    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true)]
    private ?string $totalAmount = '0.00';

    #[ORM\Column(type: 'date')]
    private DateTimeInterface $createdAt;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?DateTimeInterface $validatedAt = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'a_signer'; // a_signer, gagne, signe, perdu, termine, standby, abandonne

    // Type de contractualisation du devis: forfait (échéancier) ou regie (temps passé)
    #[ORM\Column(type: 'string', length: 20, options: ['default' => 'forfait'])]
    private string $contractType = 'forfait'; // forfait, regie

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

    public function __construct()
    {
        $this->tasks            = new ArrayCollection();
        $this->sections         = new ArrayCollection();
        $this->paymentSchedules = new ArrayCollection();
        $this->createdAt        = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
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

    public function getContingencyPercentage(): ?string
    {
        return $this->contingencyPercentage;
    }

    public function setContingencyPercentage(?string $contingencyPercentage): self
    {
        $this->contingencyPercentage = $contingencyPercentage;

        return $this;
    }

    public function getValidUntil(): ?DateTimeInterface
    {
        return $this->validUntil;
    }

    public function setValidUntil(?DateTimeInterface $validUntil): self
    {
        $this->validUntil = $validUntil;

        return $this;
    }

    public function getOrderNumber(): string
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(string $orderNumber): self
    {
        $this->orderNumber = $orderNumber;

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

    public function getContingenceAmount(): ?string
    {
        return $this->contingenceAmount;
    }

    public function setContingenceAmount(?string $contingenceAmount): self
    {
        $this->contingenceAmount = $contingenceAmount;

        return $this;
    }

    public function getContingenceReason(): ?string
    {
        return $this->contingenceReason;
    }

    public function setContingenceReason(?string $contingenceReason): self
    {
        $this->contingenceReason = $contingenceReason;

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

    public function getTotalAmount(): string
    {
        return $this->totalAmount ?? '0.00';
    }

    public function setTotalAmount(?string $totalAmount): self
    {
        $this->totalAmount = $totalAmount ?? '0.00';

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

    public function getValidatedAt(): ?DateTimeInterface
    {
        return $this->validatedAt;
    }

    public function setValidatedAt(?DateTimeInterface $validatedAt): self
    {
        $this->validatedAt = $validatedAt;

        return $this;
    }

    public function getContractType(): string
    {
        return $this->contractType;
    }

    public function setContractType(string $contractType): self
    {
        $this->contractType = $contractType;

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
}
