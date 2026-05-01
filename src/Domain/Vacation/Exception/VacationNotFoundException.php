<?php

declare(strict_types=1);

namespace App\Domain\Vacation\Exception;

use App\Domain\Vacation\ValueObject\VacationId;
use DomainException;

final class VacationNotFoundException extends DomainException
{
    public static function withId(VacationId $id): self
    {
        return new self(
            sprintf('Vacation not found with id: %s', $id->getValue()),
        );
    }
}
