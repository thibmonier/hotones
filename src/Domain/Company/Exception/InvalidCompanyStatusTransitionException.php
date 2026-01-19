<?php

declare(strict_types=1);

namespace App\Domain\Company\Exception;

use App\Domain\Company\ValueObject\CompanyStatus;
use App\Domain\Shared\Exception\DomainException;

final class InvalidCompanyStatusTransitionException extends DomainException
{
    public static function create(CompanyStatus $from, CompanyStatus $to): self
    {
        return new self(
            sprintf(
                'Cannot transition company status from "%s" to "%s"',
                $from->value,
                $to->value,
            ),
        );
    }
}
