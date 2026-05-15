<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Project\Alerting;

use App\Application\Project\Alerting\MarginAdoptionAlertState;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class MarginAdoptionAlertStateTest extends TestCase
{
    public function testInitialStateHasZeroDays(): void
    {
        $state = MarginAdoptionAlertState::initial();

        static::assertSame(0, $state->consecutiveRedDays);
        static::assertNull($state->lastRedDate);
        static::assertNull($state->lastAlertSentAt);
    }

    public function testWithRedTodayStartsStreakFromInitial(): void
    {
        $state = MarginAdoptionAlertState::initial();
        $today = new DateTimeImmutable('2026-05-12');

        $next = $state->withRedToday($today);

        static::assertSame(1, $next->consecutiveRedDays);
        static::assertEquals($today, $next->lastRedDate);
    }

    public function testWithRedTodayIncrementsConsecutiveStreak(): void
    {
        $day1 = new DateTimeImmutable('2026-05-10');
        $day2 = new DateTimeImmutable('2026-05-11');
        $day3 = new DateTimeImmutable('2026-05-12');

        $state = MarginAdoptionAlertState::initial()
            ->withRedToday($day1)
            ->withRedToday($day2)
            ->withRedToday($day3);

        static::assertSame(3, $state->consecutiveRedDays);
    }

    public function testWithRedTodaySameDayDoesNotIncrement(): void
    {
        $today = new DateTimeImmutable('2026-05-12 10:00');
        $sameDayLater = new DateTimeImmutable('2026-05-12 15:00');

        $state = MarginAdoptionAlertState::initial()
            ->withRedToday($today)
            ->withRedToday($sameDayLater);

        static::assertSame(1, $state->consecutiveRedDays);
    }

    public function testGapBreaksStreakAndRestartsAt1(): void
    {
        $day1 = new DateTimeImmutable('2026-05-05');
        $day3 = new DateTimeImmutable('2026-05-12'); // 7 day gap

        $state = MarginAdoptionAlertState::initial()
            ->withRedToday($day1)
            ->withRedToday($day3);

        static::assertSame(1, $state->consecutiveRedDays);
    }

    public function testWithGreenTodayResetsStreak(): void
    {
        $state = MarginAdoptionAlertState::initial()
            ->withRedToday(new DateTimeImmutable('2026-05-10'))
            ->withRedToday(new DateTimeImmutable('2026-05-11'));

        static::assertSame(2, $state->consecutiveRedDays);

        $reset = $state->withGreenToday();

        static::assertSame(0, $reset->consecutiveRedDays);
        static::assertNull($reset->lastRedDate);
    }

    public function testShouldFireAlertWhenStreakReachesThreshold(): void
    {
        $state = MarginAdoptionAlertState::initial();
        $now = new DateTimeImmutable('2026-05-12');

        for ($i = 1; $i <= 7; ++$i) {
            $state = $state->withRedToday($now->modify(sprintf('-%d days', 7 - $i)));
        }

        static::assertSame(7, $state->consecutiveRedDays);
        static::assertTrue($state->shouldFireAlert(threshold: 7, now: $now));
    }

    public function testShouldNotFireAlertBelowThreshold(): void
    {
        $state = MarginAdoptionAlertState::initial()
            ->withRedToday(new DateTimeImmutable('2026-05-10'))
            ->withRedToday(new DateTimeImmutable('2026-05-11'))
            ->withRedToday(new DateTimeImmutable('2026-05-12'));

        static::assertFalse($state->shouldFireAlert(threshold: 7, now: new DateTimeImmutable('2026-05-12')));
    }

    public function testShouldNotFireDuplicateAlertWithin24Hours(): void
    {
        $base = MarginAdoptionAlertState::initial();
        $now = new DateTimeImmutable('2026-05-12 10:00');

        for ($i = 1; $i <= 7; ++$i) {
            $base = $base->withRedToday($now->modify(sprintf('-%d days', 7 - $i)));
        }

        $afterAlert = $base->withAlertSentAt($now);
        $oneHourLater = $now->modify('+1 hour');

        static::assertFalse($afterAlert->shouldFireAlert(threshold: 7, now: $oneHourLater));
    }

    public function testShouldFireAlertAfter24HoursElapsed(): void
    {
        $base = MarginAdoptionAlertState::initial();
        $now = new DateTimeImmutable('2026-05-12 10:00');

        for ($i = 1; $i <= 7; ++$i) {
            $base = $base->withRedToday($now->modify(sprintf('-%d days', 7 - $i)));
        }

        $afterAlert = $base->withAlertSentAt($now);
        $nextDay = $now->modify('+25 hours');

        static::assertTrue($afterAlert->shouldFireAlert(threshold: 7, now: $nextDay));
    }
}
