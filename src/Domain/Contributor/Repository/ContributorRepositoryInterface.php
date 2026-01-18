<?php

declare(strict_types=1);

namespace App\Domain\Contributor\Repository;

use App\Domain\Company\ValueObject\CompanyId;
use App\Domain\Contributor\Entity\Contributor;
use App\Domain\Contributor\Exception\ContributorNotFoundException;
use App\Domain\Contributor\ValueObject\ContributorId;
use App\Domain\Shared\ValueObject\Email;
use App\Domain\User\ValueObject\UserId;

/**
 * Repository interface for Contributor aggregate root.
 */
interface ContributorRepositoryInterface
{
    /**
     * Find a contributor by ID.
     *
     * @throws ContributorNotFoundException
     */
    public function findById(ContributorId $id): Contributor;

    /**
     * Find a contributor by ID, or null if not found.
     */
    public function findByIdOrNull(ContributorId $id): ?Contributor;

    /**
     * Find a contributor by email within a company.
     */
    public function findByEmailAndCompany(Email $email, CompanyId $companyId): ?Contributor;

    /**
     * Find a contributor by their linked user account.
     */
    public function findByUserId(UserId $userId): ?Contributor;

    /**
     * Find all contributors for a company.
     *
     * @return Contributor[]
     */
    public function findByCompany(CompanyId $companyId): array;

    /**
     * Find active contributors for a company.
     *
     * @return Contributor[]
     */
    public function findActiveByCompany(CompanyId $companyId): array;

    /**
     * Find contributors managed by a specific contributor.
     *
     * @return Contributor[]
     */
    public function findByManager(ContributorId $managerId): array;

    /**
     * Count contributors for a company.
     */
    public function countByCompany(CompanyId $companyId): int;

    /**
     * Count active contributors for a company.
     */
    public function countActiveByCompany(CompanyId $companyId): int;

    /**
     * Check if an email is already used within a company.
     */
    public function existsByEmailInCompany(Email $email, CompanyId $companyId): bool;

    /**
     * Save a contributor (create or update).
     */
    public function save(Contributor $contributor): void;

    /**
     * Delete a contributor.
     */
    public function delete(Contributor $contributor): void;
}
