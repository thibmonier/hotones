<?php

declare(strict_types=1);

namespace App\Domain\Client\Exception;

use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Shared\Exception\DomainException;

final class ClientNotFoundException extends DomainException
{
    public static function withId(ClientId $id): self
    {
        return new self(sprintf('Client with ID "%s" was not found', $id->getValue()));
    }
}
