<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Vacation\Event;

use App\Domain\Vacation\Event\VacationRequested;
use App\Domain\Vacation\ValueObject\VacationId;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class VacationRequestedTest extends TestCase
{
    public function testConstructWithDefaultOccurredOn(): void
    {
        $vacationId = VacationId::generate();
        $before = new DateTimeImmutable();

        $event = new VacationRequested($vacationId);

        $after = new DateTimeImmutable();

        self::assertSame($vacationId, $event->vacationId);
        self::assertGreaterThanOrEqual($before, $event->occurredOn);
        self::assertLessThanOrEqual($after, $event->occurredOn);
    }

    public function testConstructWithExplicitOccurredOn(): void
    {
        $vacationId = VacationId::generate();
        $occurredOn = new DateTimeImmutable('2026-05-12 10:30:00');

        $event = new VacationRequested($vacationId, $occurredOn);

        self::assertSame($occurredOn, $event->occurredOn);
    }
}
