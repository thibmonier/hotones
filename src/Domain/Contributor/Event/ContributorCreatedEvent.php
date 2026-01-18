<?php

declare(strict_types=1);

namespace App\Domain\Contributor\Event;

use App\Domain\Company\ValueObject\CompanyId;
use App\Domain\Contributor\ValueObject\ContributorId;
use App\Domain\Shared\Interface\DomainEventInterface;
use App\Domain\Shared\ValueObject\Email;

/**
 * Domain event raised when a new contributor is created.
 */
final readonly class ContributorCreatedEvent implements DomainEventInterface
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private ContributorId $contributorId,
        private CompanyId $companyId,
        private Email $email,
        private string $firstName,
        private string $lastName,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public static function create(
        ContributorId $contributorId,
        CompanyId $companyId,
        Email $email,
        string $firstName,
        string $lastName,
    ): self {
        return new self($contributorId, $companyId, $email, $firstName, $lastName);
    }

    public function getContributorId(): ContributorId
    {
        return $this->contributorId;
    }

    public function getCompanyId(): CompanyId
    {
        return $this->companyId;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getOccurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
