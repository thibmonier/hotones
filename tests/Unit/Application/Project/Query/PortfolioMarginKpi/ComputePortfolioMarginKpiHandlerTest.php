<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Project\Query\PortfolioMarginKpi;

use App\Application\Project\Query\PortfolioMarginKpi\ComputePortfolioMarginKpiHandler;
use App\Domain\Project\Repository\PortfolioMarginReadModelRepositoryInterface;
use App\Domain\Project\Service\PortfolioMarginCalculator;
use App\Domain\Project\Service\PortfolioMarginRecord;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ComputePortfolioMarginKpiHandlerTest extends TestCase
{
    public function testReturnsEmptyDtoWithoutWarningWhenNoProjects(): void
    {
        $handler = new ComputePortfolioMarginKpiHandler(
            repository: $this->stubRepository([]),
            calculator: new PortfolioMarginCalculator(),
        );

        $dto = $handler(new DateTimeImmutable('2026-05-15'));

        static::assertSame(0.0, $dto->averagePercent);
        static::assertSame(0, $dto->projectsWithSnapshot);
        static::assertSame(0, $dto->projectsWithoutSnapshot);
        static::assertFalse($dto->warningTriggered, 'no warning when 0 projets actifs');
        static::assertSame(20.0, $dto->targetMarginPercent);
        static::assertSame(15.0, $dto->warningThresholdPercent);
    }

    public function testFlagsWarningWhenAveragePercentBelowThreshold(): void
    {
        // marge moyenne pondérée 10 % < 15 % threshold → warning
        $records = [
            $this->record(1, 'A', cout: 90_000_00, facture: 100_000_00), // marge = 10 %
            $this->record(2, 'B', cout: 90_000_00, facture: 100_000_00), // marge = 10 %
        ];

        $handler = new ComputePortfolioMarginKpiHandler(
            repository: $this->stubRepository($records),
            calculator: new PortfolioMarginCalculator(),
        );

        $dto = $handler(new DateTimeImmutable('2026-05-15'));

        static::assertTrue($dto->warningTriggered);
        static::assertEqualsWithDelta(10.0, $dto->averagePercent, 0.1);
        static::assertSame(2, $dto->projectsBelowTarget);
        static::assertSame(0, $dto->projectsAboveTarget);
    }

    public function testNoWarningWhenAveragePercentAboveThreshold(): void
    {
        // marge moyenne pondérée 25 % > 15 % threshold + > 20 % target
        $records = [
            $this->record(1, 'A', cout: 75_000_00, facture: 100_000_00), // marge = 25 %
            $this->record(2, 'B', cout: 75_000_00, facture: 100_000_00), // marge = 25 %
        ];

        $handler = new ComputePortfolioMarginKpiHandler(
            repository: $this->stubRepository($records),
            calculator: new PortfolioMarginCalculator(),
        );

        $dto = $handler(new DateTimeImmutable('2026-05-15'));

        static::assertFalse($dto->warningTriggered);
        static::assertEqualsWithDelta(25.0, $dto->averagePercent, 0.1);
        static::assertSame(2, $dto->projectsAboveTarget);
        static::assertSame(0, $dto->projectsBelowTarget);
    }

    public function testCountsProjectsWithoutSnapshotSeparately(): void
    {
        $records = [
            $this->record(1, 'WithSnapshot', cout: 80_000_00, facture: 100_000_00),
            $this->recordWithoutSnapshot(2, 'NoSnapshot'),
        ];

        $handler = new ComputePortfolioMarginKpiHandler(
            repository: $this->stubRepository($records),
            calculator: new PortfolioMarginCalculator(),
        );

        $dto = $handler(new DateTimeImmutable('2026-05-15'));

        static::assertSame(1, $dto->projectsWithSnapshot);
        static::assertSame(1, $dto->projectsWithoutSnapshot);
        static::assertSame(2, $dto->totalActiveProjects());
    }

    /**
     * @param list<PortfolioMarginRecord> $records
     */
    private function stubRepository(array $records): PortfolioMarginReadModelRepositoryInterface
    {
        return new class($records) implements PortfolioMarginReadModelRepositoryInterface {
            /** @param list<PortfolioMarginRecord> $records */
            public function __construct(private readonly array $records)
            {
            }

            public function findActiveProjectsWithSnapshot(DateTimeImmutable $now): array
            {
                return $this->records;
            }
        };
    }

    private function record(int $id, string $name, int $cout, int $facture): PortfolioMarginRecord
    {
        return new PortfolioMarginRecord(
            projectId: $id,
            projectName: $name,
            coutTotalCents: $cout,
            factureTotalCents: $facture,
            margeCalculatedAt: new DateTimeImmutable('2026-05-15 10:00:00'),
        );
    }

    private function recordWithoutSnapshot(int $id, string $name): PortfolioMarginRecord
    {
        return new PortfolioMarginRecord(
            projectId: $id,
            projectName: $name,
            coutTotalCents: null,
            factureTotalCents: null,
            margeCalculatedAt: null,
        );
    }
}
