<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interface\CompanyOwnedInterface;
use App\Repository\AccountDeletionRequestRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use SensitiveParameter;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Demande de suppression de compte (droit à l'oubli RGPD).
 * Workflow: demande → email confirmation → période de grâce 30j → suppression.
 */
#[ORM\Entity(repositoryClass: AccountDeletionRequestRepository::class)]
#[ORM\Table(name: 'account_deletion_requests')]
#[ORM\Index(columns: ['user_id'], name: 'idx_deletion_request_user')]
#[ORM\Index(columns: ['status'], name: 'idx_deletion_request_status')]
#[ORM\Index(columns: ['scheduled_deletion_at'], name: 'idx_deletion_scheduled')]
#[ORM\Index(name: 'idx_accountdeletionrequest_company', columns: ['company_id'])]
#[ORM\HasLifecycleCallbacks]
class AccountDeletionRequest implements CompanyOwnedInterface
{
    public const STATUS_PENDING   = 'pending';           // En attente de confirmation email
    public const STATUS_CONFIRMED = 'confirmed';       // Confirmé, période de grâce en cours
    public const STATUS_CANCELLED = 'cancelled';       // Annulé par l'utilisateur
    public const STATUS_COMPLETED = 'completed';       // Suppression effectuée

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private Company $company;

    /**
     * Utilisateur concerné par la demande.
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    /**
     * Statut de la demande.
     */
    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $status = self::STATUS_PENDING;

    /**
     * Token de confirmation (envoyé par email).
     */
    #[ORM\Column(type: Types::STRING, length: 64, unique: true)]
    #[Ignore]
    private string $confirmationToken;

    /**
     * Date de création de la demande.
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?DateTimeImmutable $requestedAt = null;

    /**
     * Date de confirmation par email.
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $confirmedAt = null;

    /**
     * Date prévue de suppression définitive (30 jours après confirmation).
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $scheduledDeletionAt = null;

    /**
     * Date d'annulation (si l'utilisateur change d'avis).
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $cancelledAt = null;

    /**
     * Date de suppression effective du compte.
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $completedAt = null;

    /**
     * Raison de la demande (optionnel, pour amélioration continue).
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $reason = null;

    /**
     * Adresse IP de la demande.
     */
    #[ORM\Column(type: Types::STRING, length: 45, nullable: true)]
    private ?string $ipAddress = null;

    public function __construct()
    {
        $this->confirmationToken = bin2hex(random_bytes(32));
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->requestedAt = new DateTimeImmutable();
    }

    // Getters and Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getConfirmationToken(): string
    {
        return $this->confirmationToken;
    }

    public function setConfirmationToken(#[SensitiveParameter] string $confirmationToken): static
    {
        $this->confirmationToken = $confirmationToken;

        return $this;
    }

    public function getRequestedAt(): ?DateTimeImmutable
    {
        return $this->requestedAt;
    }

    public function getConfirmedAt(): ?DateTimeImmutable
    {
        return $this->confirmedAt;
    }

    public function setConfirmedAt(?DateTimeImmutable $confirmedAt): static
    {
        $this->confirmedAt = $confirmedAt;

        return $this;
    }

    public function getScheduledDeletionAt(): ?DateTimeImmutable
    {
        return $this->scheduledDeletionAt;
    }

    public function setScheduledDeletionAt(?DateTimeImmutable $scheduledDeletionAt): static
    {
        $this->scheduledDeletionAt = $scheduledDeletionAt;

        return $this;
    }

    public function getCancelledAt(): ?DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    public function setCancelledAt(?DateTimeImmutable $cancelledAt): static
    {
        $this->cancelledAt = $cancelledAt;

        return $this;
    }

    public function getCompletedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?DateTimeImmutable $completedAt): static
    {
        $this->completedAt = $completedAt;

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): static
    {
        $this->reason = $reason;

        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    /**
     * Confirme la demande de suppression et planifie la suppression dans 30 jours.
     */
    public function confirm(): void
    {
        $this->status              = self::STATUS_CONFIRMED;
        $this->confirmedAt         = new DateTimeImmutable();
        $this->scheduledDeletionAt = new DateTimeImmutable('+30 days');
    }

    /**
     * Annule la demande de suppression.
     */
    public function cancel(): void
    {
        $this->status      = self::STATUS_CANCELLED;
        $this->cancelledAt = new DateTimeImmutable();
    }

    /**
     * Marque la suppression comme effectuée.
     */
    public function complete(): void
    {
        $this->status      = self::STATUS_COMPLETED;
        $this->completedAt = new DateTimeImmutable();
    }

    /**
     * Vérifie si la demande est en période de grâce (confirmée mais pas encore supprimée).
     */
    public function isInGracePeriod(): bool
    {
        return self::STATUS_CONFIRMED === $this->status
            && null !== $this->scheduledDeletionAt
            && $this->scheduledDeletionAt > new DateTimeImmutable();
    }

    /**
     * Vérifie si la suppression doit être exécutée (période de grâce expirée).
     */
    public function isDeletionDue(): bool
    {
        return self::STATUS_CONFIRMED === $this->status
            && null !== $this->scheduledDeletionAt
            && $this->scheduledDeletionAt <= new DateTimeImmutable();
    }

    /**
     * Vérifie si le token de confirmation a expiré (48h après demande).
     */
    public function isConfirmationTokenExpired(): bool
    {
        if (!$this->requestedAt) {
            return true;
        }

        $expiryDate = $this->requestedAt->modify('+48 hours');

        return $expiryDate < new DateTimeImmutable();
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
