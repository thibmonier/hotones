<?php

declare(strict_types=1);

namespace App\Domain\Shared\Tenant;

/**
 * Marker interface for entities scoped to a tenant.
 *
 * Implementing this interface signals to `TenantFilter` (Doctrine SQLFilter)
 * that the entity must be filtered by `company_id` at the SQL layer when
 * `TenantContext` is set.
 *
 * Adoption strategy (sprint-007 SEC-MULTITENANT-002):
 *   - Existing entities with `private Company $company` add `implements TenantAwareInterface`.
 *   - Zero schema change (the `company_id` column already exists).
 *   - Filter reads metadata to discover the join column name.
 *
 * Design choice: marker interface vs trait
 *   - Interface: explicit, supports `instanceof` and `interface_implements()`,
 *     no risk of method/property collision with existing entity code.
 *   - Trait: would need a property `private string $tenantId`, requiring
 *     migration of existing schema and data backfill — too costly for V1.
 */
interface TenantAwareInterface
{
}
