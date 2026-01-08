<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CompanyRepository;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Error;
use Gedmo\Mapping\Annotation as Gedmo;
use Stringable;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Company entity - Root tenant entity for multi-tenant SAAS platform.
 *
 * Each Company represents one customer/organization and owns all related data
 * (users, projects, clients, orders, timesheets, etc.).
 *
 * Key Features:
 * - Unique slug for subdomain/URL routing
 * - Subscription tier management (starter/professional/enterprise)
 * - Resource limits (max users, projects, storage)
 * - Company-specific settings (timezone, locale, branding)
 * - Feature flags for modular functionality
 * - Trial management
 * - Cascading deletions (GDPR compliance)
 */
#[ORM\Entity(repositoryClass: CompanyRepository::class)]
#[ORM\Table(name: 'companies')]
#[ORM\Index(name: 'idx_company_slug', columns: ['slug'])]
#[ORM\Index(name: 'idx_company_status', columns: ['status'])]
#[ORM\Index(name: 'idx_company_subscription_tier', columns: ['subscription_tier'])]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['slug'], message: 'This company slug is already in use.')]
class Company implements Stringable
{
    // ===========================
    // Constants
    // ===========================

    public const STATUS_ACTIVE    = 'active';
    public const STATUS_TRIAL     = 'trial';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_CANCELLED = 'cancelled';

    public const TIER_STARTER      = 'starter';
    public const TIER_PROFESSIONAL = 'professional';
    public const TIER_ENTERPRISE   = 'enterprise';

    public const FEATURE_INVOICING      = 'invoicing';
    public const FEATURE_PLANNING       = 'planning';
    public const FEATURE_ANALYTICS      = 'analytics';
    public const FEATURE_BUSINESS_UNITS = 'business_units';
    public const FEATURE_AI_TOOLS       = 'ai_tools';
    public const FEATURE_API_ACCESS     = 'api_access';

    // ===========================
    // Primary Key
    // ===========================

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public private(set) ?int $id = null;

