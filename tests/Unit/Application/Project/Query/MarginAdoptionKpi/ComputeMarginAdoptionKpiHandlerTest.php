<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Project\Query\MarginAdoptionKpi;

use App\Application\Project\Query\MarginAdoptionKpi\ComputeMarginAdoptionKpiHandler;
use App\Domain\Project\Repository\MarginAdoptionReadModelRepositoryInterface;
use App\Domain\Project\Service\MarginAdoptionCalculator;
use App\Domain\Project\Service\ProjectMarginSnapshotRecord;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ComputeMarginAdoptionKpiHandlerTest extends TestCase
{
    public function testReturnsEmptyStatsWithoutWarningWhenNoProjects(): void
    {
        $handler = new ComputeMarginAdoptionKpiHandler(
            repository: $this->stubRepository([]),
            calculator: new MarginAdoptionCalculator(),
        );

        $dto = $handler(new DateTimeImmutable('2026-05-12'));

        self::assertSame(0, $dto->stats->totalActive);
        self::assertSame(0.0, $dto->stats->freshPercent);
        self::assertFalse($dto->warningTriggered, 'no warning when 0 projets actifs');
        self::assertSame(60.0, $dto->warningThresholdPercent);
    }

    public function testFlagsWarningWhenFreshPercentBelowThreshold(): void
    {
        // 1 fresh + 1 stale → 50 % fresh (< 60 % threshold)
        $records = [
            $this->record(1, 'A', daysAgo: 2),
            $this->record(2, 'B', daysAgo: 50),
        ];

        $handler = new ComputeMarginAdoptionKpiHandler(
            repository: $this->stubRepository($records),
            calculator: new MarginAdoptionCalculator(),
        );

        $dto = $handler(new DateTimeImmutable('2026-05-12'));

        self::assertTrue($dto->warningTriggered);
        self::assertSame(50.0, $dto->stats->freshPercent);
    }

    public function testNoWarningWhenFreshPercentAtOrAboveThreshold(): void
    {
        // 7 fresh + 3 critical → 70 % fresh (≥ 60 %)
        $records = [];
        for ($i = 1; $i <= 7; ++$i) {
            $records[] = $this->record($i, "Fresh$i", daysAgo: 2);
        }
        for ($i = 8; $i <= 10; ++$i) {
            $records[] = $this->record($i, "Stale$i", daysAgo: 60);
        }

        $handler = new ComputeMarginAdoptionKpiHandler(
            repository: $this->stubRepository($records),
            calculator: new MarginAdoptionCalculator(),
        );

        $dto = $handler(new DateTimeImmutable('2026-05-12'));

        self::assertFalse($dto->warningTriggered);
        self::assertSame(70.0, $dto->stats->freshPercent);
    }

    /**
     * @param list<ProjectMarginSnapshotRecord> $records
     */
    private function stubRepository(array $records): MarginAdoptionReadModelRepositoryInterface
    {
        return new class($records) implements MarginAdoptionReadModelRepositoryInterface {
            /** @param list<ProjectMarginSnapshotRecord> $records */
            public function __construct(private readonly array $records)
            {
            }

            public function findActiveWithMarginSnapshot(): array
            {
                return $this->records;
            }
        };
    }

    private function record(int $id, string $name, int $daysAgo): ProjectMarginSnapshotRecord
    {
        $now = new DateTimeImmutable('2026-05-12');

        return new ProjectMarginSnapshotRecord(
            projectId: $id,
            projectName: $name,
            marginCalculatedAt: $now->modify(sprintf('-%d days', $daysAgo)),
        );
    }
}
