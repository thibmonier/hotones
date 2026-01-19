<?php

declare(strict_types=1);

namespace App\Domain\Company\Exception;

use App\Domain\Company\ValueObject\CompanyId;
use App\Domain\Company\ValueObject\CompanySlug;
use App\Domain\Shared\Exception\DomainException;

final class CompanyNotFoundException extends DomainException
{
    public static function withId(CompanyId $id): self
    {
        return new self(
            sprintf('Company with ID "%s" not found', $id->getValue()),
        );
    }

    public static function withSlug(CompanySlug $slug): self
    {
        return new self(
            sprintf('Company with slug "%s" not found', $slug->getValue()),
        );
    }
}
