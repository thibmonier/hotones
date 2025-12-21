<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\LeadCaptureRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Lead capturé via un formulaire de téléchargement (lead magnet).
 */
#[ORM\Entity(repositoryClass: LeadCaptureRepository::class)]
#[ORM\Table(name: 'lead_captures')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(columns: ['email'], name: 'idx_lead_email')]
#[ORM\Index(columns: ['source'], name: 'idx_lead_source')]
#[ORM\Index(columns: ['created_at'], name: 'idx_lead_created_at')]
class LeadCapture
{
    // Sources possibles
    public const SOURCE_HOMEPAGE  = 'homepage';
    public const SOURCE_PRICING   = 'pricing';
    public const SOURCE_ANALYTICS = 'analytics';
    public const SOURCE_FEATURES  = 'features';
    public const SOURCE_CONTACT   = 'contact';
    public const SOURCE_OTHER     = 'other';

    // Statuts du lead
    public const STATUS_NEW       = 'new';
    public const STATUS_NURTURING = 'nurturing';
    public const STATUS_QUALIFIED = 'qualified';
    public const STATUS_CONVERTED = 'converted';
    public const STATUS_LOST      = 'lost';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $email = '';

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $firstName = '';

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $lastName = '';

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $company = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private ?string $phone = null;

    /**
     * Source de provenance du lead (homepage, pricing, analytics, etc.).
     */
    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $source = self::SOURCE_OTHER;

    /**
     * Type de contenu téléchargé (guide-kpis, etc.).
     */
    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $contentType = 'guide-kpis';

    /**
     * Date et heure du téléchargement effectif (null si pas encore téléchargé).
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $downloadedAt = null;

    /**
     * Nombre de fois que le lead a téléchargé le contenu.
     */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $downloadCount = 0;

    /**
     * Consentement RGPD pour recevoir des emails marketing.
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $marketingConsent = false;

    /**
     * Notes internes (non visibles par le lead).
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $internalNotes = null;

    /**
     * Statut du lead dans le funnel.
     */
    #[ORM\Column(type: Types::STRING, length: 50, options: ['default' => self::STATUS_NEW])]
    private string $status = self::STATUS_NEW;

    /**
     * Date d'envoi de l'email de nurturing J+1.
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $nurturingDay1SentAt = null;

    /**
     * Date d'envoi de l'email de nurturing J+3.
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $nurturingDay3SentAt = null;

    /**
     * Date d'envoi de l'email de nurturing J+7.
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $nurturingDay7SentAt = null;

    /**
     * Date de création du lead.
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $createdAt = null;

    /**
     * Date de dernière mise à jour.
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new DateTime();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        if ($this->createdAt === null) {
            $this->createdAt = new DateTime();
        }
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

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getFullName(): string
    {
        return trim($this->firstName.' '.$this->lastName);
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setCompany(?string $company): self
    {
        $this->company = $company;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function setSource(string $source): self
    {
        $this->source = $source;

        return $this;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function setContentType(string $contentType): self
    {
        $this->contentType = $contentType;

        return $this;
    }

    public function getDownloadedAt(): ?DateTimeInterface
    {
        return $this->downloadedAt;
    }

    public function setDownloadedAt(?DateTimeInterface $downloadedAt): self
    {
        $this->downloadedAt = $downloadedAt;

        return $this;
    }

    public function markAsDownloaded(): self
    {
        if ($this->downloadedAt === null) {
            $this->downloadedAt = new DateTime();
        }
        ++$this->downloadCount;

        return $this;
    }

    public function getDownloadCount(): int
    {
        return $this->downloadCount;
    }

    public function setDownloadCount(int $downloadCount): self
    {
        $this->downloadCount = $downloadCount;

        return $this;
    }

    public function hasMarketingConsent(): bool
    {
        return $this->marketingConsent;
    }

    public function setMarketingConsent(bool $marketingConsent): self
    {
        $this->marketingConsent = $marketingConsent;

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
     * Retourne true si le lead a déjà téléchargé le contenu.
     */
    public function hasDownloaded(): bool
    {
        return $this->downloadedAt !== null;
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

    public function getNurturingDay1SentAt(): ?DateTimeInterface
    {
        return $this->nurturingDay1SentAt;
    }

    public function setNurturingDay1SentAt(?DateTimeInterface $nurturingDay1SentAt): self
    {
        $this->nurturingDay1SentAt = $nurturingDay1SentAt;

        return $this;
    }

    public function markNurturingDay1AsSent(): self
    {
        $this->nurturingDay1SentAt = new DateTime();
        if ($this->status === self::STATUS_NEW) {
            $this->status = self::STATUS_NURTURING;
        }

        return $this;
    }

    public function getNurturingDay3SentAt(): ?DateTimeInterface
    {
        return $this->nurturingDay3SentAt;
    }

    public function setNurturingDay3SentAt(?DateTimeInterface $nurturingDay3SentAt): self
    {
        $this->nurturingDay3SentAt = $nurturingDay3SentAt;

        return $this;
    }

    public function markNurturingDay3AsSent(): self
    {
        $this->nurturingDay3SentAt = new DateTime();

        return $this;
    }

    public function getNurturingDay7SentAt(): ?DateTimeInterface
    {
        return $this->nurturingDay7SentAt;
    }

    public function setNurturingDay7SentAt(?DateTimeInterface $nurturingDay7SentAt): self
    {
        $this->nurturingDay7SentAt = $nurturingDay7SentAt;

        return $this;
    }

    public function markNurturingDay7AsSent(): self
    {
        $this->nurturingDay7SentAt = new DateTime();

        return $this;
    }

    /**
     * Retourne le nombre de jours depuis la création du lead.
     */
    public function getDaysSinceCreation(): int
    {
        $now      = new DateTime();
        $interval = $this->createdAt->diff($now);

        return (int) $interval->days;
    }

    /**
     * Retourne true si le lead devrait recevoir l'email de nurturing J+1.
     */
    public function shouldReceiveNurturingDay1(): bool
    {
        return $this->marketingConsent
            && $this->nurturingDay1SentAt === null
            && $this->getDaysSinceCreation() >= 1
            && $this->status !== self::STATUS_CONVERTED
            && $this->status !== self::STATUS_LOST;
    }

    /**
     * Retourne true si le lead devrait recevoir l'email de nurturing J+3.
     */
    public function shouldReceiveNurturingDay3(): bool
    {
        return $this->marketingConsent
            && $this->nurturingDay3SentAt === null
            && $this->getDaysSinceCreation() >= 3
            && $this->status !== self::STATUS_CONVERTED
            && $this->status !== self::STATUS_LOST;
    }

    /**
     * Retourne true si le lead devrait recevoir l'email de nurturing J+7.
     */
    public function shouldReceiveNurturingDay7(): bool
    {
        return $this->marketingConsent
            && $this->nurturingDay7SentAt === null
            && $this->getDaysSinceCreation() >= 7
            && $this->status !== self::STATUS_CONVERTED
            && $this->status !== self::STATUS_LOST;
    }
}
