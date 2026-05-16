<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Project\EventListener;

use App\Application\Project\EventListener\InvalidatePortfolioMarginCacheOnProjectMarginRecalculated;
use App\Domain\Project\Event\ProjectMarginRecalculatedEvent;
use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\Shared\ValueObject\Money;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;

final class InvalidatePortfolioMarginCacheOnProjectMarginRecalculatedTest extends TestCase
{
    public function testClearsKpiCachePoolOnEvent(): void
    {
        $pool = $this->createMock(CacheItemPoolInterface::class);
        $pool->expects(self::once())
            ->method('clear')
            ->willReturn(true);

        $handler = new InvalidatePortfolioMarginCacheOnProjectMarginRecalculated(kpiCache: $pool);

        $event = ProjectMarginRecalculatedEvent::create(
            projectId: ProjectId::fromLegacyInt(42),
            projectName: 'Test Project',
            costTotal: Money::fromCents(80_000_00),
            invoicedPaidTotal: Money::fromCents(100_000_00),
            marginPercent: 20.0,
        );

        $handler($event);
    }
}
