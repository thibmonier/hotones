<?php

declare(strict_types=1);

namespace App\Domain\BusinessUnit\Repository;

use App\Domain\BusinessUnit\Entity\BusinessUnit;
use App\Domain\BusinessUnit\Exception\BusinessUnitNotFoundException;
use App\Domain\BusinessUnit\ValueObject\BusinessUnitId;
use App\Domain\Company\ValueObject\CompanyId;
use App\Domain\User\ValueObject\UserId;

/**
 * Repository interface for BusinessUnit aggregate root.
 */
interface BusinessUnitRepositoryInterface
{
    /**
     * Find a business unit by ID.
     *
     * @throws BusinessUnitNotFoundException
     */
    public function findById(BusinessUnitId $id): BusinessUnit;

    /**
     * Find a business unit by ID, or null if not found.
     */
    public function findByIdOrNull(BusinessUnitId $id): ?BusinessUnit;

    /**
     * Find a business unit by name within a company.
     */
    public function findByNameAndCompany(string $name, CompanyId $companyId): ?BusinessUnit;

    /**
     * Find all business units for a company.
     *
     * @return BusinessUnit[]
     */
    public function findByCompany(CompanyId $companyId): array;

    /**
     * Find active business units for a company.
     *
     * @return BusinessUnit[]
     */
    public function findActiveByCompany(CompanyId $companyId): array;

    /**
     * Find root business units (without parent) for a company.
     *
     * @return BusinessUnit[]
     */
    public function findRootsByCompany(CompanyId $companyId): array;

    /**
     * Find children of a business unit.
     *
     * @return BusinessUnit[]
     */
    public function findChildren(BusinessUnitId $parentId): array;

    /**
     * Find business units managed by a specific user.
     *
     * @return BusinessUnit[]
     */
    public function findByManager(UserId $managerId): array;

    /**
     * Count business units for a company.
     */
    public function countByCompany(CompanyId $companyId): int;

    /**
     * Count active business units for a company.
     */
    public function countActiveByCompany(CompanyId $companyId): int;

    /**
     * Check if a name is already used within a company (optionally excluding a specific BU).
     */
    public function existsByNameInCompany(
        string $name,
        CompanyId $companyId,
        ?BusinessUnitId $excludeId = null,
    ): bool;

    /**
     * Save a business unit (create or update).
     */
    public function save(BusinessUnit $businessUnit): void;

    /**
     * Delete a business unit.
     */
    public function delete(BusinessUnit $businessUnit): void;
}
