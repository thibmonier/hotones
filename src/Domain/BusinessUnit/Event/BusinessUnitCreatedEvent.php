<?php

declare(strict_types=1);

namespace App\Domain\BusinessUnit\Event;

use App\Domain\BusinessUnit\ValueObject\BusinessUnitId;
use App\Domain\Company\ValueObject\CompanyId;
use App\Domain\Shared\Interface\DomainEventInterface;
use DateTimeImmutable;

/**
 * Domain event raised when a new business unit is created.
 */
final readonly class BusinessUnitCreatedEvent implements DomainEventInterface
{
    private DateTimeImmutable $occurredOn;

    public function __construct(
        private BusinessUnitId $businessUnitId,
        private CompanyId $companyId,
        private string $name,
        private ?BusinessUnitId $parentId = null,
    ) {
        $this->occurredOn = new DateTimeImmutable();
    }

    public static function create(
        BusinessUnitId $businessUnitId,
        CompanyId $companyId,
        string $name,
        ?BusinessUnitId $parentId = null,
    ): self {
        return new self($businessUnitId, $companyId, $name, $parentId);
    }

    public function getBusinessUnitId(): BusinessUnitId
    {
        return $this->businessUnitId;
    }

    public function getCompanyId(): CompanyId
    {
        return $this->companyId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getParentId(): ?BusinessUnitId
    {
        return $this->parentId;
    }

    public function getOccurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
