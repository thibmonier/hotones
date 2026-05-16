<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Project\Query\RevenueForecastKpi;

use App\Application\Project\Query\RevenueForecastKpi\ComputeRevenueForecastKpiHandler;
use App\Domain\Project\Repository\RevenueForecastReadModelRepositoryInterface;
use App\Domain\Project\Service\PipelineOrderRecord;
use App\Domain\Project\Service\RevenueForecastCalculator;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ComputeRevenueForecastKpiHandlerTest extends TestCase
{
    public function testReturnsZeroForecastWithoutWarningWhenPipelineEmpty(): void
    {
        $handler = new ComputeRevenueForecastKpiHandler(
            repository: $this->stubRepository([]),
            calculator: new RevenueForecastCalculator(),
        );

        $dto = $handler(new DateTimeImmutable('2026-05-15'));

        static::assertSame(0.0, $dto->forecast30Euros);
        static::assertSame(0.0, $dto->forecast90Euros);
        static::assertSame(0.0, $dto->confirmedEuros);
        static::assertSame(0.0, $dto->weightedQuotesEuros);
        static::assertSame(0.3, $dto->probabilityCoefficient);
        static::assertSame(10_000.0, $dto->warningThresholdEuros);
        static::assertFalse($dto->warningTriggered, 'pas d\'alerte sur pipeline vide (spam guard)');
    }

    public function testFlagsWarningWhenForecast30BelowThreshold(): void
    {
        // Quote 5000 € × 0.3 = 1500 € à 15j → forecast30 = 1500 < 10000
        $records = [
            $this->quote(amountEuros: 5000, validUntilDaysFromNow: 15),
        ];

        $handler = new ComputeRevenueForecastKpiHandler(
            repository: $this->stubRepository($records),
            calculator: new RevenueForecastCalculator(),
        );

        $dto = $handler(new DateTimeImmutable('2026-05-15'));

        static::assertTrue($dto->warningTriggered);
        static::assertGreaterThan(0.0, $dto->forecast30Euros);
        static::assertLessThan(10_000.0, $dto->forecast30Euros);
    }

    public function testNoWarningWhenForecast30AboveThreshold(): void
    {
        // Signed 50_000 € à 15j → forecast30 = 50000 > 10000
        $records = [
            $this->signed(amountEuros: 50_000, validUntilDaysFromNow: 15),
        ];

        $handler = new ComputeRevenueForecastKpiHandler(
            repository: $this->stubRepository($records),
            calculator: new RevenueForecastCalculator(),
        );

        $dto = $handler(new DateTimeImmutable('2026-05-15'));

        static::assertFalse($dto->warningTriggered);
        static::assertGreaterThanOrEqual(10_000.0, $dto->forecast30Euros);
    }

    /**
     * @param list<PipelineOrderRecord> $records
     */
    private function stubRepository(array $records): RevenueForecastReadModelRepositoryInterface
    {
        return new class($records) implements RevenueForecastReadModelRepositoryInterface {
            /** @param list<PipelineOrderRecord> $records */
            public function __construct(private readonly array $records)
            {
            }

            public function findPipelineOrders(DateTimeImmutable $now): array
            {
                return $this->records;
            }
        };
    }

    private function quote(float $amountEuros, int $validUntilDaysFromNow): PipelineOrderRecord
    {
        $now = new DateTimeImmutable('2026-05-15');

        return new PipelineOrderRecord(
            status: PipelineOrderRecord::STATUS_QUOTE,
            amountCents: (int) round($amountEuros * 100),
            validUntil: $now->modify(sprintf('+%d days', $validUntilDaysFromNow)),
        );
    }

    private function signed(float $amountEuros, int $validUntilDaysFromNow): PipelineOrderRecord
    {
        $now = new DateTimeImmutable('2026-05-15');

        return new PipelineOrderRecord(
            status: PipelineOrderRecord::STATUS_SIGNED,
            amountCents: (int) round($amountEuros * 100),
            validUntil: $now->modify(sprintf('+%d days', $validUntilDaysFromNow)),
        );
    }
}
