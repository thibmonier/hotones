<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Project\Service;

use App\Domain\Project\Service\PipelineOrderRecord;
use App\Domain\Project\Service\RevenueForecastCalculator;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class RevenueForecastCalculatorTest extends TestCase
{
    private RevenueForecastCalculator $calculator;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->calculator = new RevenueForecastCalculator();
        $this->now = new DateTimeImmutable('2026-06-10T00:00:00+00:00');
    }

    public function testEmptyPipelineReturnsZero(): void
    {
        $result = $this->calculator->calculate([], 0.3, $this->now);

        self::assertSame(0, $result->getForecast30Cents());
        self::assertSame(0, $result->getForecast90Cents());
        self::assertSame(0, $result->getConfirmedCents());
        self::assertSame(0, $result->getWeightedQuotesCents());
    }

    public function testConfirmedOrderCountsFullAmount(): void
    {
        $records = [
            new PipelineOrderRecord(
                status: PipelineOrderRecord::STATUS_SIGNED,
                amountCents: 100_000,
                validUntil: $this->now->modify('+15 days'),
            ),
        ];

        $result = $this->calculator->calculate($records, 0.3, $this->now);

        self::assertSame(100_000, $result->getForecast30Cents());
        self::assertSame(100_000, $result->getForecast90Cents());
        self::assertSame(100_000, $result->getConfirmedCents());
        self::assertSame(0, $result->getWeightedQuotesCents());
    }

    public function testQuoteIsWeightedByProbabilityCoefficient(): void
    {
        $records = [
            new PipelineOrderRecord(
                status: PipelineOrderRecord::STATUS_QUOTE,
                amountCents: 100_000,
                validUntil: $this->now->modify('+10 days'),
            ),
        ];

        $result = $this->calculator->calculate($records, 0.3, $this->now);

        // 100 000 × 0.3 = 30 000
        self::assertSame(30_000, $result->getForecast30Cents());
        self::assertSame(30_000, $result->getForecast90Cents());
        self::assertSame(0, $result->getConfirmedCents());
        self::assertSame(30_000, $result->getWeightedQuotesCents());
    }

    public function testMixedConfirmedAndQuotePipeline(): void
    {
        $records = [
            new PipelineOrderRecord(
                status: PipelineOrderRecord::STATUS_WON,
                amountCents: 200_000,
                validUntil: $this->now->modify('+20 days'),
            ),
            new PipelineOrderRecord(
                status: PipelineOrderRecord::STATUS_QUOTE,
                amountCents: 100_000,
                validUntil: $this->now->modify('+25 days'),
            ),
        ];

        $result = $this->calculator->calculate($records, 0.3, $this->now);

        // 200 000 confirmé + 100 000 × 0.3 pondéré = 230 000
        self::assertSame(230_000, $result->getForecast30Cents());
        self::assertSame(230_000, $result->getForecast90Cents());
        self::assertSame(200_000, $result->getConfirmedCents());
        self::assertSame(30_000, $result->getWeightedQuotesCents());
    }

    public function testExcludedStatusesAreIgnored(): void
    {
        $records = [
            new PipelineOrderRecord('perdu', 500_000, $this->now->modify('+10 days')),
            new PipelineOrderRecord('abandonne', 500_000, $this->now->modify('+10 days')),
            new PipelineOrderRecord('standby', 500_000, $this->now->modify('+10 days')),
            new PipelineOrderRecord('termine', 500_000, $this->now->modify('+10 days')),
        ];

        $result = $this->calculator->calculate($records, 0.3, $this->now);

        self::assertSame(0, $result->getForecast30Cents());
        self::assertSame(0, $result->getForecast90Cents());
    }

    public function testHorizonSeparates30And90Windows(): void
    {
        $records = [
            // Échéance dans 15 j → dans 30 j ET 90 j
            new PipelineOrderRecord(
                PipelineOrderRecord::STATUS_SIGNED,
                100_000,
                $this->now->modify('+15 days'),
            ),
            // Échéance dans 60 j → dans 90 j seulement
            new PipelineOrderRecord(
                PipelineOrderRecord::STATUS_SIGNED,
                300_000,
                $this->now->modify('+60 days'),
            ),
        ];

        $result = $this->calculator->calculate($records, 0.3, $this->now);

        self::assertSame(100_000, $result->getForecast30Cents());
        self::assertSame(400_000, $result->getForecast90Cents());
        self::assertSame(400_000, $result->getConfirmedCents());
    }

    public function testOrderWithoutValidUntilIsExcluded(): void
    {
        $records = [
            new PipelineOrderRecord(PipelineOrderRecord::STATUS_SIGNED, 100_000, null),
        ];

        $result = $this->calculator->calculate($records, 0.3, $this->now);

        self::assertSame(0, $result->getForecast90Cents());
    }

    public function testOverdueOrderIsExcluded(): void
    {
        $records = [
            // Échéance dépassée (hier) → hors horizon forecast
            new PipelineOrderRecord(
                PipelineOrderRecord::STATUS_SIGNED,
                100_000,
                $this->now->modify('-1 day'),
            ),
        ];

        $result = $this->calculator->calculate($records, 0.3, $this->now);

        self::assertSame(0, $result->getForecast90Cents());
    }

    public function testOrderBeyond90DaysIsExcluded(): void
    {
        $records = [
            new PipelineOrderRecord(
                PipelineOrderRecord::STATUS_SIGNED,
                100_000,
                $this->now->modify('+120 days'),
            ),
        ];

        $result = $this->calculator->calculate($records, 0.3, $this->now);

        self::assertSame(0, $result->getForecast90Cents());
    }

    public function testNegativeProbabilityCoefficientThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculator->calculate([], -0.1, $this->now);
    }

    public function testProbabilityCoefficientAboveOneThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculator->calculate([], 1.5, $this->now);
    }

    public function testNegativeAmountThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PipelineOrderRecord(PipelineOrderRecord::STATUS_SIGNED, -100, $this->now);
    }

    public function testGeneratorInputIsConsumedOnce(): void
    {
        $generator = (function () {
            yield new PipelineOrderRecord(
                PipelineOrderRecord::STATUS_SIGNED,
                100_000,
                $this->now->modify('+10 days'),
            );
        })();

        $result = $this->calculator->calculate($generator, 0.3, $this->now);

        // Generator matérialisé une fois → forecast 30 ET 90 corrects
        self::assertSame(100_000, $result->getForecast30Cents());
        self::assertSame(100_000, $result->getForecast90Cents());
    }
}
