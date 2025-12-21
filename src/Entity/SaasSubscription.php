<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SaasSubscriptionRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Abonnement à un service SaaS.
 */
#[ORM\Entity(repositoryClass: SaasSubscriptionRepository::class)]
#[ORM\Table(name: 'saas_subscriptions')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(columns: ['service_id'], name: 'idx_saas_subscription_service')]
#[ORM\Index(columns: ['status'], name: 'idx_saas_subscription_status')]
#[ORM\Index(columns: ['next_renewal_date'], name: 'idx_saas_subscription_renewal')]
class SaasSubscription
{
    // Périodicités de facturation
    public const BILLING_MONTHLY = 'monthly';
    public const BILLING_YEARLY  = 'yearly';

    public const BILLING_PERIODS = [
        self::BILLING_MONTHLY => 'Mensuel',
        self::BILLING_YEARLY  => 'Annuel',
    ];

    // Statuts d'abonnement
    public const STATUS_ACTIVE    = 'active';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_EXPIRED   = 'expired';

    public const STATUSES = [
        self::STATUS_ACTIVE    => 'Actif',
        self::STATUS_CANCELLED => 'Annulé',
        self::STATUS_SUSPENDED => 'Suspendu',
        self::STATUS_EXPIRED   => 'Expiré',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Service SaaS auquel on est abonné.
     */
    #[ORM\ManyToOne(targetEntity: SaasService::class, inversedBy: 'subscriptions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?SaasService $service = null;

    /**
     * Nom personnalisé de l'abonnement (optionnel).
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $customName = null;

    /**
     * Périodicité de facturation (monthly ou yearly).
     */
    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $billingPeriod = self::BILLING_MONTHLY;

    /**
     * Prix de l'abonnement (par mois ou par an selon billingPeriod).
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $price = '0.00';

    /**
     * Devise.
     */
    #[ORM\Column(type: Types::STRING, length: 3, options: ['default' => 'EUR'])]
    private string $currency = 'EUR';

    /**
     * Nombre de licences/utilisateurs.
     */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 1])]
    private int $quantity = 1;

    /**
     * Date de début de l'abonnement.
     */
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?DateTimeInterface $startDate = null;

    /**
     * Date de fin de l'abonnement (null si actif).
     */
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?DateTimeInterface $endDate = null;

    /**
     * Date du prochain renouvellement.
     */
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?DateTimeInterface $nextRenewalDate = null;

    /**
     * Date du dernier renouvellement effectué.
     */
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?DateTimeInterface $lastRenewalDate = null;

    /**
     * Renouvellement automatique activé.
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $autoRenewal = true;

    /**
     * Statut de l'abonnement.
     */
    #[ORM\Column(type: Types::STRING, length: 20, options: ['default' => self::STATUS_ACTIVE])]
    private string $status = self::STATUS_ACTIVE;

    /**
     * Numéro de commande/référence externe (optionnel).
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $externalReference = null;

    /**
     * Notes internes sur l'abonnement.
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    /**
     * Date de création.
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $createdAt = null;

    /**
     * Date de dernière modification.
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getService(): ?SaasService
    {
        return $this->service;
    }

    public function setService(?SaasService $service): self
    {
        $this->service = $service;

        return $this;
    }

    public function getCustomName(): ?string
    {
        return $this->customName;
    }

    public function setCustomName(?string $customName): self
    {
        $this->customName = $customName;

        return $this;
    }

    public function getBillingPeriod(): string
    {
        return $this->billingPeriod;
    }

    public function setBillingPeriod(string $billingPeriod): self
    {
        $this->billingPeriod = $billingPeriod;

        return $this;
    }

    public function getPrice(): string
    {
        return $this->price;
    }

    public function setPrice(string $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;

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

    public function getNextRenewalDate(): ?DateTimeInterface
    {
        return $this->nextRenewalDate;
    }

    public function setNextRenewalDate(?DateTimeInterface $nextRenewalDate): self
    {
        $this->nextRenewalDate = $nextRenewalDate;

        return $this;
    }

    public function getLastRenewalDate(): ?DateTimeInterface
    {
        return $this->lastRenewalDate;
    }

    public function setLastRenewalDate(?DateTimeInterface $lastRenewalDate): self
    {
        $this->lastRenewalDate = $lastRenewalDate;

        return $this;
    }

    public function hasAutoRenewal(): bool
    {
        return $this->autoRenewal;
    }

    public function setAutoRenewal(bool $autoRenewal): self
    {
        $this->autoRenewal = $autoRenewal;

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

    public function getExternalReference(): ?string
    {
        return $this->externalReference;
    }

    public function setExternalReference(?string $externalReference): self
    {
        $this->externalReference = $externalReference;

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

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Calcule le coût mensuel de l'abonnement.
     */
    public function getMonthlyCost(): float
    {
        $price = (float) $this->price;

        if ($this->billingPeriod === self::BILLING_YEARLY) {
            return ($price * $this->quantity) / 12;
        }

        return $price * $this->quantity;
    }

    /**
     * Calcule le coût annuel de l'abonnement.
     */
    public function getYearlyCost(): float
    {
        $price = (float) $this->price;

        if ($this->billingPeriod === self::BILLING_MONTHLY) {
            return ($price * $this->quantity) * 12;
        }

        return $price * $this->quantity;
    }

    /**
     * Calcule la prochaine date de renouvellement en fonction de la périodicité.
     */
    public function calculateNextRenewalDate(?DateTimeInterface $fromDate = null): DateTimeInterface
    {
        $date     = $fromDate ?? $this->nextRenewalDate ?? $this->startDate ?? new DateTime();
        $nextDate = clone $date;

        if ($this->billingPeriod === self::BILLING_MONTHLY) {
            $nextDate->modify('+1 month');
        } else {
            $nextDate->modify('+1 year');
        }

        return $nextDate;
    }

    /**
     * Renouvelle l'abonnement et met à jour les dates.
     */
    public function renew(): self
    {
        $this->lastRenewalDate = clone $this->nextRenewalDate;
        $this->nextRenewalDate = $this->calculateNextRenewalDate();

        return $this;
    }

    /**
     * Retourne true si l'abonnement est actif.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Retourne true si l'abonnement doit être renouvelé (date dépassée et auto-renewal activé).
     */
    public function shouldBeRenewed(): bool
    {
        if (!$this->isActive() || !$this->autoRenewal || !$this->nextRenewalDate) {
            return false;
        }

        $today = new DateTime();
        $today->setTime(0, 0, 0);

        return $this->nextRenewalDate <= $today;
    }

    /**
     * Annule l'abonnement.
     */
    public function cancel(?DateTimeInterface $endDate = null): self
    {
        $this->status      = self::STATUS_CANCELLED;
        $this->endDate     = $endDate ?? new DateTime();
        $this->autoRenewal = false;

        return $this;
    }

    /**
     * Suspend l'abonnement.
     */
    public function suspend(): self
    {
        $this->status      = self::STATUS_SUSPENDED;
        $this->autoRenewal = false;

        return $this;
    }

    /**
     * Réactive l'abonnement.
     */
    public function reactivate(): self
    {
        $this->status  = self::STATUS_ACTIVE;
        $this->endDate = null;

        return $this;
    }

    /**
     * Retourne le nom d'affichage de l'abonnement.
     */
    public function getDisplayName(): string
    {
        if ($this->customName) {
            return $this->customName;
        }

        return $this->service ? $this->service->getName() : 'Abonnement';
    }

    public function __toString(): string
    {
        return $this->getDisplayName();
    }
}
