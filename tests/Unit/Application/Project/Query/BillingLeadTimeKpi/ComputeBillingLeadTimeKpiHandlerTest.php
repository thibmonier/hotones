<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Project\Query\BillingLeadTimeKpi;

use App\Application\Project\Query\BillingLeadTimeKpi\ComputeBillingLeadTimeKpiHandler;
use App\Domain\Project\Repository\BillingLeadTimeReadModelRepositoryInterface;
use App\Domain\Project\Service\BillingLeadTimeCalculator;
use App\Domain\Project\Service\QuoteInvoiceRecord;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ComputeBillingLeadTimeKpiHandlerTest extends TestCase
{
    public function testReturnsZeroStatsWhenNoRecords(): void
    {
        $handler = new ComputeBillingLeadTimeKpiHandler(
            repository: $this->stubRepository([]),
            calculator: new BillingLeadTimeCalculator(),
        );

        $dto = $handler(new DateTimeImmutable('2026-05-12'));

        self::assertSame(0, $dto->stats30->count);
        self::assertSame(0, $dto->stats90->count);
        self::assertSame(0, $dto->stats365->count);
        self::assertSame([], $dto->topSlowClients);
        self::assertFalse($dto->warningTriggered);
        self::assertSame(14.0, $dto->warningThresholdDays);
    }

    public function testFlagsWarningWhenMedian30JAboveThreshold(): void
    {
        $records = [
            $this->record(daysAgoEmitted: 5, leadTimeDays: 20, clientId: 1, clientName: 'Acme'),
        ];

        $handler = new ComputeBillingLeadTimeKpiHandler(
            repository: $this->stubRepository($records),
            calculator: new BillingLeadTimeCalculator(),
        );

        $dto = $handler(new DateTimeImmutable('2026-05-12'));

        self::assertTrue($dto->warningTriggered);
        self::assertEqualsWithDelta(20.0, $dto->stats30->p50->getDays(), 0.5);
    }

    public function testRanksTopSlowClientsByAverageLeadTimeDesc(): void
    {
        $records = [
            // Acme : avg 30
            $this->record(daysAgoEmitted: 5, leadTimeDays: 25, clientId: 1, clientName: 'Acme'),
            $this->record(daysAgoEmitted: 5, leadTimeDays: 35, clientId: 1, clientName: 'Acme'),
            // Beta : avg 10
            $this->record(daysAgoEmitted: 5, leadTimeDays: 10, clientId: 2, clientName: 'Beta'),
            // Gamma : avg 50
            $this->record(daysAgoEmitted: 5, leadTimeDays: 50, clientId: 3, clientName: 'Gamma'),
            // Delta : avg 5 (will not be top 3 if 4 distinct clients)
            $this->record(daysAgoEmitted: 5, leadTimeDays: 5, clientId: 4, clientName: 'Delta'),
        ];

        $handler = new ComputeBillingLeadTimeKpiHandler(
            repository: $this->stubRepository($records),
            calculator: new BillingLeadTimeCalculator(),
        );

        $dto = $handler(new DateTimeImmutable('2026-05-12'));

        self::assertCount(3, $dto->topSlowClients);
        self::assertSame('Gamma', $dto->topSlowClients[0]->clientName);
        self::assertSame(50.0, $dto->topSlowClients[0]->averageLeadTimeDays);
        self::assertSame('Acme', $dto->topSlowClients[1]->clientName);
        self::assertSame(30.0, $dto->topSlowClients[1]->averageLeadTimeDays);
        self::assertSame(2, $dto->topSlowClients[1]->sampleCount);
        self::assertSame('Beta', $dto->topSlowClients[2]->clientName);
    }

    public function testSkipsRecordsWithoutClientIdInTop3(): void
    {
        $records = [
            $this->record(daysAgoEmitted: 5, leadTimeDays: 100, clientId: null, clientName: null),
            $this->record(daysAgoEmitted: 5, leadTimeDays: 10, clientId: 1, clientName: 'Acme'),
        ];

        $handler = new ComputeBillingLeadTimeKpiHandler(
            repository: $this->stubRepository($records),
            calculator: new BillingLeadTimeCalculator(),
        );

        $dto = $handler(new DateTimeImmutable('2026-05-12'));

        self::assertCount(1, $dto->topSlowClients);
        self::assertSame('Acme', $dto->topSlowClients[0]->clientName);
    }

    /**
     * @param list<QuoteInvoiceRecord> $records
     */
    private function stubRepository(array $records): BillingLeadTimeReadModelRepositoryInterface
    {
        return new class($records) implements BillingLeadTimeReadModelRepositoryInterface {
            /** @param list<QuoteInvoiceRecord> $records */
            public function __construct(private readonly array $records)
            {
            }

            public function findEmittedInRollingWindow(int $windowDays, DateTimeImmutable $now): array
            {
                return $this->records;
            }

            public function findAllClientsAggregated(int $windowDays, DateTimeImmutable $now): array
            {
                return [];
            }
        };
    }

    private function record(int $daysAgoEmitted, int $leadTimeDays, ?int $clientId, ?string $clientName): QuoteInvoiceRecord
    {
        $now = new DateTimeImmutable('2026-05-12');
        $emittedAt = $now->modify('-'.$daysAgoEmitted.' days');
        $signedAt = $emittedAt->modify('-'.$leadTimeDays.' days');

        return new QuoteInvoiceRecord(
            signedAt: $signedAt,
            emittedAt: $emittedAt,
            clientId: $clientId,
            clientName: $clientName,
        );
    }
}
