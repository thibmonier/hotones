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
        static::assertCount(2, $pulled);
        static::assertSame($event1, $pulled[0]);
        static::assertSame($event2, $pulled[1]);

        // Second pull is empty (buffer cleared).
        static::assertSame([], $aggregate->pullDomainEvents());
    }

    public function testEmptyByDefault(): void
    {
        $aggregate = new class implements AggregateRootInterface {
            use RecordsDomainEvents;
        };

        static::assertSame([], $aggregate->pullDomainEvents());
    }
}
