<?php

declare(strict_types=1);

namespace App\Exception;

use Throwable;

/**
 * Exception thrown when company context cannot be determined.
 *
 * This occurs when:
 * - User is not authenticated
 * - JWT token is missing company_id claim
 * - Session doesn't contain company context
 * - User has no assigned company
 */
class CompanyContextMissingException extends TenantIsolationException
{
    public function __construct(
        string $message = 'Company context is missing or cannot be determined',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
