<?php

declare(strict_types=1);

namespace App\Domain\Company\Entity;

use App\Domain\Company\Event\CompanyCreatedEvent;
use App\Domain\Company\Event\CompanyStatusChangedEvent;
use App\Domain\Company\Event\CompanySubscriptionChangedEvent;
use App\Domain\Company\Exception\CompanyResourceLimitException;
use App\Domain\Company\Exception\InvalidCompanyStatusTransitionException;
use App\Domain\Company\ValueObject\CompanyFeature;
use App\Domain\Company\ValueObject\CompanyId;
use App\Domain\Company\ValueObject\CompanySlug;
use App\Domain\Company\ValueObject\CompanyStatus;
use App\Domain\Company\ValueObject\SubscriptionTier;
use App\Domain\Shared\Interface\AggregateRootInterface;
use App\Domain\Shared\Trait\RecordsDomainEvents;
use DateTimeImmutable;

/**
 * Company aggregate root - multi-tenant SAAS root entity.
 */
final class Company implements AggregateRootInterface
{
    use RecordsDomainEvents;

    private CompanyId $id;
    private string $name;
    private CompanySlug $slug;
    private CompanyStatus $status;
    private SubscriptionTier $subscriptionTier;

    /** @var array<string> */
    private array $enabledFeatures;

    // Resource limits
    private int $maxUsers;
    private int $maxProjects;
    private int $maxStorageMb;

    // Billing configuration
    private ?DateTimeImmutable $billingStartDate;
    private int $billingDayOfMonth;
    private string $currency;

    // Company settings
    private float $structureCostCoefficient;
    private float $employerChargesCoefficient;
    private int $annualPaidLeaveDays;
    private int $annualRttDays;

    // Timestamps
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt;
    private ?DateTimeImmutable $suspendedAt;
    private ?DateTimeImmutable $trialEndsAt;

    private function __construct(
        CompanyId $id,
        string $name,
        CompanySlug $slug,
        SubscriptionTier $subscriptionTier,
    ) {
        $this->id               = $id;
        $this->name             = $name;
        $this->slug             = $slug;
        $this->subscriptionTier = $subscriptionTier;
        $this->status           = CompanyStatus::TRIAL;
        $this->enabledFeatures  = array_map(
            fn (CompanyFeature $f) => $f->value,
            CompanyFeature::getDefaultsForTier($subscriptionTier),
        );

        // Default resource limits based on tier
        $this->setResourceLimitsForTier($subscriptionTier);

        // Billing defaults
        $this->billingStartDate  = null;
        $this->billingDayOfMonth = 1;
        $this->currency          = 'EUR';

        // Settings defaults
        $this->structureCostCoefficient   = 0.0;
        $this->employerChargesCoefficient = 0.45;
        $this->annualPaidLeaveDays        = 25;
        $this->annualRttDays              = 10;

        // Timestamps
        $this->createdAt   = new DateTimeImmutable();
        $this->updatedAt   = null;
        $this->suspendedAt = null;
        $this->trialEndsAt = (new DateTimeImmutable())->modify('+14 days');
    }

    public static function create(
        CompanyId $id,
        string $name,
        CompanySlug $slug,
        SubscriptionTier $subscriptionTier = SubscriptionTier::STARTER,
    ): self {
        $company = new self($id, $name, $slug, $subscriptionTier);

        $company->recordEvent(
            CompanyCreatedEvent::create($id, $name, $slug, $subscriptionTier),
        );

        return $company;
    }

    // Status management

    public function changeStatus(CompanyStatus $newStatus): void
    {
        if ($this->status === $newStatus) {
            return;
        }

        if (!$this->status->canTransitionTo($newStatus)) {
            throw InvalidCompanyStatusTransitionException::create($this->status, $newStatus);
        }

        $previousStatus  = $this->status;
        $this->status    = $newStatus;
        $this->updatedAt = new DateTimeImmutable();

        if ($newStatus === CompanyStatus::SUSPENDED) {
            $this->suspendedAt = new DateTimeImmutable();
        }

        if ($previousStatus === CompanyStatus::SUSPENDED && $newStatus === CompanyStatus::ACTIVE) {
            $this->suspendedAt = null;
        }

        $this->recordEvent(
            CompanyStatusChangedEvent::create($this->id, $previousStatus, $newStatus),
        );
    }

    public function activate(): void
    {
        $this->changeStatus(CompanyStatus::ACTIVE);
    }

    public function suspend(): void
    {
        $this->changeStatus(CompanyStatus::SUSPENDED);
    }

    public function cancel(): void
    {
        $this->changeStatus(CompanyStatus::CANCELLED);
    }

    // Subscription management

    public function changeSubscription(SubscriptionTier $newTier): void
    {
        if ($this->subscriptionTier === $newTier) {
            return;
        }

        $previousTier           = $this->subscriptionTier;
        $this->subscriptionTier = $newTier;
        $this->setResourceLimitsForTier($newTier);
        $this->enabledFeatures = array_map(
            fn (CompanyFeature $f) => $f->value,
            CompanyFeature::getDefaultsForTier($newTier),
        );
        $this->updatedAt = new DateTimeImmutable();

        $this->recordEvent(
            CompanySubscriptionChangedEvent::create($this->id, $previousTier, $newTier),
        );
    }

