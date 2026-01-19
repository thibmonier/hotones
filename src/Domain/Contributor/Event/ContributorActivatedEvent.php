<?php

declare(strict_types=1);

namespace App\Domain\Contributor\Event;

use App\Domain\Contributor\ValueObject\ContributorId;
use App\Domain\Shared\Interface\DomainEventInterface;
use DateTimeImmutable;

/**
 * Domain event raised when a contributor is activated.
 */
final readonly class ContributorActivatedEvent implements DomainEventInterface
{
    private DateTimeImmutable $occurredOn;

    public function __construct(
        private ContributorId $contributorId,
    ) {
        $this->occurredOn = new DateTimeImmutable();
    }

    public static function create(ContributorId $contributorId): self
    {
        return new self($contributorId);
    }

    public function getContributorId(): ContributorId
    {
        return $this->contributorId;
    }

    public function getOccurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
