<?php

declare(strict_types=1);

namespace App\Domain\BusinessUnit\Exception;

use App\Domain\BusinessUnit\ValueObject\BusinessUnitId;
use App\Domain\Shared\Exception\DomainException;

/**
 * Exception thrown when an invalid business unit hierarchy operation is attempted.
 */
final class InvalidBusinessUnitHierarchyException extends DomainException
{
    public static function cannotSetSelfAsParent(): self
    {
        return new self('A business unit cannot be its own parent.');
    }

    public static function cannotSetDescendantAsParent(BusinessUnitId $descendantId): self
    {
        return new self(
            sprintf(
                'Cannot set descendant business unit "%s" as parent (would create circular reference).',
                $descendantId->getValue()
            )
        );
    }

    public static function parentFromDifferentCompany(): self
    {
        return new self('Parent business unit must belong to the same company.');
    }
}
