<?php

declare(strict_types=1);

namespace App\Domain\Company\Event;

use App\Domain\Company\ValueObject\CompanyId;
use App\Domain\Company\ValueObject\CompanySlug;
use App\Domain\Company\ValueObject\SubscriptionTier;
use App\Domain\Shared\Interface\DomainEventInterface;
use DateTimeImmutable;

final readonly class CompanyCreatedEvent implements DomainEventInterface
{
    public function __construct(
        private CompanyId $companyId,
        private string $name,
        private CompanySlug $slug,
        private SubscriptionTier $subscriptionTier,
        private DateTimeImmutable $occurredOn,
    ) {
    }

    public static function create(
        CompanyId $companyId,
        string $name,
        CompanySlug $slug,
        SubscriptionTier $subscriptionTier,
    ): self {
        return new self($companyId, $name, $slug, $subscriptionTier, new DateTimeImmutable());
    }

    public function getCompanyId(): CompanyId
    {
        return $this->companyId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): CompanySlug
    {
        return $this->slug;
    }

    public function getSubscriptionTier(): SubscriptionTier
    {
        return $this->subscriptionTier;
    }

    public function getOccurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
