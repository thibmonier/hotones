<?php

declare(strict_types=1);

namespace App\Domain\Contributor\Exception;

use App\Domain\Contributor\ValueObject\ContributorId;
use App\Domain\Shared\Exception\DomainException;

/**
 * Exception thrown when a contributor cannot be found.
 */
final class ContributorNotFoundException extends DomainException
{
    public static function withId(ContributorId $contributorId): self
    {
        return new self(
            sprintf('Contributor with ID "%s" was not found.', $contributorId->getValue()),
        );
    }

    public static function withEmail(string $email): self
    {
        return new self(
            sprintf('Contributor with email "%s" was not found.', $email),
        );
    }
}
