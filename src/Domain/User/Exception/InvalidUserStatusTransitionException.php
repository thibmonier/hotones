<?php

declare(strict_types=1);

namespace App\Domain\User\Exception;

use App\Domain\Shared\Exception\DomainException;
use App\Domain\User\ValueObject\UserStatus;

/**
 * Exception thrown when an invalid user status transition is attempted.
 */
final class InvalidUserStatusTransitionException extends DomainException
{
    public static function create(UserStatus $from, UserStatus $to): self
    {
        $allowedTransitions = $from->allowedTransitions();
        $allowedNames       = array_map(
            fn (UserStatus $status) => $status->value,
            $allowedTransitions,
        );

        return new self(
            sprintf(
                'Cannot transition user status from "%s" to "%s". Allowed transitions: [%s].',
                $from->value,
                $to->value,
                implode(', ', $allowedNames),
            ),
        );
    }
}
