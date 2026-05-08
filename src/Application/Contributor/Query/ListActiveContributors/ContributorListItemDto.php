<?php

declare(strict_types=1);

namespace App\Application\Contributor\Query\ListActiveContributors;

use App\Domain\Contributor\Entity\Contributor;
use DateTimeInterface;

/**
 * DTO read-only exposé côté API JSON.
 *
 * Sprint-018 Phase 3 strangler fig Contributor BC.
 */
final readonly class ContributorListItemDto
{
    public function __construct(
        public int $id,
        public int $companyId,
        public string $firstName,
        public string $lastName,
        public string $fullName,
        public ?string $email,
        public string $status,
        public ?int $managerId,
        public string $createdAt,
    ) {
    }

    public static function fromAggregate(Contributor $contributor): self
    {
        $name = $contributor->getName();

        return new self(
            id: $contributor->getId()->toLegacyInt(),
            companyId: $contributor->getCompanyId()->toLegacyInt(),
            firstName: $name->getFirstName(),
            lastName: $name->getLastName(),
            fullName: $name->getFullName(),
            email: $contributor->getEmail(),
            status: $contributor->getStatus()->value,
            managerId: $contributor->getManagerId()?->toLegacyInt(),
            createdAt: $contributor->getCreatedAt()->format(DateTimeInterface::ATOM),
        );
    }

    /**
     * @return array{id: int, companyId: int, firstName: string, lastName: string, fullName: string, email: ?string, status: string, managerId: ?int, createdAt: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'companyId' => $this->companyId,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'fullName' => $this->fullName,
            'email' => $this->email,
            'status' => $this->status,
            'managerId' => $this->managerId,
            'createdAt' => $this->createdAt,
        ];
    }
}
