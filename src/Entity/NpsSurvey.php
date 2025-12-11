<?php

namespace App\Entity;

use App\Repository\NpsSurveyRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

/**
 * Enquête de satisfaction client (Net Promoter Score).
 */
#[ORM\Entity(repositoryClass: NpsSurveyRepository::class)]
#[ORM\Table(name: 'nps_surveys')]
#[ORM\HasLifecycleCallbacks]
class NpsSurvey
{
    // Statuts possibles
    public const STATUS_PENDING   = 'pending';      // En attente de réponse
    public const STATUS_COMPLETED = 'completed';  // Répondu
    public const STATUS_EXPIRED   = 'expired';      // Expiré

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Project $project = null;

    /**
     * Token unique pour accéder au formulaire publiquement.
     */
    #[ORM\Column(type: Types::STRING, length: 64, unique: true)]
    private ?string $token = null;

    /**
     * Date d'envoi de l'enquête.
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $sentAt = null;

    /**
     * Date de réponse (null si pas encore répondu).
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $respondedAt = null;

    /**
     * Score NPS (0-10).
     * 0-6 : Détracteurs
     * 7-8 : Passifs
     * 9-10 : Promoteurs.
     */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $score = null;

    /**
     * Commentaire optionnel du client.
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comment = null;

    /**
     * Statut de l'enquête.
     */
    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $status = self::STATUS_PENDING;

    /**
     * Email du contact client qui doit répondre.
     */
    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $recipientEmail = null;

    /**
     * Nom du contact client.
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $recipientName = null;

    /**
     * Date d'expiration de l'enquête (après laquelle elle n'est plus valide).
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $expiresAt = null;

    /**
     * Date de création.
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new DateTime();
        $this->sentAt    = new DateTime();
        $this->token     = bin2hex(random_bytes(32));
        // Par défaut, expire après 30 jours
        $this->expiresAt = (new DateTime())->modify('+30 days');
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        if ($this->createdAt === null) {
            $this->createdAt = new DateTime();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getSentAt(): ?DateTimeInterface
    {
        return $this->sentAt;
    }

    public function setSentAt(DateTimeInterface $sentAt): self
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    public function getRespondedAt(): ?DateTimeInterface
    {
        return $this->respondedAt;
    }

    public function setRespondedAt(?DateTimeInterface $respondedAt): self
    {
        $this->respondedAt = $respondedAt;

        return $this;
    }

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(?int $score): self
    {
        if ($score !== null && ($score < 0 || $score > 10)) {
            throw new InvalidArgumentException('Le score NPS doit être entre 0 et 10');
        }

        $this->score = $score;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

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

    public function getRecipientEmail(): ?string
    {
        return $this->recipientEmail;
    }

    public function setRecipientEmail(string $recipientEmail): self
    {
        $this->recipientEmail = $recipientEmail;

        return $this;
    }

    public function getRecipientName(): ?string
    {
        return $this->recipientName;
    }

    public function setRecipientName(?string $recipientName): self
    {
        $this->recipientName = $recipientName;

        return $this;
    }

    public function getExpiresAt(): ?DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(DateTimeInterface $expiresAt): self
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * Vérifie si l'enquête a expiré.
     */
    public function isExpired(): bool
    {
        return $this->expiresAt < new DateTime();
    }

    /**
     * Retourne la catégorie NPS basée sur le score.
     * - Détracteur : 0-6
     * - Passif : 7-8
     * - Promoteur : 9-10.
     */
    public function getCategory(): ?string
    {
        if ($this->score === null) {
            return null;
        }

        if ($this->score <= 6) {
            return 'detractor';
        }

        if ($this->score <= 8) {
            return 'passive';
        }

        return 'promoter';
    }

    /**
     * Retourne le libellé de la catégorie en français.
     */
    public function getCategoryLabel(): ?string
    {
        return match ($this->getCategory()) {
            'detractor' => 'Détracteur',
            'passive'   => 'Passif',
            'promoter'  => 'Promoteur',
            default     => null,
        };
    }

    /**
     * Marque l'enquête comme complétée.
     */
    public function markAsCompleted(): self
    {
        $this->status      = self::STATUS_COMPLETED;
        $this->respondedAt = new DateTime();

        return $this;
    }

    /**
     * Marque l'enquête comme expirée.
     */
    public function markAsExpired(): self
    {
        $this->status = self::STATUS_EXPIRED;

        return $this;
    }

    public function setCreatedAt(DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
