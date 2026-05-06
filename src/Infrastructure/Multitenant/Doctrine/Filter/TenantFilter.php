<?php

declare(strict_types=1);

namespace App\Infrastructure\Multitenant\Doctrine\Filter;

use App\Domain\Shared\Tenant\TenantAwareInterface;
use App\Entity\Interface\CompanyOwnedInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

/**
 * Doctrine SQL filter that scopes every query on `TenantAwareInterface`
 * entities to the current tenant.
 *
 * Activation: `TenantFilterSubscriber` enables the filter at `kernel.request`
 * after `TenantMiddleware` resolved the tenant from the security context,
 * setting parameter `tenantId` to the current Company id.
 *
 * Behavior per query:
 *   - Entity does not implement `TenantAwareInterface` → no constraint added.
 *   - Entity implements `TenantAwareInterface` but tenant column unresolved →
 *     no constraint (defensive — should not happen if mapping is correct).
 *   - Otherwise → injects `<alias>.<company_id_column> = <quoted_tenant_id>`.
 *
 * Bypass: superadmin reports may temporarily disable the filter via
 * `$em->getFilters()->disable('tenant_filter')`. Re-enable defensively after
 * the cross-tenant query.
 */
final class TenantFilter extends SQLFilter
{
    /**
     * Convention column name for the tenant link on legacy entities.
     *
     * 99% of entities link to Company via the standard `company_id` foreign
     * key (Doctrine ORM default). For the rare entity that uses a different
     * column name, override via an ad-hoc filter or rename the join column
     * before adopting `TenantAwareInterface`.
     */
    private const string DEFAULT_TENANT_COLUMN = 'company_id';

    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
    {
        $class = $targetEntity->getName();

        // Two markers are honored:
        //   - `TenantAwareInterface` (Domain Shared): forward-compatible marker
        //     for future DDD-aligned entities under `src/Domain/`.
        //   - `CompanyOwnedInterface` (legacy `App\Entity\Interface`): every
        //     existing tenant-scoped entity in `src/Entity/` already implements
        //     this. Reuse it to avoid touching 52 files for this story.
        if (
            !is_subclass_of($class, TenantAwareInterface::class)
            && !is_subclass_of($class, CompanyOwnedInterface::class)
        ) {
<<<<<<< fix/sec-multitenant-fix-001-tenant-filter-find
=======
        // Skip entities that opted out (do not implement the marker interface).
        if (!is_subclass_of($targetEntity->getName(), TenantAwareInterface::class)) {
>>>>>>> main
            return '';
        }

        // SQLFilter quotes the parameter for us via getParameter().
        $tenantId = $this->getParameter('tenantId');

        return sprintf('%s.%s = %s', $targetTableAlias, self::DEFAULT_TENANT_COLUMN, $tenantId);
    }
}
