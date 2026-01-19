<?php

declare(strict_types=1);

namespace App\Domain\BusinessUnit\Event;

use App\Domain\BusinessUnit\ValueObject\BusinessUnitId;
use App\Domain\Shared\Interface\DomainEventInterface;
use DateTimeImmutable;

/**
 * Domain event raised when a business unit is deactivated.
 */
final readonly class BusinessUnitDeactivatedEvent implements DomainEventInterface
{
    private DateTimeImmutable $occurredOn;

    public function __construct(
        private BusinessUnitId $businessUnitId,
    ) {
        $this->occurredOn = new DateTimeImmutable();
    }

    public static function create(BusinessUnitId $businessUnitId): self
    {
        return new self($businessUnitId);
    }

    public function getBusinessUnitId(): BusinessUnitId
    {
        return $this->businessUnitId;
    }

    public function getOccurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
