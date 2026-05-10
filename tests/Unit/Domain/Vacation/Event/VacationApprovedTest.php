<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Vacation\Event;

use App\Domain\Vacation\Event\VacationApproved;
use App\Domain\Vacation\ValueObject\VacationId;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class VacationApprovedTest extends TestCase
{
    public function testConstructStoresApproverId(): void
    {
        $vacationId = VacationId::generate();
        $approverId = 42;

        $event = new VacationApproved($vacationId, $approverId);

        self::assertSame($vacationId, $event->vacationId);
        self::assertSame(42, $event->approvedByUserId);
    }

    public function testDefaultOccurredOnSetAtConstruction(): void
    {
        $before = new DateTimeImmutable();

        $event = new VacationApproved(VacationId::generate(), 1);

        $after = new DateTimeImmutable();

        self::assertGreaterThanOrEqual($before, $event->occurredOn);
        self::assertLessThanOrEqual($after, $event->occurredOn);
    }

    public function testExplicitOccurredOn(): void
    {
        $occurredOn = new DateTimeImmutable('2026-05-12 14:00:00');

        $event = new VacationApproved(VacationId::generate(), 5, $occurredOn);

        self::assertSame($occurredOn, $event->occurredOn);
    }
}
