<?php

declare(strict_types=1);

namespace App\Domain\BusinessUnit\Exception;

use App\Domain\BusinessUnit\ValueObject\BusinessUnitId;
use App\Domain\Shared\Exception\DomainException;

/**
 * Exception thrown when a business unit cannot be found.
 */
final class BusinessUnitNotFoundException extends DomainException
{
    public static function withId(BusinessUnitId $businessUnitId): self
    {
        return new self(
            sprintf('Business unit with ID "%s" was not found.', $businessUnitId->getValue())
        );
    }

    public static function withName(string $name): self
    {
        return new self(
            sprintf('Business unit with name "%s" was not found.', $name)
        );
    }
}
