<?php

declare(strict_types=1);

namespace App\Domain\Shared\Interface;

interface AggregateRootInterface
{
    /**
     * @return array<DomainEventInterface>
     */
    public function pullDomainEvents(): array;
}
