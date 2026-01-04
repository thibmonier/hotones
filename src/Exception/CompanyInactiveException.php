<?php

declare(strict_types=1);

namespace App\Exception;

use Throwable;

/**
 * Exception thrown when attempting to access an inactive company.
 *
 * This occurs when:
 * - Company is suspended (payment issue, policy violation)
 * - Company is cancelled (subscription ended)
 * - Trial period has expired
 *
 * Users should be redirected to a company selection page or
 * shown a reactivation message.
 */
class CompanyInactiveException extends TenantIsolationException
{
    private ?string $companyStatus = null;

    public function __construct(
        string $message = 'Company is not active',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getCompanyStatus(): ?string
    {
        return $this->companyStatus;
    }

    public function setCompanyStatus(?string $companyStatus): self
    {
        $this->companyStatus = $companyStatus;

        return $this;
    }

    /**
     * Create exception with company details.
     *
     * @param int    $companyId     Inactive company ID
     * @param string $companyStatus Company status (suspended/cancelled/trial)
     */
    public static function create(int $companyId, string $companyStatus): self
    {
        $message = sprintf(
            'Company %d is %s and cannot be accessed',
            $companyId,
            $companyStatus,
        );

        $exception = new self($message);
        $exception->setAttemptedCompanyId($companyId);
        $exception->setCompanyStatus($companyStatus);

        return $exception;
    }
}
