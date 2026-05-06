<?php

declare(strict_types=1);

namespace App\Domain\Project\Exception;

use App\Domain\Project\ValueObject\ProjectStatus;
use App\Domain\Shared\Exception\DomainException;

final class InvalidProjectStatusTransitionException extends DomainException
{
    public static function create(ProjectStatus $from, ProjectStatus $to): self
    {
        return new self(
            sprintf(
                'Cannot transition project status from "%s" to "%s"',
                $from->value,
                $to->value,
            ),
        );
    }
}
