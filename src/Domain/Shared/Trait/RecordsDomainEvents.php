<?php

declare(strict_types=1);

namespace App\Domain\Shared\Trait;

use App\Domain\Shared\Interface\DomainEventInterface;

/**
 * Foundation trait for DDD aggregates. Consumers will land in EPIC-001 phases 1-3.
 *
 * @phpstan-ignore trait.unused
 */
trait RecordsDomainEvents
{
    /** @var array<DomainEventInterface> */
    private array $domainEvents = [];

    protected function recordEvent(DomainEventInterface $event): void
    {
        $this->domainEvents[] = $event;
    }

    /**
     * @return array<DomainEventInterface>
     */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }
}
