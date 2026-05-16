<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Project\Query\ConversionRateKpi;

use App\Application\Project\Query\ConversionRateKpi\ComputeConversionRateKpiHandler;
use App\Domain\Project\Repository\ConversionRateReadModelRepositoryInterface;
use App\Domain\Project\Service\ConversionRateCalculator;
use App\Domain\Project\Service\OrderConversionRecord;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ComputeConversionRateKpiHandlerTest extends TestCase
{
    public function testReturnsZeroRateWithoutWarningWhenPipelineEmpty(): void
    {
        $handler = new ComputeConversionRateKpiHandler(
            repository: $this->stubRepository([]),
            calculator: new ConversionRateCalculator(),
        );

        $dto = $handler(new DateTimeImmutable('2026-05-15'));

        static::assertSame(0.0, $dto->rate30Percent);
        static::assertSame(0, $dto->emitted30Count);
        static::assertSame(0, $dto->converted30Count);
        static::assertSame(40.0, $dto->warningThresholdPercent);
        static::assertFalse($dto->warningTriggered, 'pas d\'alerte sur pipeline vide (spam guard)');
    }

    public function testFlagsWarningWhenRate30BelowThreshold(): void
    {
        // 1 converti + 4 perdus = 20 % < 40 %
        $records = [
            $this->order(OrderConversionRecord::STATUS_CONVERTED_SIGNED, daysAgo: 5),
            $this->order(OrderConversionRecord::STATUS_FAILED_LOST, daysAgo: 6),
            $this->order(OrderConversionRecord::STATUS_FAILED_LOST, daysAgo: 7),
            $this->order(OrderConversionRecord::STATUS_FAILED_ABANDONED, daysAgo: 8),
            $this->order(OrderConversionRecord::STATUS_FAILED_ABANDONED, daysAgo: 9),
        ];

        $handler = new ComputeConversionRateKpiHandler(
            repository: $this->stubRepository($records),
            calculator: new ConversionRateCalculator(),
        );

        $dto = $handler(new DateTimeImmutable('2026-05-15'));

        static::assertTrue($dto->warningTriggered);
        static::assertEqualsWithDelta(20.0, $dto->rate30Percent, 0.1);
        static::assertSame(5, $dto->emitted30Count);
        static::assertSame(1, $dto->converted30Count);
    }

    public function testNoWarningWhenRate30AboveThreshold(): void
    {
        // 3 convertis + 1 perdu = 75 % > 40 %
        $records = [
            $this->order(OrderConversionRecord::STATUS_CONVERTED_SIGNED, daysAgo: 5),
            $this->order(OrderConversionRecord::STATUS_CONVERTED_WON, daysAgo: 6),
            $this->order(OrderConversionRecord::STATUS_CONVERTED_SIGNED, daysAgo: 7),
            $this->order(OrderConversionRecord::STATUS_FAILED_LOST, daysAgo: 8),
        ];

        $handler = new ComputeConversionRateKpiHandler(
            repository: $this->stubRepository($records),
            calculator: new ConversionRateCalculator(),
        );

        $dto = $handler(new DateTimeImmutable('2026-05-15'));

        static::assertFalse($dto->warningTriggered);
        static::assertEqualsWithDelta(75.0, $dto->rate30Percent, 0.1);
    }

    /**
     * @param list<OrderConversionRecord> $records
     */
    private function stubRepository(array $records): ConversionRateReadModelRepositoryInterface
    {
        return new class($records) implements ConversionRateReadModelRepositoryInterface {
            /** @param list<OrderConversionRecord> $records */
            public function __construct(private readonly array $records)
            {
            }

            public function findConversionRecords(DateTimeImmutable $now): array
            {
                return $this->records;
            }

            public function findAllClientsAggregated(int $windowDays, DateTimeImmutable $now): array
            {
                return [];
            }
        };
    }

    private function order(string $status, int $daysAgo): OrderConversionRecord
    {
        $now = new DateTimeImmutable('2026-05-15');

        return new OrderConversionRecord(
            status: $status,
            createdAt: $now->modify(sprintf('-%d days', $daysAgo)),
        );
    }
}
