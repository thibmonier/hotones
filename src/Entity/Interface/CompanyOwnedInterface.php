<?php

declare(strict_types=1);

namespace App\Entity\Interface;

use App\Entity\Company;

/**
 * Marker interface for entities that belong to a Company (tenant-scoped entities).
 *
 * All entities implementing this interface must have a Company relationship
 * and will be automatically filtered by CompanyAwareRepository to prevent
 * cross-tenant data leakage.
 *
 * @see \App\Repository\CompanyAwareRepository
 * @see \App\Security\Voter\CompanyVoter
 */
interface CompanyOwnedInterface
{
    /**
     * Get the Company that owns this entity.
     *
     * @return Company The company this entity belongs to
     */
    public function getCompany(): Company;

    /**
     * Set the Company that owns this entity.
     *
     * @param Company $company The company to assign this entity to
     *
     * @return $this Fluent interface
     */
    public function setCompany(Company $company): self;
}
