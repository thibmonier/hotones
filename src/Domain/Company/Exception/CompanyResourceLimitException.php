<?php

declare(strict_types=1);

namespace App\Domain\Company\Exception;

use App\Domain\Shared\Exception\DomainException;

final class CompanyResourceLimitException extends DomainException
{
    public static function userLimitReached(int $maxUsers): self
    {
        return new self(
            sprintf('User limit of %d has been reached', $maxUsers),
        );
    }

    public static function projectLimitReached(int $maxProjects): self
    {
        return new self(
            sprintf('Project limit of %d has been reached', $maxProjects),
        );
    }

    public static function storageLimitReached(int $maxStorageMb): self
    {
        return new self(
            sprintf('Storage limit of %d MB has been reached', $maxStorageMb),
        );
    }
}
