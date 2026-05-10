<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Vacation\Event;

use App\Domain\Vacation\Event\VacationCancelled;
use App\Domain\Vacation\ValueObject\VacationId;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class VacationCancelledTest extends TestCase
{
    public function testConstructWithDefaultOccurredOn(): void
    {
        $vacationId = VacationId::generate();
        $before = new DateTimeImmutable();

        $event = new VacationCancelled($vacationId);

        $after = new DateTimeImmutable();

        self::assertSame($vacationId, $event->vacationId);
        self::assertGreaterThanOrEqual($before, $event->occurredOn);
        self::assertLessThanOrEqual($after, $event->occurredOn);
    }

    public function testConstructWithExplicitOccurredOn(): void
    {
        $occurredOn = new DateTimeImmutable('2026-05-12 16:30:00');

        $event = new VacationCancelled(VacationId::generate(), $occurredOn);

        self::assertSame($occurredOn, $event->occurredOn);
    }
}
