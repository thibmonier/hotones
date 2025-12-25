<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CookieConsentRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entité de consentement cookies pour traçabilité RGPD.
 * Stocke les préférences de cookies de l'utilisateur avec IP et User-Agent.
 */
#[ORM\Entity(repositoryClass: CookieConsentRepository::class)]
#[ORM\Table(name: 'cookie_consents')]
#[ORM\Index(columns: ['user_id'], name: 'idx_cookie_consent_user')]
#[ORM\Index(columns: ['created_at'], name: 'idx_cookie_consent_created')]
#[ORM\Index(columns: ['expires_at'], name: 'idx_cookie_consent_expires')]
#[ORM\HasLifecycleCallbacks]
class CookieConsent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Utilisateur associé (null pour les visiteurs non authentifiés).
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', nullable: true, onDelete: 'CASCADE')]
    private ?User $user = null;

    /**
     * Cookies essentiels (toujours true, non désactivable).
     */
    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $essential = true;

    /**
     * Cookies fonctionnels (mémorisation préférences, filtres, etc.).
     */
    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $functional = false;

    /**
     * Cookies analytiques (statistiques anonymes).
     */
    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $analytics = false;

    /**
     * Version de la politique de cookies acceptée.
     */
    #[ORM\Column(type: Types::STRING, length: 10)]
    private string $version = '1.0';

    /**
     * Adresse IP de l'utilisateur au moment du consentement.
     */
    #[ORM\Column(type: Types::STRING, length: 45, nullable: true)]
    private ?string $ipAddress = null;

    /**
     * User-Agent du navigateur.
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $userAgent = null;

    /**
     * Date de consentement.
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?DateTimeImmutable $createdAt = null;

    /**
     * Date d'expiration du consentement (365 jours par défaut).
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?DateTimeImmutable $expiresAt = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new DateTimeImmutable();
        $this->expiresAt = new DateTimeImmutable('+365 days');
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

    public function isEssential(): bool
    {
        return $this->essential;
    }

    public function setEssential(bool $essential): static
    {
        $this->essential = $essential;

        return $this;
    }

    public function isFunctional(): bool
    {
        return $this->functional;
    }

    public function setFunctional(bool $functional): static
    {
        $this->functional = $functional;

        return $this;
    }

    public function isAnalytics(): bool
    {
        return $this->analytics;
    }

    public function setAnalytics(bool $analytics): static
    {
        $this->analytics = $analytics;

        return $this;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function setVersion(string $version): static
    {
        $this->version = $version;

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

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): static
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getExpiresAt(): ?DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(DateTimeImmutable $expiresAt): static
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    /**
     * Vérifie si le consentement est expiré.
     */
    public function isExpired(): bool
    {
        return $this->expiresAt < new DateTimeImmutable();
    }
}