    private function setResourceLimitsForTier(SubscriptionTier $tier): void
    {
        [$this->maxUsers, $this->maxProjects, $this->maxStorageMb] = match ($tier) {
            SubscriptionTier::STARTER      => [5, 10, 1024],
            SubscriptionTier::PROFESSIONAL => [25, 50, 10240],
            SubscriptionTier::ENTERPRISE   => [999, 999, 102400],
        };
    }

    // Feature management

    public function isFeatureEnabled(CompanyFeature $feature): bool
    {
        return in_array($feature->value, $this->enabledFeatures, true);
    }

    public function enableFeature(CompanyFeature $feature): void
    {
        if (!$this->isFeatureEnabled($feature)) {
            $this->enabledFeatures[] = $feature->value;
            $this->updatedAt         = new DateTimeImmutable();
        }
    }

    public function disableFeature(CompanyFeature $feature): void
    {
        $key = array_search($feature->value, $this->enabledFeatures, true);
        if ($key !== false) {
            unset($this->enabledFeatures[$key]);
            $this->enabledFeatures = array_values($this->enabledFeatures);
            $this->updatedAt       = new DateTimeImmutable();
        }
    }

    /**
     * @return array<string>
     */
    public function getEnabledFeatures(): array
    {
        return $this->enabledFeatures;
    }

    // Resource limit checks

    public function checkUserLimit(int $currentUserCount): void
    {
        if ($currentUserCount >= $this->maxUsers) {
            throw CompanyResourceLimitException::userLimitReached($this->maxUsers);
        }
    }

    public function checkProjectLimit(int $currentProjectCount): void
    {
        if ($currentProjectCount >= $this->maxProjects) {
            throw CompanyResourceLimitException::projectLimitReached($this->maxProjects);
        }
    }

    public function checkStorageLimit(int $currentStorageMb): void
    {
        if ($currentStorageMb >= $this->maxStorageMb) {
            throw CompanyResourceLimitException::storageLimitReached($this->maxStorageMb);
        }
    }

    // Settings management

    public function updateSettings(
        float $structureCostCoefficient,
        float $employerChargesCoefficient,
        int $annualPaidLeaveDays,
        int $annualRttDays,
    ): void {
        $this->structureCostCoefficient   = $structureCostCoefficient;
        $this->employerChargesCoefficient = $employerChargesCoefficient;
        $this->annualPaidLeaveDays        = $annualPaidLeaveDays;
        $this->annualRttDays              = $annualRttDays;
        $this->updatedAt                  = new DateTimeImmutable();
    }

    public function updateBilling(
        DateTimeImmutable $billingStartDate,
        int $billingDayOfMonth,
        string $currency,
    ): void {
        $this->billingStartDate  = $billingStartDate;
        $this->billingDayOfMonth = $billingDayOfMonth;
        $this->currency          = $currency;
        $this->updatedAt         = new DateTimeImmutable();
    }

    // Calculated values

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    public function isTrial(): bool
    {
        return $this->status->isTrial();
    }

    public function isTrialActive(): bool
    {
        if (!$this->isTrial() || $this->trialEndsAt === null) {
            return false;
        }

        return $this->trialEndsAt > new DateTimeImmutable();
    }

    public function isSuspended(): bool
    {
        return $this->status->isSuspended();
    }

    public function isCancelled(): bool
    {
        return $this->status->isCancelled();
    }

    public function isOperational(): bool
    {
        return $this->status->isOperational();
    }

    public function getGlobalChargeCoefficient(): float
    {
        return $this->structureCostCoefficient + $this->employerChargesCoefficient;
    }

    // Getters

    public function getId(): CompanyId
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): CompanySlug
    {
        return $this->slug;
    }

    public function getStatus(): CompanyStatus
    {
        return $this->status;
    }

    public function getSubscriptionTier(): SubscriptionTier
    {
        return $this->subscriptionTier;
    }

    public function getMaxUsers(): int
    {
        return $this->maxUsers;
    }

    public function getMaxProjects(): int
    {
        return $this->maxProjects;
    }

    public function getMaxStorageMb(): int
    {
        return $this->maxStorageMb;
    }

    public function getBillingStartDate(): ?DateTimeImmutable
    {
        return $this->billingStartDate;
    }

    public function getBillingDayOfMonth(): int
    {
        return $this->billingDayOfMonth;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getStructureCostCoefficient(): float
    {
        return $this->structureCostCoefficient;
    }

    public function getEmployerChargesCoefficient(): float
    {
        return $this->employerChargesCoefficient;
    }

    public function getAnnualPaidLeaveDays(): int
    {
        return $this->annualPaidLeaveDays;
    }

    public function getAnnualRttDays(): int
    {
        return $this->annualRttDays;
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
}
