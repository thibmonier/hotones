<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interface\CompanyOwnedInterface;
use App\Repository\BoondManagerSettingsRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Configuration du connecteur BoondManager pour une entreprise.
 * Permet la synchronisation des temps passés depuis BoondManager vers HotOnes.
 */
#[ORM\Entity(repositoryClass: BoondManagerSettingsRepository::class)]
#[ORM\Table(name: 'boond_manager_settings')]
#[ORM\Index(name: 'idx_boond_settings_company', columns: ['company_id'])]
#[ORM\HasLifecycleCallbacks]
class BoondManagerSettings implements CompanyOwnedInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public private(set) ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private Company $company;

    /**
     * URL de base de l'API BoondManager (ex: https://votrecompagnie.api.boondmanager.com).
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    public ?string $apiBaseUrl = null {
        get => $this->apiBaseUrl;
        set {
            $this->apiBaseUrl = $value;
        }
    }

    /**
     * Nom d'utilisateur pour l'authentification Basic.
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    public ?string $apiUsername = null {
        get => $this->apiUsername;
        set {
            $this->apiUsername = $value;
        }
    }

    /**
     * Mot de passe pour l'authentification Basic (stocké chiffré en base).
     */
    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    public ?string $apiPassword = null {
        get => $this->apiPassword;
        set {
            $this->apiPassword = $value;
        }
    }

    /**
     * Token utilisateur pour l'authentification JWT (optionnel).
     */
    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    public ?string $userToken = null {
        get => $this->userToken;
        set {
            $this->userToken = $value;
        }
    }

    /**
     * Token client pour l'authentification JWT (optionnel).
     */
    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    public ?string $clientToken = null {
        get => $this->clientToken;
        set {
            $this->clientToken = $value;
        }
    }

    /**
     * Clé client pour l'authentification JWT (optionnel).
     */
    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    public ?string $clientKey = null {
        get => $this->clientKey;
        set {
            $this->clientKey = $value;
        }
    }

    /**
     * Type d'authentification: 'basic' ou 'jwt'.
     */
    #[ORM\Column(type: 'string', length: 20)]
    public string $authType = 'basic' {
        get => $this->authType;
        set {
            $this->authType = $value;
        }
    }

    /**
     * Activation/désactivation de la synchronisation.
     */
    #[ORM\Column(type: 'boolean')]
    public bool $enabled = false {
        get => $this->enabled;
        set {
            $this->enabled = $value;
        }
    }

    /**
     * Synchronisation automatique activée.
     */
    #[ORM\Column(type: 'boolean')]
    public bool $autoSyncEnabled = false {
        get => $this->autoSyncEnabled;
        set {
            $this->autoSyncEnabled = $value;
        }
    }

    /**
     * Fréquence de synchronisation automatique en heures.
     */
    #[ORM\Column(type: 'integer')]
    public int $syncFrequencyHours = 24 {
        get => $this->syncFrequencyHours;
        set {
            $this->syncFrequencyHours = $value;
        }
    }

    /**
     * Date de dernière synchronisation réussie.
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    public ?DateTimeInterface $lastSyncAt = null {
        get => $this->lastSyncAt;
        set {
            $this->lastSyncAt = $value;
        }
    }

    /**
     * Statut de la dernière synchronisation.
     */
    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    public ?string $lastSyncStatus = null {
        get => $this->lastSyncStatus;
        set {
            $this->lastSyncStatus = $value;
        }
    }

    /**
     * Message d'erreur de la dernière synchronisation.
     */
    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $lastSyncError = null {
        get => $this->lastSyncError;
        set {
            $this->lastSyncError = $value;
        }
    }

    /**
     * Nombre d'entrées synchronisées lors de la dernière sync.
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    public ?int $lastSyncCount = null {
        get => $this->lastSyncCount;
        set {
            $this->lastSyncCount = $value;
        }
    }

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
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new DateTime();
    }

    public function isConfigured(): bool
    {
        if ($this->apiBaseUrl === null || $this->apiBaseUrl === '') {
            return false;
        }

        if ($this->authType === 'basic') {
            return
                $this->apiUsername    !== null
                && $this->apiUsername !== ''
                && $this->apiPassword !== null
                && $this->apiPassword !== ''
            ;
        }

        // JWT auth
        return
            $this->userToken      !== null
            && $this->userToken   !== ''
            && $this->clientToken !== null
            && $this->clientToken !== ''
            && $this->clientKey   !== null
            && $this->clientKey   !== ''
        ;
    }

    public function needsSync(): bool
    {
        if (!$this->enabled || !$this->autoSyncEnabled || !$this->isConfigured()) {
            return false;
        }

        if ($this->lastSyncAt === null) {
            return true;
        }

        $nextSyncTime = (clone $this->lastSyncAt)->modify('+'.$this->syncFrequencyHours.' hours');

        return new DateTime() >= $nextSyncTime;
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

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getApiBaseUrl(): ?string
    {
        return $this->apiBaseUrl;
    }

    public function setApiBaseUrl(?string $value): self
    {
        $this->apiBaseUrl = $value;

        return $this;
    }

    public function getApiUsername(): ?string
    {
        return $this->apiUsername;
    }

    public function setApiUsername(?string $value): self
    {
        $this->apiUsername = $value;

        return $this;
    }

    public function getApiPassword(): ?string
    {
        return $this->apiPassword;
    }

    public function setApiPassword(?string $value): self
    {
        $this->apiPassword = $value;

        return $this;
    }

    public function getUserToken(): ?string
    {
        return $this->userToken;
    }

    public function setUserToken(?string $value): self
    {
        $this->userToken = $value;

        return $this;
    }

    public function getClientToken(): ?string
    {
        return $this->clientToken;
    }

    public function setClientToken(?string $value): self
    {
        $this->clientToken = $value;

        return $this;
    }

    public function getClientKey(): ?string
    {
        return $this->clientKey;
    }

    public function setClientKey(?string $value): self
    {
        $this->clientKey = $value;

        return $this;
    }

    public function getAuthType(): string
    {
        return $this->authType;
    }

    public function setAuthType(string $value): self
    {
        $this->authType = $value;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $value): self
    {
        $this->enabled = $value;

        return $this;
    }

    public function isAutoSyncEnabled(): bool
    {
        return $this->autoSyncEnabled;
    }

    public function setAutoSyncEnabled(bool $value): self
    {
        $this->autoSyncEnabled = $value;

        return $this;
    }

    public function getSyncFrequencyHours(): int
    {
        return $this->syncFrequencyHours;
    }

    public function setSyncFrequencyHours(int $value): self
    {
        $this->syncFrequencyHours = $value;

        return $this;
    }

    public function getLastSyncAt(): ?DateTimeInterface
    {
        return $this->lastSyncAt;
    }

    public function setLastSyncAt(?DateTimeInterface $value): self
    {
        $this->lastSyncAt = $value;

        return $this;
    }

    public function getLastSyncStatus(): ?string
    {
        return $this->lastSyncStatus;
    }

    public function setLastSyncStatus(?string $value): self
    {
        $this->lastSyncStatus = $value;

        return $this;
    }

    public function getLastSyncError(): ?string
    {
        return $this->lastSyncError;
    }

    public function setLastSyncError(?string $value): self
    {
        $this->lastSyncError = $value;

        return $this;
    }

    public function getLastSyncCount(): ?int
    {
        return $this->lastSyncCount;
    }

    public function setLastSyncCount(?int $value): self
    {
        $this->lastSyncCount = $value;

        return $this;
    }
}
