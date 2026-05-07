<?php

declare(strict_types=1);

namespace App\Domain\Contributor\Event;

use App\Domain\Company\ValueObject\CompanyId;
use App\Domain\Contributor\ValueObject\ContributorId;
use App\Domain\Contributor\ValueObject\PersonName;
use App\Domain\Shared\Interface\DomainEventInterface;
use DateTimeImmutable;

final readonly class ContributorCreatedEvent implements DomainEventInterface
{
    private function __construct(
        public ContributorId $contributorId,
        public CompanyId $companyId,
        public PersonName $name,
        public DateTimeImmutable $occurredAt,
    ) {
    }

    public static function create(
        ContributorId $contributorId,
        CompanyId $companyId,
        PersonName $name,
    ): self {
        return new self($contributorId, $companyId, $name, new DateTimeImmutable());
    }

    public function getOccurredOn(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function getAggregateId(): string
    {
        return $this->contributorId->getValue();
    }
}
