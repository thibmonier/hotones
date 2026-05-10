<?php

declare(strict_types=1);

namespace App\Domain\Contributor\Entity;

use App\Domain\Company\ValueObject\CompanyId;
use App\Domain\Contributor\Event\ContributorCreatedEvent;
use App\Domain\Contributor\ValueObject\ContractStatus;
use App\Domain\Contributor\ValueObject\ContributorId;
use App\Domain\Contributor\ValueObject\PersonName;
use App\Domain\Shared\Interface\AggregateRootInterface;
use App\Domain\Shared\Trait\RecordsDomainEvents;
use DateTimeImmutable;

/**
 * Contributor aggregate root.
 *
 * Represents a person working in a company (employee, freelancer, etc.).
 * Phase 2 ACL : works with legacy `App\Entity\Contributor` via translators.
 *
 * @see ADR-0008 ACL pattern
 */
final class Contributor implements AggregateRootInterface
{
    use RecordsDomainEvents;
    private ?string $email;
    private ?ContributorId $managerId;
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt;

    private function __construct(
        private ContributorId $id,
        private CompanyId $companyId,
        private PersonName $name,
        private ContractStatus $status,
    ) {
        $this->email = null;
        $this->managerId = null;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = null;
    }

    public static function create(
        ContributorId $id,
        CompanyId $companyId,
        PersonName $name,
    ): self {
        $contributor = new self($id, $companyId, $name, ContractStatus::ACTIVE);

        $contributor->recordEvent(ContributorCreatedEvent::create($id, $companyId, $name));

        return $contributor;
    }

    /**
     * Reconstitute from persisted state — used by ACL adapters.
     * Does NOT record domain events.
     *
     * @param array{
     *     email?: ?string,
     *     managerId?: ?ContributorId,
     *     createdAt?: ?DateTimeImmutable,
     *     updatedAt?: ?DateTimeImmutable,
     * } $extra
     */
    public static function reconstitute(
        ContributorId $id,
        CompanyId $companyId,
        PersonName $name,
        ContractStatus $status,
        array $extra = [],
    ): self {
        $contributor = new self($id, $companyId, $name, $status);
        $contributor->email = $extra['email'] ?? null;
        $contributor->managerId = $extra['managerId'] ?? null;
        $contributor->createdAt = $extra['createdAt'] ?? new DateTimeImmutable();
        $contributor->updatedAt = $extra['updatedAt'] ?? null;

        return $contributor;
    }

    // Mutations

    public function rename(PersonName $newName): void
    {
        if ($this->name->equals($newName)) {
            return;
        }

        $this->name = $newName;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function deactivate(): void
    {
        if (!$this->status->isActive()) {
            return;
        }

        $this->status = ContractStatus::INACTIVE;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function reactivate(): void
    {
        if ($this->status->isActive()) {
            return;
        }

        $this->status = ContractStatus::ACTIVE;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function setManager(?ContributorId $managerId): void
    {
        $this->managerId = $managerId;
        $this->updatedAt = new DateTimeImmutable();
    }

    // Getters

    public function getId(): ContributorId
    {
        return $this->id;
    }

    public function getCompanyId(): CompanyId
    {
        return $this->companyId;
    }

    public function getName(): PersonName
    {
        return $this->name;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getStatus(): ContractStatus
    {
        return $this->status;
    }

    public function getManagerId(): ?ContributorId
    {
        return $this->managerId;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }
}
