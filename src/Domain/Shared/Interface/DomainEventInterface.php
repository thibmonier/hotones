<?php

declare(strict_types=1);

namespace App\Domain\Shared\Interface;

interface DomainEventInterface
{
    public function getOccurredOn(): \DateTimeImmutable;
}
