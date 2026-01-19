<?php

declare(strict_types=1);

namespace App\Domain\BusinessUnit\Entity;

use App\Domain\BusinessUnit\Event\BusinessUnitActivatedEvent;
use App\Domain\BusinessUnit\Event\BusinessUnitCreatedEvent;
use App\Domain\BusinessUnit\Event\BusinessUnitDeactivatedEvent;
use App\Domain\BusinessUnit\Exception\InvalidBusinessUnitHierarchyException;
use App\Domain\BusinessUnit\ValueObject\BusinessUnitId;
use App\Domain\Company\ValueObject\CompanyId;
use App\Domain\Shared\Interface\AggregateRootInterface;
use App\Domain\Shared\Trait\RecordsDomainEvents;
use App\Domain\User\ValueObject\UserId;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * BusinessUnit aggregate root - represents a hierarchical sub-organization within a Company.
 *
 * BusinessUnits allow companies to organize their operations hierarchically
 * (e.g., Direction → BU Web → Team Mobile) for better reporting and access control.
 */
final class BusinessUnit implements AggregateRootInterface
{
    use RecordsDomainEvents;

    private BusinessUnitId $id;
    private CompanyId $companyId;
    private string $name;
    private ?string $description;
    private bool $active;

    // Hierarchical structure
    private ?BusinessUnitId $parentId;

    // Management
    private ?UserId $managerId;

    // Business objectives
    private ?float $annualRevenueTarget;
    private ?float $annualMarginTarget;
    private ?int $headcountTarget;

    // Accounting integration
    private ?string $costCenter;

    // Timestamps
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt;

    private function __construct(
        BusinessUnitId $id,
        CompanyId $companyId,
        string $name,
    ) {
        $this->id        = $id;
        $this->companyId = $companyId;
        $this->name      = $name;

        // Default values
        $this->description         = null;
        $this->active              = true;
        $this->parentId            = null;
        $this->managerId           = null;
        $this->annualRevenueTarget = null;
        $this->annualMarginTarget  = null;
        $this->headcountTarget     = null;
        $this->costCenter          = null;

        // Timestamps
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = null;
    }

    public static function create(
        BusinessUnitId $id,
        CompanyId $companyId,
        string $name,
        ?BusinessUnitId $parentId = null,
    ): self {
        $businessUnit           = new self($id, $companyId, $name);
        $businessUnit->parentId = $parentId;

        $businessUnit->recordEvent(
            BusinessUnitCreatedEvent::create($id, $companyId, $name, $parentId),
        );

        return $businessUnit;
    }

    // Core information management

    public function updateInfo(
        string $name,
        ?string $description = null,
    ): void {
        $this->name        = $name;
        $this->description = $description;
        $this->updatedAt   = new DateTimeImmutable();
    }

    // Hierarchy management

    /**
     * Set the parent business unit.
     *
     * Note: Circular reference validation should be done at the application layer
     * since it requires loading other BusinessUnit entities.
     */
    public function setParent(?BusinessUnitId $parentId, ?CompanyId $parentCompanyId = null): void
    {
        // Cannot set self as parent
        if ($parentId !== null && $this->id->equals($parentId)) {
            throw InvalidBusinessUnitHierarchyException::cannotSetSelfAsParent();
        }

        // If parentCompanyId is provided, validate same company
        if ($parentId !== null && $parentCompanyId !== null && !$this->companyId->equals($parentCompanyId)) {
            throw InvalidBusinessUnitHierarchyException::parentFromDifferentCompany();
        }

        $this->parentId  = $parentId;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function removeParent(): void
    {
        $this->parentId  = null;
        $this->updatedAt = new DateTimeImmutable();
    }

    // Manager management

    public function assignManager(UserId $managerId): void
    {
        $this->managerId = $managerId;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function removeManager(): void
    {
        $this->managerId = null;
        $this->updatedAt = new DateTimeImmutable();
    }

    // Business objectives management

    public function updateBusinessObjectives(
        ?float $annualRevenueTarget = null,
        ?float $annualMarginTarget = null,
        ?int $headcountTarget = null,
    ): void {
        if ($annualRevenueTarget !== null && $annualRevenueTarget < 0) {
            throw new InvalidArgumentException('Annual revenue target cannot be negative.');
        }

        if ($annualMarginTarget !== null && ($annualMarginTarget < 0 || $annualMarginTarget > 100)) {
            throw new InvalidArgumentException('Annual margin target must be between 0 and 100.');
        }

        if ($headcountTarget !== null && $headcountTarget < 0) {
            throw new InvalidArgumentException('Headcount target cannot be negative.');
        }

        $this->annualRevenueTarget = $annualRevenueTarget;
        $this->annualMarginTarget  = $annualMarginTarget;
        $this->headcountTarget     = $headcountTarget;
        $this->updatedAt           = new DateTimeImmutable();
    }

    // Accounting integration

    public function updateCostCenter(?string $costCenter): void
    {
        $this->costCenter = $costCenter;
        $this->updatedAt  = new DateTimeImmutable();
    }

    // Activation status management

    public function activate(): void
    {
        if ($this->active) {
            return;
        }

        $this->active    = true;
        $this->updatedAt = new DateTimeImmutable();

        $this->recordEvent(BusinessUnitActivatedEvent::create($this->id));
    }

    public function deactivate(): void
    {
        if (!$this->active) {
            return;
        }

        $this->active    = false;
        $this->updatedAt = new DateTimeImmutable();

        $this->recordEvent(BusinessUnitDeactivatedEvent::create($this->id));
    }

    // Calculated values

    public function isRoot(): bool
    {
        return $this->parentId === null;
    }

    public function hasManager(): bool
    {
        return $this->managerId !== null;
    }

    public function hasBusinessObjectives(): bool
    {
        return $this->annualRevenueTarget !== null
            || $this->annualMarginTarget  !== null
            || $this->headcountTarget     !== null;
    }

    // Getters

    public function getId(): BusinessUnitId
    {
        return $this->id;
    }

    public function getCompanyId(): CompanyId
    {
        return $this->companyId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getParentId(): ?BusinessUnitId
    {
        return $this->parentId;
    }

    public function getManagerId(): ?UserId
    {
        return $this->managerId;
    }

    public function getAnnualRevenueTarget(): ?float
    {
        return $this->annualRevenueTarget;
    }

    public function getAnnualMarginTarget(): ?float
    {
        return $this->annualMarginTarget;
    }

    public function getHeadcountTarget(): ?int
    {
        return $this->headcountTarget;
    }

    public function getCostCenter(): ?string
    {
        return $this->costCenter;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
