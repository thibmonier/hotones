<?php

declare(strict_types=1);

namespace App\Domain\Shared\Interface;

use DateTimeImmutable;

interface DomainEventInterface
{
    public function getOccurredOn(): DateTimeImmutable;
}
