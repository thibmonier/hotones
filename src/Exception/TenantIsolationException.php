<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;
use Throwable;

/**
 * Base exception for tenant isolation violations.
 *
 * This exception is thrown when tenant isolation rules are violated,
 * such as attempting to access data from a different company or
 * missing company context when required.
 *
 * Security Note: All tenant isolation violations should be logged
 * for security auditing purposes.
 */
class TenantIsolationException extends RuntimeException
{
    private ?int $userId             = null;
    private ?int $attemptedCompanyId = null;
    private ?int $currentCompanyId   = null;

    public function __construct(
        string $message = 'Tenant isolation violation detected',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getAttemptedCompanyId(): ?int
    {
        return $this->attemptedCompanyId;
    }

    public function setAttemptedCompanyId(?int $attemptedCompanyId): self
    {
        $this->attemptedCompanyId = $attemptedCompanyId;

        return $this;
    }

    public function getCurrentCompanyId(): ?int
    {
        return $this->currentCompanyId;
    }

    public function setCurrentCompanyId(?int $currentCompanyId): self
    {
        $this->currentCompanyId = $currentCompanyId;

        return $this;
    }

    /**
     * Get security context for logging.
     *
     * @return array<string, mixed> Security context data
     */
    public function getSecurityContext(): array
    {
        return [
            'user_id'              => $this->userId,
            'attempted_company_id' => $this->attemptedCompanyId,
            'current_company_id'   => $this->currentCompanyId,
            'message'              => $this->getMessage(),
            'file'                 => $this->getFile(),
            'line'                 => $this->getLine(),
        ];
    }
}
