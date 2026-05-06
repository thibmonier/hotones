<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Shared\Trait;

use App\Domain\Shared\Interface\AggregateRootInterface;
use App\Domain\Shared\Interface\DomainEventInterface;
use App\Domain\Shared\Trait\RecordsDomainEvents;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class RecordsDomainEventsTest extends TestCase
{
    public function testPullsRecordedEventsAndEmptiesBuffer(): void
    {
        $aggregate = new class implements AggregateRootInterface {
            use RecordsDomainEvents;

            public function emit(DomainEventInterface $event): void
            {
                $this->recordEvent($event);
            }
        };

        $event1 = new class implements DomainEventInterface {
            public function getOccurredOn(): DateTimeImmutable
            {
                return new DateTimeImmutable();
            }
        };
        $event2 = new class implements DomainEventInterface {
            public function getOccurredOn(): DateTimeImmutable
            {
                return new DateTimeImmutable();
            }
        };

        $aggregate->emit($event1);
        $aggregate->emit($event2);

        $pulled = $aggregate->pullDomainEvents();
        $this->assertCount(2, $pulled);
        $this->assertSame($event1, $pulled[0]);
        $this->assertSame($event2, $pulled[1]);

        // Second pull is empty (buffer cleared).
        $this->assertSame([], $aggregate->pullDomainEvents());
    }

    public function testEmptyByDefault(): void
    {
        $aggregate = new class implements AggregateRootInterface {
            use RecordsDomainEvents;
        };

        $this->assertSame([], $aggregate->pullDomainEvents());
    }
}
