<?php

declare(strict_types=1);

namespace App\Domain\Company\Event;

use App\Domain\Company\ValueObject\CompanyId;
use App\Domain\Company\ValueObject\SubscriptionTier;
use App\Domain\Shared\Interface\DomainEventInterface;

final readonly class CompanySubscriptionChangedEvent implements DomainEventInterface
{
    public function __construct(
        private CompanyId $companyId,
        private SubscriptionTier $previousTier,
        private SubscriptionTier $newTier,
        private \DateTimeImmutable $occurredOn,
    ) {
    }

    public static function create(
        CompanyId $companyId,
        SubscriptionTier $previousTier,
        SubscriptionTier $newTier,
    ): self {
        return new self($companyId, $previousTier, $newTier, new \DateTimeImmutable());
    }

    public function getCompanyId(): CompanyId
    {
        return $this->companyId;
    }

    public function getPreviousTier(): SubscriptionTier
    {
        return $this->previousTier;
    }

    public function getNewTier(): SubscriptionTier
    {
        return $this->newTier;
    }

    public function isUpgrade(): bool
    {
        return $this->newTier->includes($this->previousTier)
            && $this->newTier !== $this->previousTier;
    }

    public function isDowngrade(): bool
    {
        return $this->previousTier->includes($this->newTier)
            && $this->newTier !== $this->previousTier;
    }

    public function getOccurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
