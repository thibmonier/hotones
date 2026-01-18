<?php

declare(strict_types=1);

namespace App\Domain\Company\Event;

use App\Domain\Company\ValueObject\CompanyId;
use App\Domain\Company\ValueObject\CompanyStatus;
use App\Domain\Shared\Interface\DomainEventInterface;

final readonly class CompanyStatusChangedEvent implements DomainEventInterface
{
    public function __construct(
        private CompanyId $companyId,
        private CompanyStatus $previousStatus,
        private CompanyStatus $newStatus,
        private \DateTimeImmutable $occurredOn,
    ) {
    }

    public static function create(
        CompanyId $companyId,
        CompanyStatus $previousStatus,
        CompanyStatus $newStatus,
    ): self {
        return new self($companyId, $previousStatus, $newStatus, new \DateTimeImmutable());
    }

    public function getCompanyId(): CompanyId
    {
        return $this->companyId;
    }

    public function getPreviousStatus(): CompanyStatus
    {
        return $this->previousStatus;
    }

    public function getNewStatus(): CompanyStatus
    {
        return $this->newStatus;
    }

    public function getOccurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
