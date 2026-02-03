<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interface\CompanyOwnedInterface;
use App\Repository\HubSpotSettingsRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Configuration du connecteur HubSpot pour une entreprise.
 * Permet la synchronisation des affaires, clients et contacts depuis HubSpot vers HotOnes.
 */
#[ORM\Entity(repositoryClass: HubSpotSettingsRepository::class)]
#[ORM\Table(name: 'hubspot_settings')]
#[ORM\Index(name: 'idx_hubspot_settings_company', columns: ['company_id'])]
#[ORM\HasLifecycleCallbacks]
class HubSpotSettings implements CompanyOwnedInterface
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
     * Token d'acces prive HubSpot (Private App Access Token).
     * Format: pat-na1-xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx.
     */
    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    public ?string $accessToken = null {
        get => $this->accessToken;
        set {
            $this->accessToken = $value;
        }
    }

    /**
     * Portal ID (Hub ID) HubSpot.
     */
    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    public ?string $portalId = null {
        get => $this->portalId;
        set {
            $this->portalId = $value;
        }
    }

    /**
     * Activation/desactivation de la synchronisation.
     */
    #[ORM\Column(type: 'boolean')]
    public bool $enabled = false {
        get => $this->enabled;
        set {
            $this->enabled = $value;
        }
    }

    /**
     * Synchronisation automatique activee.
     */
    #[ORM\Column(type: 'boolean')]
    public bool $autoSyncEnabled = false {
        get => $this->autoSyncEnabled;
        set {
            $this->autoSyncEnabled = $value;
        }
    }

    /**
     * Frequence de synchronisation automatique en heures.
     */
    #[ORM\Column(type: 'integer')]
    public int $syncFrequencyHours = 24 {
        get => $this->syncFrequencyHours;
        set {
            $this->syncFrequencyHours = $value;
        }
    }

    /**
     * Synchroniser les affaires (deals).
     */
    #[ORM\Column(type: 'boolean')]
    public bool $syncDeals = true {
        get => $this->syncDeals;
        set {
            $this->syncDeals = $value;
        }
    }

    /**
     * Synchroniser les entreprises (companies).
     */
    #[ORM\Column(type: 'boolean')]
    public bool $syncCompanies = true {
        get => $this->syncCompanies;
        set {
            $this->syncCompanies = $value;
        }
    }

    /**
     * Synchroniser les contacts.
     */
    #[ORM\Column(type: 'boolean')]
    public bool $syncContacts = true {
        get => $this->syncContacts;
        set {
            $this->syncContacts = $value;
        }
    }

    /**
     * Filtrer les deals par pipeline (IDs separes par des virgules).
     * Si vide, tous les pipelines sont synchronises.
     */
    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $pipelineFilter = null {
        get => $this->pipelineFilter;
        set {
            $this->pipelineFilter = $value;
        }
    }

    /**
     * Filtrer les deals par stades a exclure (ex: closedwon, closedlost).
     * Stages separes par des virgules.
     */
    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $excludedStages = 'closedwon,closedlost' {
        get => $this->excludedStages;
        set {
            $this->excludedStages = $value;
        }
    }

    /**
     * Date de derniere synchronisation reussie.
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    public ?DateTimeInterface $lastSyncAt = null {
        get => $this->lastSyncAt;
        set {
            $this->lastSyncAt = $value;
        }
    }

    /**
     * Statut de la derniere synchronisation.
     */
    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    public ?string $lastSyncStatus = null {
        get => $this->lastSyncStatus;
        set {
            $this->lastSyncStatus = $value;
        }
    }

    /**
     * Message d'erreur de la derniere synchronisation.
     */
    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $lastSyncError = null {
        get => $this->lastSyncError;
        set {
            $this->lastSyncError = $value;
        }
    }

    /**
     * Nombre d'entrees synchronisees lors de la derniere sync.
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
        return $this->accessToken !== null && $this->accessToken !== '';
    }

    public function needsSync(): bool
    {
        if (!$this->enabled || !$this->autoSyncEnabled || !$this->isConfigured()) {
            return false;
        }

        if ($this->lastSyncAt === null) {
            return true;
        }

        $nextSyncTime = (clone $this->lastSyncAt)->modify('+' . $this->syncFrequencyHours . ' hours');

        return new DateTime() >= $nextSyncTime;
    }

    /**
     * Retourne la liste des pipeline IDs a synchroniser.
     *
     * @return string[]
     */
    public function getPipelineIds(): array
    {
        if ($this->pipelineFilter === null || trim($this->pipelineFilter) === '') {
            return [];
        }

        return array_filter(array_map('trim', explode(',', $this->pipelineFilter)));
    }

    /**
     * Retourne la liste des stages exclus.
     *
     * @return string[]
     */
    public function getExcludedStagesList(): array
    {
        if ($this->excludedStages === null || trim($this->excludedStages) === '') {
            return [];
        }

        return array_filter(array_map('trim', explode(',', $this->excludedStages)));
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

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function setAccessToken(?string $value): self
    {
        $this->accessToken = $value;

        return $this;
    }

    public function getPortalId(): ?string
    {
        return $this->portalId;
    }

    public function setPortalId(?string $value): self
    {
        $this->portalId = $value;

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

    public function isSyncDeals(): bool
    {
        return $this->syncDeals;
    }

    public function setSyncDeals(bool $value): self
    {
        $this->syncDeals = $value;

        return $this;
    }

    public function isSyncCompanies(): bool
    {
        return $this->syncCompanies;
    }

    public function setSyncCompanies(bool $value): self
    {
        $this->syncCompanies = $value;

        return $this;
    }

    public function isSyncContacts(): bool
    {
        return $this->syncContacts;
    }

    public function setSyncContacts(bool $value): self
    {
        $this->syncContacts = $value;

        return $this;
    }

    public function getPipelineFilter(): ?string
    {
        return $this->pipelineFilter;
    }

    public function setPipelineFilter(?string $value): self
    {
        $this->pipelineFilter = $value;

        return $this;
    }

    public function getExcludedStages(): ?string
    {
        return $this->excludedStages;
    }

    public function setExcludedStages(?string $value): self
    {
        $this->excludedStages = $value;

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
