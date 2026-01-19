<?php

declare(strict_types=1);

namespace App\Domain\User\Exception;

use App\Domain\Shared\Exception\DomainException;
use App\Domain\User\ValueObject\UserRole;

/**
 * Exception thrown when an invalid user role change is attempted.
 */
final class InvalidUserRoleChangeException extends DomainException
{
    public static function sameRole(UserRole $role): self
    {
        return new self(
            sprintf('User already has the role "%s".', $role->value),
        );
    }

    public static function cannotChangeOwnRole(): self
    {
        return new self('Users cannot change their own role.');
    }
}
