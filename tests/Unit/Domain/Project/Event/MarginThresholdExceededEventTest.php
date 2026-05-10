<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Project\Event;

use App\Domain\Project\Event\MarginThresholdExceededEvent;
use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\Shared\ValueObject\Money;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class MarginThresholdExceededEventTest extends TestCase
{
    public function testCreateBuildsEventWithFields(): void
    {
        $projectId = ProjectId::generate();

        $event = MarginThresholdExceededEvent::create(
            projectId: $projectId,
            projectName: 'Refonte Site E-Commerce',
            costTotal: Money::fromAmount(15000.00),
            invoicedPaidTotal: Money::fromAmount(16000.00),
            marginPercent: 6.25,
            thresholdPercent: 10.0,
        );

        self::assertSame($projectId, $event->projectId);
        self::assertSame('Refonte Site E-Commerce', $event->projectName);
        self::assertSame(6.25, $event->marginPercent);
        self::assertSame(10.0, $event->thresholdPercent);
        self::assertSame((string) $projectId, $event->getAggregateId());
    }

    public function testIsCriticalTrueWhenMarginBelowHalfThreshold(): void
    {
        // marge 4 % < threshold 10 / 2 = 5 % → CRITICAL
        $event = MarginThresholdExceededEvent::create(
            projectId: ProjectId::generate(),
            projectName: 'Project A',
            costTotal: Money::fromAmount(10000.00),
            invoicedPaidTotal: Money::fromAmount(10400.00),
            marginPercent: 4.0,
            thresholdPercent: 10.0,
        );

        self::assertTrue($event->isCritical());
    }

    public function testIsCriticalFalseWhenMarginAboveHalfThreshold(): void
    {
        // marge 7 % > threshold 10 / 2 = 5 % → WARN (pas critical)
        $event = MarginThresholdExceededEvent::create(
            projectId: ProjectId::generate(),
            projectName: 'Project B',
            costTotal: Money::fromAmount(10000.00),
            invoicedPaidTotal: Money::fromAmount(10750.00),
            marginPercent: 7.0,
            thresholdPercent: 10.0,
        );

        self::assertFalse($event->isCritical());
    }

    public function testOccurredOnSetAtConstruction(): void
    {
        $before = new DateTimeImmutable();

        $event = MarginThresholdExceededEvent::create(
            projectId: ProjectId::generate(),
            projectName: 'X',
            costTotal: Money::fromAmount(1000.00),
            invoicedPaidTotal: Money::fromAmount(1100.00),
            marginPercent: 9.0,
            thresholdPercent: 10.0,
        );

        $after = new DateTimeImmutable();

        self::assertGreaterThanOrEqual($before, $event->getOccurredOn());
        self::assertLessThanOrEqual($after, $event->getOccurredOn());
    }
}
