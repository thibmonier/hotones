<?php

declare(strict_types=1);

namespace App\Infrastructure\Multitenant;

use App\Domain\Shared\ValueObject\TenantId;
use App\Infrastructure\Multitenant\Exception\NoTenantContextException;

/**
 * Holds the current tenant identifier per request lifecycle.
 *
 * Service-scoped (request-scoped via Symfony) container. Set by `TenantMiddleware`
 * at `kernel.request`, consumed by `TenantFilterSubscriber` and entity-aware code.
 *
 * Implements the contract described in `.claude/rules/14-multitenant.md`.
 */
final class TenantContext
{
    private ?TenantId $currentTenant = null;

    public function setCurrentTenant(TenantId $tenantId): void
    {
        $this->currentTenant = $tenantId;
    }

    /**
     * @throws NoTenantContextException if no tenant has been set
     */
    public function getCurrentTenant(): TenantId
    {
        if ($this->currentTenant === null) {
            throw new NoTenantContextException();
        }

        return $this->currentTenant;
    }

    public function hasTenant(): bool
    {
        return $this->currentTenant !== null;
    }

    public function clear(): void
    {
        $this->currentTenant = null;
    }
}
