<?php

declare(strict_types=1);

namespace App\Domain\Contributor\Repository;

use App\Domain\Company\ValueObject\CompanyId;
use App\Domain\Contributor\Entity\Contributor;
use App\Domain\Contributor\ValueObject\ContributorId;

/**
 * @see ADR-0008 ACL pattern
 */
interface ContributorRepositoryInterface
{
    public function findById(ContributorId $id): Contributor;

    public function findByIdOrNull(ContributorId $id): ?Contributor;

    /**
     * @return array<Contributor>
     */
    public function findActive(): array;

    /**
     * @return array<Contributor>
     */
    public function findByCompanyId(CompanyId $companyId): array;

    /**
     * @return array<Contributor>
     */
    public function findByManagerId(ContributorId $managerId): array;

    public function save(Contributor $contributor): void;

    public function delete(Contributor $contributor): void;
}
