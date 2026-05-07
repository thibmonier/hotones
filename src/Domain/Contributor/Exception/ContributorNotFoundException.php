<?php

declare(strict_types=1);

namespace App\Domain\Contributor\Exception;

use App\Domain\Contributor\ValueObject\ContributorId;
use RuntimeException;

final class ContributorNotFoundException extends RuntimeException
{
    public static function withId(ContributorId $id): self
    {
        return new self(sprintf('Contributor %s not found', (string) $id));
    }
}
