<?php

declare(strict_types=1);

namespace App\Domain\Company\Repository;

use App\Domain\Company\Entity\Company;
use App\Domain\Company\Exception\CompanyNotFoundException;
use App\Domain\Company\ValueObject\CompanyId;
use App\Domain\Company\ValueObject\CompanySlug;
use App\Domain\Company\ValueObject\CompanyStatus;
use App\Domain\Company\ValueObject\SubscriptionTier;

/**
 * Repository interface for Company aggregate root.
 *
 * Implementations should be in Infrastructure layer.
 */
interface CompanyRepositoryInterface
{
    /**
     * Find a company by its ID.
     *
     * @throws CompanyNotFoundException if the company does not exist
     */
    public function findById(CompanyId $id): Company;

    /**
     * Find a company by its ID, returning null if not found.
     */
    public function findByIdOrNull(CompanyId $id): ?Company;

    /**
     * Find a company by its slug.
     */
    public function findBySlug(CompanySlug $slug): ?Company;

    /**
     * Find all companies with a specific status.
     *
     * @return Company[]
     */
    public function findByStatus(CompanyStatus $status): array;

    /**
     * Find all companies with a specific subscription tier.
     *
     * @return Company[]
     */
    public function findBySubscriptionTier(SubscriptionTier $tier): array;

    /**
     * Find all active companies.
     *
     * @return Company[]
     */
    public function findActive(): array;

    /**
     * Find all trial companies with expired trials.
     *
     * @return Company[]
     */
    public function findExpiredTrials(): array;

    /**
     * Find all companies.
     *
     * @return Company[]
     */
    public function findAll(): array;

    /**
     * Persist a company.
     */
    public function save(Company $company): void;

    /**
     * Remove a company.
     */
    public function delete(Company $company): void;
}
