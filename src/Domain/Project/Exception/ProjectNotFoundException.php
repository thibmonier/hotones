<?php

declare(strict_types=1);

namespace App\Domain\Project\Exception;

use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\Shared\Exception\DomainException;

final class ProjectNotFoundException extends DomainException
{
    public static function withId(ProjectId $id): self
    {
        return new self(
            sprintf('Project with ID "%s" not found', $id->value()),
        );
    }

    public static function withReference(string $reference): self
    {
        return new self(
            sprintf('Project with reference "%s" not found', $reference),
        );
    }
}