    // ===========================
    // Core Identity
    // ===========================

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255)]
    public string $name {
        get => $this->name;
        set {
            $this->name = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 100, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    #[Assert\Regex(
        pattern: '/^[a-z0-9-]+$/',
        message: 'Slug can only contain lowercase letters, numbers, and hyphens',
    )]
    public string $slug {
        get => $this->slug;
        set {
            $this->slug = $value;
        }
    }

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $description = null {
        get => $this->description;
        set {
            $this->description = $value;
        }
    }

    // ===========================
    // Ownership
    // ===========================

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'RESTRICT')]
    private ?User $owner = null;

    // ===========================
    // Subscription & Status
    // ===========================

    // Note: status uses private property because setStatus() has special logic for suspendedAt
    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\Choice(choices: [
        self::STATUS_ACTIVE,
        self::STATUS_TRIAL,
        self::STATUS_SUSPENDED,
        self::STATUS_CANCELLED,
    ])]
    private string $status = self::STATUS_ACTIVE;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\Choice(choices: [
        self::TIER_STARTER,
        self::TIER_PROFESSIONAL,
        self::TIER_ENTERPRISE,
    ])]
    public string $subscriptionTier = self::TIER_PROFESSIONAL {
        get => $this->subscriptionTier;
        set {
            $this->subscriptionTier = $value;
        }
    }

    // ===========================
    // Subscription Limits
    // ===========================

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Positive]
    public ?int $maxUsers = null {
        get => $this->maxUsers;
        set {
            $this->maxUsers = $value;
        }
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Positive]
    public ?int $maxProjects = null {
        get => $this->maxProjects;
        set {
            $this->maxProjects = $value;
        }
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Positive]
    public ?int $maxStorageMb = null {
        get => $this->maxStorageMb;
        set {
            $this->maxStorageMb = $value;
        }
    }

    // ===========================
    // Billing Configuration
    // ===========================

    #[ORM\Column(type: 'date')]
    #[Assert\NotNull]
    public DateTimeInterface $billingStartDate {
        get => $this->billingStartDate;
        set {
            $this->billingStartDate = $value;
        }
    }

    #[ORM\Column(type: 'integer')]
    #[Assert\Range(min: 1, max: 28)]
    public int $billingDayOfMonth = 1 {
        get => $this->billingDayOfMonth;
        set {
            $this->billingDayOfMonth = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 3)]
    #[Assert\Currency]
    public string $currency = 'EUR' {
        get => $this->currency;
        set {
            $this->currency = $value;
        }
    }

    // ===========================
    // Company-specific Settings (JSON)
    // ===========================

    #[ORM\Column(type: 'json')]
    public array $settings = [] {
        get => $this->settings;
        set {
            $this->settings = $value;
        }
    }

    // ===========================
    // Feature Flags (JSON)
    // ===========================

    #[ORM\Column(type: 'json')]
    public array $enabledFeatures = [] {
        get => $this->enabledFeatures;
        set {
            $this->enabledFeatures = $value;
        }
    }

    // ===========================
    // Company Settings (formerly CompanySettings entity)
    // ===========================

    #[ORM\Column(type: 'decimal', precision: 10, scale: 4)]
    #[Assert\Positive]
    public string $structureCostCoefficient = '1.3500' {
        get => $this->structureCostCoefficient;
        set {
            $this->structureCostCoefficient = $value;
        }
    }

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 4)]
    #[Assert\Positive]
    public string $employerChargesCoefficient = '1.4500' {
        get => $this->employerChargesCoefficient;
        set {
            $this->employerChargesCoefficient = $value;
        }
    }

    #[ORM\Column(type: 'integer')]
    #[Assert\Range(min: 0, max: 50)]
    public int $annualPaidLeaveDays = 25 {
        get => $this->annualPaidLeaveDays;
        set {
            $this->annualPaidLeaveDays = $value;
        }
    }

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\Range(min: 0, max: 30)]
    public int $annualRttDays = 10 {
        get => $this->annualRttDays;
        set {
            $this->annualRttDays = $value;
        }
    }

    // ===========================
    // Timestamps
    // ===========================

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Gedmo\Timestampable]
    public DateTimeImmutable $createdAt {
        get => $this->createdAt;
        set {
            $this->createdAt = $value;
        }
    }

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Gedmo\Timestampable]
    public ?DateTimeImmutable $updatedAt = null {
        get => $this->updatedAt;
        set {
            $this->updatedAt = $value;
        }
    }

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $suspendedAt = null {
        get => $this->suspendedAt;
        set {
            $this->suspendedAt = $value;
        }
    }

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $trialEndsAt = null {
        get => $this->trialEndsAt;
        set {
            $this->trialEndsAt = $value;
        }
    }

    // ===========================
    // Relationships (OneToMany with CASCADE DELETE)
    // ===========================

    /** @var Collection<int, BusinessUnit> */
    #[ORM\OneToMany(mappedBy: 'company', targetEntity: BusinessUnit::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $businessUnits;

    /** @var Collection<int, User> */
    #[ORM\OneToMany(mappedBy: 'company', targetEntity: User::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $users;

    // Additional relationships will be added as entities are migrated:
    // - contributors, projects, clients, orders, timesheets, technologies, etc.

    // ===========================
    // Constructor
    // ===========================

    public function __construct()
    {
        $this->businessUnits   = new ArrayCollection();
        $this->users           = new ArrayCollection();
        $this->enabledFeatures = [
            self::FEATURE_PLANNING,
            self::FEATURE_ANALYTICS,
        ];
    }

    // ===========================
    // Lifecycle Callbacks
    // ===========================

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = $this->createdAt;

        // Set billing start date to today if not set
        try {
            // Try to access the property - if uninitialized, it will throw
            $test = $this->billingStartDate;
        } catch (Error) {
            // Property not initialized, set default value
            $this->billingStartDate = new DateTime();
        }
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    // ===========================
    // Business Logic Methods
    // ===========================

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isTrial(): bool
    {
        return $this->status === self::STATUS_TRIAL;
    }

    public function isTrialActive(): bool
    {
        return $this->isTrial()
            && $this->trialEndsAt !== null
            && $this->trialEndsAt > new DateTimeImmutable();
    }

    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isFeatureEnabled(string $feature): bool
    {
        return in_array($feature, $this->enabledFeatures, true);
    }

    public function enableFeature(string $feature): self
    {
        if (!in_array($feature, $this->enabledFeatures, true)) {
            $features              = $this->enabledFeatures;
            $features[]            = $feature;
            $this->enabledFeatures = $features;
        }

        return $this;
    }

    public function disableFeature(string $feature): self
    {
        $this->enabledFeatures = array_values(
            array_filter($this->enabledFeatures, fn ($f): bool => $f !== $feature),
        );

        return $this;
    }

    /**
     * Get global charge coefficient (structure cost * employer charges).
     *
     * @return string The combined coefficient as a decimal string
     */
    public function getGlobalChargeCoefficient(): string
    {
        return bcmul($this->structureCostCoefficient, $this->employerChargesCoefficient, 4);
    }

    /**
     * Get setting value from JSON settings array.
     *
     * @param string $key     Setting key
     * @param mixed  $default Default value if key doesn't exist
     *
     * @return mixed Setting value
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default;
    }

    /**
     * Set setting value in JSON settings array.
     *
     * @param string $key   Setting key
     * @param mixed  $value Setting value
     *
     * @return $this
     */
    public function setSetting(string $key, mixed $value): self
    {
        $settings       = $this->settings;
        $settings[$key] = $value;
        $this->settings = $settings;

        return $this;
    }

    /**
     * Check if user limit is reached.
     *
     * @return bool True if at limit, false otherwise
     */
    public function isUserLimitReached(): bool
    {
        if ($this->maxUsers === null) {
            return false; // Unlimited
        }

        return $this->users->count() >= $this->maxUsers;
    }

    // ===========================
    // Relationship Getters & Setters
    // ===========================

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    // Note: getStatus() and setStatus() remain as traditional methods
    // because setStatus() has special business logic (suspendedAt timestamp)
    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        // Set suspended timestamp
        if ($status === self::STATUS_SUSPENDED && $this->suspendedAt === null) {
            $this->suspendedAt = new DateTimeImmutable();
        }

        return $this;
    }

    /**
     * @return Collection<int, BusinessUnit>
     */
    public function getBusinessUnits(): Collection
    {
        return $this->businessUnits;
    }

    public function addBusinessUnit(BusinessUnit $businessUnit): self
    {
        if (!$this->businessUnits->contains($businessUnit)) {
            $this->businessUnits->add($businessUnit);
            $businessUnit->setCompany($this);
        }

        return $this;
    }

    public function removeBusinessUnit(BusinessUnit $businessUnit): self
    {
        if ($this->businessUnits->removeElement($businessUnit)) {
            // Set the owning side to null (unless already changed)
            if ($businessUnit->getCompany() === $this) {
                $businessUnit->setCompany(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->setCompany($this);
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        if ($this->users->removeElement($user)) {
            // Set the owning side to null (unless already changed)
            if ($user->getCompany() === $this) {
                $user->setCompany(null);
            }
        }

        return $this;
    }

    // ===========================
    // String Representation
    // ===========================

    public function __toString(): string
    {
        return $this->name;
    }

    // ===========================
    // Compatibility Methods
    // ===========================

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 public private(set), prefer direct access: $company->id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $company->name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $company->name = $value.
     */
    public function setName(string $value): self
    {
        $this->name = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $company->slug.
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $company->slug = $value.
     */
    public function setSlug(string $value): self
    {
        $this->slug = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $company->description.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $company->description = $value.
     */
    public function setDescription(?string $value): self
    {
        $this->description = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $company->subscriptionTier.
     */
    public function getSubscriptionTier(): string
    {
        return $this->subscriptionTier;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $company->subscriptionTier = $value.
     */
    public function setSubscriptionTier(string $value): self
    {
        $this->subscriptionTier = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $company->maxUsers.
     */
    public function getMaxUsers(): ?int
    {
        return $this->maxUsers;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $company->maxUsers = $value.
     */
    public function setMaxUsers(?int $value): self
    {
        $this->maxUsers = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $company->maxProjects.
     */
    public function getMaxProjects(): ?int
    {
        return $this->maxProjects;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $company->maxProjects = $value.
     */
    public function setMaxProjects(?int $value): self
    {
        $this->maxProjects = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $company->maxStorageMb.
     */
    public function getMaxStorageMb(): ?int
    {
        return $this->maxStorageMb;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $company->maxStorageMb = $value.
     */
    public function setMaxStorageMb(?int $value): self
    {
        $this->maxStorageMb = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $company->billingStartDate.
     */
    public function getBillingStartDate(): DateTimeInterface
    {
        return $this->billingStartDate;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $company->billingStartDate = $value.
     */
    public function setBillingStartDate(DateTimeInterface $value): self
    {
        $this->billingStartDate = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $company->billingDayOfMonth.
     */
    public function getBillingDayOfMonth(): int
    {
        return $this->billingDayOfMonth;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $company->billingDayOfMonth = $value.
     */
    public function setBillingDayOfMonth(int $value): self
    {
        $this->billingDayOfMonth = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $company->currency.
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $company->currency = $value.
     */
    public function setCurrency(string $value): self
    {
        $this->currency = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $company->settings.
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $company->settings = $value.
     */
    public function setSettings(array $value): self
    {
        $this->settings = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $company->enabledFeatures.
     */
    public function getEnabledFeatures(): array
    {
        return $this->enabledFeatures;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $company->enabledFeatures = $value.
     */
    public function setEnabledFeatures(array $value): self
    {
        $this->enabledFeatures = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $company->structureCostCoefficient.
     */
    public function getStructureCostCoefficient(): string
    {
        return $this->structureCostCoefficient;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $company->structureCostCoefficient = $value.
     */
    public function setStructureCostCoefficient(string $value): self
    {
        $this->structureCostCoefficient = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $company->employerChargesCoefficient.
     */
    public function getEmployerChargesCoefficient(): string
    {
        return $this->employerChargesCoefficient;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $company->employerChargesCoefficient = $value.
     */
    public function setEmployerChargesCoefficient(string $value): self
    {
        $this->employerChargesCoefficient = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $company->annualPaidLeaveDays.
     */
    public function getAnnualPaidLeaveDays(): int
    {
        return $this->annualPaidLeaveDays;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $company->annualPaidLeaveDays = $value.
     */
    public function setAnnualPaidLeaveDays(int $value): self
    {
        $this->annualPaidLeaveDays = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $company->annualRttDays.
     */
    public function getAnnualRttDays(): int
    {
        return $this->annualRttDays;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $company->annualRttDays = $value.
     */
    public function setAnnualRttDays(int $value): self
    {
        $this->annualRttDays = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $company->createdAt.
     */
    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $company->updatedAt.
     */
    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $company->suspendedAt.
     */
    public function getSuspendedAt(): ?DateTimeImmutable
    {
        return $this->suspendedAt;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $company->trialEndsAt.
     */
    public function getTrialEndsAt(): ?DateTimeImmutable
    {
        return $this->trialEndsAt;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $company->trialEndsAt = $value.
     */
    public function setTrialEndsAt(?DateTimeImmutable $value): self
    {
        $this->trialEndsAt = $value;

        return $this;
    }
}
