<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CompanyRepository;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
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
class Company
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
    private ?int $id = null;

    // ===========================
    // Core Identity
    // ===========================

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 100, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    #[Assert\Regex(
        pattern: '/^[a-z0-9-]+$/',
        message: 'Slug can only contain lowercase letters, numbers, and hyphens',
    )]
    private string $slug;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    // ===========================
    // Ownership
    // ===========================

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    #[Assert\NotNull]
    private User $owner;

    // ===========================
    // Subscription & Status
    // ===========================

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
    private string $subscriptionTier = self::TIER_PROFESSIONAL;

    // ===========================
    // Subscription Limits
    // ===========================

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Positive]
    private ?int $maxUsers = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Positive]
    private ?int $maxProjects = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Positive]
    private ?int $maxStorageMb = null;

    // ===========================
    // Billing Configuration
    // ===========================

    #[ORM\Column(type: 'date')]
    #[Assert\NotNull]
    private DateTimeInterface $billingStartDate;

    #[ORM\Column(type: 'integer')]
    #[Assert\Range(min: 1, max: 28)]
    private int $billingDayOfMonth = 1;

    #[ORM\Column(type: 'string', length: 3)]
    #[Assert\Currency]
    private string $currency = 'EUR';

    // ===========================
    // Company-specific Settings (JSON)
    // ===========================

    #[ORM\Column(type: 'json')]
    private array $settings = [];

    // ===========================
    // Feature Flags (JSON)
    // ===========================

    #[ORM\Column(type: 'json')]
    private array $enabledFeatures = [];

    // ===========================
    // Company Settings (formerly CompanySettings entity)
    // ===========================

    #[ORM\Column(type: 'decimal', precision: 10, scale: 4)]
    #[Assert\Positive]
    private string $structureCostCoefficient = '1.3500';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 4)]
    #[Assert\Positive]
    private string $employerChargesCoefficient = '1.4500';

    #[ORM\Column(type: 'integer')]
    #[Assert\Range(min: 0, max: 50)]
    private int $annualPaidLeaveDays = 25;

    #[ORM\Column(type: 'integer')]
    #[Assert\Range(min: 0, max: 30)]
    private int $annualRttDays = 10;

    // ===========================
    // Timestamps
    // ===========================

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $suspendedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $trialEndsAt = null;

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
        if (!isset($this->billingStartDate)) {
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
            $this->enabledFeatures[] = $feature;
        }

        return $this;
    }

    public function disableFeature(string $feature): self
    {
        $this->enabledFeatures = array_values(
            array_filter($this->enabledFeatures, fn ($f) => $f !== $feature),
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
        $this->settings[$key] = $value;

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
    // Getters & Setters
    // ===========================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

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

    public function getOwner(): User
    {
        return $this->owner;
    }

    public function setOwner(User $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

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

    public function getSubscriptionTier(): string
    {
        return $this->subscriptionTier;
    }

    public function setSubscriptionTier(string $subscriptionTier): self
    {
        $this->subscriptionTier = $subscriptionTier;

        return $this;
    }

    public function getMaxUsers(): ?int
    {
        return $this->maxUsers;
    }

    public function setMaxUsers(?int $maxUsers): self
    {
        $this->maxUsers = $maxUsers;

        return $this;
    }

    public function getMaxProjects(): ?int
    {
        return $this->maxProjects;
    }

    public function setMaxProjects(?int $maxProjects): self
    {
        $this->maxProjects = $maxProjects;

        return $this;
    }

    public function getMaxStorageMb(): ?int
    {
        return $this->maxStorageMb;
    }

    public function setMaxStorageMb(?int $maxStorageMb): self
    {
        $this->maxStorageMb = $maxStorageMb;

        return $this;
    }

    public function getBillingStartDate(): DateTimeInterface
    {
        return $this->billingStartDate;
    }

    public function setBillingStartDate(DateTimeInterface $billingStartDate): self
    {
        $this->billingStartDate = $billingStartDate;

        return $this;
    }

    public function getBillingDayOfMonth(): int
    {
        return $this->billingDayOfMonth;
    }

    public function setBillingDayOfMonth(int $billingDayOfMonth): self
    {
        $this->billingDayOfMonth = $billingDayOfMonth;

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

    public function getSettings(): array
    {
        return $this->settings;
    }

    public function setSettings(array $settings): self
    {
        $this->settings = $settings;

        return $this;
    }

    public function getEnabledFeatures(): array
    {
        return $this->enabledFeatures;
    }

    public function setEnabledFeatures(array $enabledFeatures): self
    {
        $this->enabledFeatures = $enabledFeatures;

        return $this;
    }

    public function getStructureCostCoefficient(): string
    {
        return $this->structureCostCoefficient;
    }

    public function setStructureCostCoefficient(string $structureCostCoefficient): self
    {
        $this->structureCostCoefficient = $structureCostCoefficient;

        return $this;
    }

    public function getEmployerChargesCoefficient(): string
    {
        return $this->employerChargesCoefficient;
    }

    public function setEmployerChargesCoefficient(string $employerChargesCoefficient): self
    {
        $this->employerChargesCoefficient = $employerChargesCoefficient;

        return $this;
    }

    public function getAnnualPaidLeaveDays(): int
    {
        return $this->annualPaidLeaveDays;
    }

    public function setAnnualPaidLeaveDays(int $annualPaidLeaveDays): self
    {
        $this->annualPaidLeaveDays = $annualPaidLeaveDays;

        return $this;
    }

    public function getAnnualRttDays(): int
    {
        return $this->annualRttDays;
    }

    public function setAnnualRttDays(int $annualRttDays): self
    {
        $this->annualRttDays = $annualRttDays;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getSuspendedAt(): ?DateTimeImmutable
    {
        return $this->suspendedAt;
    }

    public function getTrialEndsAt(): ?DateTimeImmutable
    {
        return $this->trialEndsAt;
    }

    public function setTrialEndsAt(?DateTimeImmutable $trialEndsAt): self
    {
        $this->trialEndsAt = $trialEndsAt;

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
}
