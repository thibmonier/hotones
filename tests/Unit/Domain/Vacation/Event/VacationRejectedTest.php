<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Vacation\Event;

use App\Domain\Vacation\Event\VacationRejected;
use App\Domain\Vacation\ValueObject\VacationId;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class VacationRejectedTest extends TestCase
{
    public function testConstructWithDefaultOccurredOn(): void
    {
        $vacationId = VacationId::generate();
        $before = new DateTimeImmutable();

        $event = new VacationRejected($vacationId);

        $after = new DateTimeImmutable();

        static::assertSame($vacationId, $event->vacationId);
        static::assertGreaterThanOrEqual($before, $event->occurredOn);
        static::assertLessThanOrEqual($after, $event->occurredOn);
    }

    public function testConstructWithExplicitOccurredOn(): void
    {
        $occurredOn = new DateTimeImmutable('2026-05-12 09:15:00');

        $event = new VacationRejected(VacationId::generate(), $occurredOn);

        static::assertSame($occurredOn, $event->occurredOn);
    }
}
