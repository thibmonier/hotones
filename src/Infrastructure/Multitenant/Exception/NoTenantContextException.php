<?php

declare(strict_types=1);

namespace App\Infrastructure\Multitenant\Exception;

use RuntimeException;

/**
 * Thrown when an operation requires a tenant context but none is set.
 *
 * Typical causes:
 * - Code accessed outside an HTTP request (CLI, test harness without bootstrap).
 * - Tenant middleware did not run before the consumer (firewall ordering issue).
 * - User authenticated without a Company link.
 *
 * Recovery: ensure `TenantMiddleware` ran, or call `TenantContext::setCurrentTenant()`
 * explicitly in CLI/test bootstrap.
 */
final class NoTenantContextException extends RuntimeException
{
    public function __construct(string $message = 'No tenant context set. Ensure TenantMiddleware ran or seed it explicitly in CLI/test bootstrap.')
    {
        parent::__construct($message);
    }
}
