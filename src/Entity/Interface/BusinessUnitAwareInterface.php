<?php

declare(strict_types=1);

namespace App\Entity\Interface;

use App\Entity\BusinessUnit;

/**
 * Optional interface for entities that can be assigned to a BusinessUnit.
 *
 * BusinessUnits provide hierarchical organization within a Company.
 * Implementing this interface allows entities to be filtered/grouped by BU.
 *
 * Note: BusinessUnit assignment is optional (nullable). Entities can belong
 * to a Company without being assigned to any specific BusinessUnit.
 *
 * @see BusinessUnit
 */
interface BusinessUnitAwareInterface
{
    /**
     * Get the BusinessUnit this entity is assigned to (if any).
     *
     * @return BusinessUnit|null The assigned business unit, or null if not assigned
     */
    public function getBusinessUnit(): ?BusinessUnit;

    /**
     * Set the BusinessUnit for this entity.
     *
     * @param BusinessUnit|null $businessUnit The business unit to assign, or null to unassign
     *
     * @return $this Fluent interface
     */
    public function setBusinessUnit(?BusinessUnit $businessUnit): self;
}
