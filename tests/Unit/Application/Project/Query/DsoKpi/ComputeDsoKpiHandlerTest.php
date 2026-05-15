<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Project\Query\DsoKpi;

use App\Application\Project\Query\DsoKpi\ComputeDsoKpiHandler;
use App\Application\Project\Query\DsoKpi\DsoTrend;
use App\Domain\Project\Repository\DsoReadModelRepositoryInterface;
use App\Domain\Project\Service\DsoCalculator;
use App\Domain\Project\Service\InvoicePaymentRecord;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ComputeDsoKpiHandlerTest extends TestCase
{
    public function testReturnsZeroDsoWhenNoInvoicesArePresent(): void
    {
        $handler = new ComputeDsoKpiHandler(
            repository: $this->stubRepository([]),
            calculator: new DsoCalculator(),
        );

        $dto = $handler(new DateTimeImmutable('2026-05-12'));

        static::assertSame(0.0, $dto->dso30Days);
        static::assertSame(0.0, $dto->dso90Days);
        static::assertSame(0.0, $dto->dso365Days);
        static::assertSame(DsoTrend::Stable, $dto->trend30);
        static::assertFalse($dto->warningTriggered);
        static::assertSame(45.0, $dto->warningThresholdDays);
    }

    public function testFlagsWarningWhen30DayDsoExceedsDefaultThreshold(): void
    {
        $records = [
            $this->record(delayDays: 50, amountCents: 10_000),
        ];

        $handler = new ComputeDsoKpiHandler(
            repository: $this->stubRepository($records),
            calculator: new DsoCalculator(),
        );

        $dto = $handler(new DateTimeImmutable('2026-05-12'));

        static::assertTrue($dto->warningTriggered);
        static::assertEqualsWithDelta(50.0, $dto->dso30Days, 0.1);
    }

    public function testTrendUpWhenCurrentDsoHigherThanPrevious(): void
    {
        $repo = new class implements DsoReadModelRepositoryInterface {
            public function findPaidInRollingWindow(int $windowDays, DateTimeImmutable $now): array
            {
                // Current = "high" delay 40 days for last period 2026-05-12,
                // Previous = "low" delay 10 days for period 2026-04-12.
                $isCurrentPeriod = $now->format('Y-m-d') === '2026-05-12';
                $delayDays = $isCurrentPeriod ? 40 : 10;

                return [
                    new InvoicePaymentRecord(
                        issuedAt: new DateTimeImmutable('2026-01-01'),
                        paidAt: $now->modify('-1 day'),
                        amountPaidCents: 10_000,
                    ),
                    new InvoicePaymentRecord(
                        issuedAt: new DateTimeImmutable('2026-01-01'),
                        paidAt: new DateTimeImmutable('2026-01-01')->modify('+'.$delayDays.' days'),
                        amountPaidCents: 10_000,
                    ),
                ];
            }

            public function findAllClientsAggregated(int $windowDays, DateTimeImmutable $now): array
            {
                return [];
            }
        };

        $handler = new ComputeDsoKpiHandler($repo, new DsoCalculator());

        $dto = $handler(new DateTimeImmutable('2026-05-12'));

        static::assertSame(DsoTrend::Up, $dto->trend30);
    }

    public function testTrendStableWhenDeltaWithinOneDay(): void
    {
        $records = [
            $this->record(delayDays: 20, amountCents: 10_000),
        ];

        $handler = new ComputeDsoKpiHandler(
            repository: $this->stubRepository($records),
            calculator: new DsoCalculator(),
        );

        $dto = $handler(new DateTimeImmutable('2026-05-12'));

        static::assertSame(DsoTrend::Stable, $dto->trend30);
    }

    /**
     * @param InvoicePaymentRecord[] $records
     */
    private function stubRepository(array $records): DsoReadModelRepositoryInterface
    {
        return new class($records) implements DsoReadModelRepositoryInterface {
            /** @param InvoicePaymentRecord[] $records */
            public function __construct(private readonly array $records)
            {
            }

            public function findPaidInRollingWindow(int $windowDays, DateTimeImmutable $now): array
            {
                return $this->records;
            }

            public function findAllClientsAggregated(int $windowDays, DateTimeImmutable $now): array
            {
                return [];
            }
        };
    }

    private function record(int $delayDays, int $amountCents): InvoicePaymentRecord
    {
        $issuedAt = new DateTimeImmutable('2026-05-01');

        return new InvoicePaymentRecord(
            issuedAt: $issuedAt,
            paidAt: $issuedAt->modify('+'.$delayDays.' days'),
            amountPaidCents: $amountCents,
        );
    }
}
