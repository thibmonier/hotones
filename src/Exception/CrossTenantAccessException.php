<?php

declare(strict_types=1);

namespace App\Exception;

use Throwable;

/**
 * Exception thrown when a user attempts to access data from another company.
 *
 * This is a critical security violation that indicates either:
 * - Malicious attempt to access cross-tenant data
 * - Programming error in data scoping logic
 * - Compromised authentication token
 *
 * All occurrences must be logged and investigated.
 */
class CrossTenantAccessException extends TenantIsolationException
{
    public function __construct(
        string $message = 'Attempt to access data from different company detected',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create exception with full context.
     *
     * @param int $userId             User attempting access
     * @param int $userCompanyId      User's current company ID
     * @param int $attemptedCompanyId Company ID being accessed
     */
    public static function create(
        int $userId,
        int $userCompanyId,
        int $attemptedCompanyId
    ): self {
        $message = sprintf(
            'User %d (company %d) attempted to access data from company %d',
            $userId,
            $userCompanyId,
            $attemptedCompanyId,
        );

        $exception = new self($message);
        $exception->setUserId($userId);
        $exception->setCurrentCompanyId($userCompanyId);
        $exception->setAttemptedCompanyId($attemptedCompanyId);

        return $exception;
    }
}
