<?php

declare(strict_types=1);

namespace App\Domain\User\Exception;

use App\Domain\Shared\Exception\DomainException;
use App\Domain\User\ValueObject\UserId;

/**
 * Exception thrown when a user cannot be found.
 */
final class UserNotFoundException extends DomainException
{
    public static function withId(UserId $userId): self
    {
        return new self(
            sprintf('User with ID "%s" was not found.', $userId->getValue()),
        );
    }

    public static function withEmail(string $email): self
    {
        return new self(
            sprintf('User with email "%s" was not found.', $email),
        );
    }
}
