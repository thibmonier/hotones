<?php

namespace App\Entity;

use App\Entity\Interface\CompanyOwnedInterface;
use App\Repository\NpsSurveyRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\Blameable;
use Gedmo\Timestampable\Traits\Timestampable;
use InvalidArgumentException;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Enquête de satisfaction client (Net Promoter Score).
 */
#[ORM\Entity(repositoryClass: NpsSurveyRepository::class)]
#[ORM\Table(name: 'nps_surveys')]
#[ORM\Index(name: 'idx_npssurvey_company', columns: ['company_id'])]
#[ORM\HasLifecycleCallbacks]
class NpsSurvey implements CompanyOwnedInterface
{
    use Timestampable;
    use Blameable;

    // Statuts possibles
    public const STATUS_PENDING   = 'pending';      // En attente de réponse
    public const STATUS_COMPLETED = 'completed';  // Répondu
    public const STATUS_EXPIRED   = 'expired';      // Expiré

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public private(set) ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private Company $company;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Project $project = null;

    /**
     * Token unique pour accéder au formulaire publiquement.
     */
    #[ORM\Column(type: Types::STRING, length: 64, unique: true)]
    #[Ignore]
    public ?string $token = null {
        get => $this->token;
        set {
            $this->token = $value;
        }
    }

    /**
     * Date d'envoi de l'enquête.
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    public ?DateTimeInterface $sentAt = null {
        get => $this->sentAt;
        set {
            $this->sentAt = $value;
        }
    }

    /**
     * Date de réponse (null si pas encore répondu).
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    public ?DateTimeInterface $respondedAt = null {
        get => $this->respondedAt;
        set {
            $this->respondedAt = $value;
        }
    }

    /**
     * Score NPS (0-10).
     * 0-6 : Détracteurs
     * 7-8 : Passifs
     * 9-10 : Promoteurs.
     */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    public ?int $score = null {
        get => $this->score;
        set {
            if ($value !== null && ($value < 0 || $value > 10)) {
                throw new InvalidArgumentException('Le score NPS doit être entre 0 et 10');
            }
            $this->score = $value;
        }
    }

    /**
     * Commentaire optionnel du client.
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public ?string $comment = null {
        get => $this->comment;
        set {
            $this->comment = $value;
        }
    }

    /**
     * Statut de l'enquête.
     */
    #[ORM\Column(type: Types::STRING, length: 20)]
    public string $status = self::STATUS_PENDING {
        get => $this->status;
        set {
            $this->status = $value;
        }
    }

    /**
     * Email du contact client qui doit répondre.
     */
    #[ORM\Column(type: Types::STRING, length: 255)]
    public ?string $recipientEmail = null {
        get => $this->recipientEmail;
        set {
            $this->recipientEmail = $value;
        }
    }

    /**
     * Nom du contact client.
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    public ?string $recipientName = null {
        get => $this->recipientName;
        set {
            $this->recipientName = $value;
        }
    }

    /**
     * Date d'expiration de l'enquête (après laquelle elle n'est plus valide).
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    public ?DateTimeInterface $expiresAt = null {
        get => $this->expiresAt;
        set {
            $this->expiresAt = $value;
        }
    }

    public function __construct()
    {
        $this->sentAt = new DateTime();
        $this->token  = bin2hex(random_bytes(32));
        // Par défaut, expire après 30 jours
        $this->expiresAt = (new DateTime())->modify('+30 days');
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
     * With PHP 8.4 property hooks, prefer direct access: $survey->token.
     */
    public function getToken(): ?string
    {
        return $this->token;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $survey->token = $value.
     */
    public function setToken(#[SensitiveParameter] string $value): self
    {
        $this->token = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $survey->sentAt.
     */
    public function getSentAt(): ?DateTimeInterface
    {
        return $this->sentAt;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $survey->sentAt = $value.
     */
    public function setSentAt(DateTimeInterface $value): self
    {
        $this->sentAt = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $survey->respondedAt.
     */
    public function getRespondedAt(): ?DateTimeInterface
    {
        return $this->respondedAt;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $survey->respondedAt = $value.
     */
    public function setRespondedAt(?DateTimeInterface $value): self
    {
        $this->respondedAt = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $survey->score.
     */
    public function getScore(): ?int
    {
        return $this->score;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $survey->score = $value.
     */
    public function setScore(?int $value): self
    {
        $this->score = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $survey->comment.
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $survey->comment = $value.
     */
    public function setComment(?string $value): self
    {
        $this->comment = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $survey->status.
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $survey->status = $value.
     */
    public function setStatus(string $value): self
    {
        $this->status = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $survey->recipientEmail.
     */
    public function getRecipientEmail(): ?string
    {
        return $this->recipientEmail;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $survey->recipientEmail = $value.
     */
    public function setRecipientEmail(string $value): self
    {
        $this->recipientEmail = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $survey->recipientName.
     */
    public function getRecipientName(): ?string
    {
        return $this->recipientName;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $survey->recipientName = $value.
     */
    public function setRecipientName(?string $value): self
    {
        $this->recipientName = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $survey->expiresAt.
     */
    public function getExpiresAt(): ?DateTimeInterface
    {
        return $this->expiresAt;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $survey->expiresAt = $value.
     */
    public function setExpiresAt(DateTimeInterface $value): self
    {
        $this->expiresAt = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $survey->createdAt.
     */
    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }
}
