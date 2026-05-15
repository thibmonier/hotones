<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Project\Service;

use App\Domain\Project\Service\ConversionRateCalculator;
use App\Domain\Project\Service\OrderConversionRecord;
use App\Domain\Project\ValueObject\ConversionTrend;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ConversionRateCalculatorTest extends TestCase
{
    private ConversionRateCalculator $calculator;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->calculator = new ConversionRateCalculator();
        $this->now = new DateTimeImmutable('2026-06-10T00:00:00+00:00');
    }

    public function testEmptyPipelineReturnsZeroRate(): void
    {
        $result = $this->calculator->calculate([], $this->now);

        self::assertSame(0.0, $result->getRate30Percent());
        self::assertSame(0.0, $result->getRate90Percent());
        self::assertSame(0.0, $result->getRate365Percent());
        self::assertSame(0, $result->getEmitted30Count());
        self::assertSame(0, $result->getConverted30Count());
        self::assertSame(ConversionTrend::Stable, $result->getTrend30());
    }

    public function testFullConversionReturns100Percent(): void
    {
        $records = [
            new OrderConversionRecord('signe', $this->now->modify('-5 days')),
            new OrderConversionRecord('gagne', $this->now->modify('-10 days')),
        ];

        $result = $this->calculator->calculate($records, $this->now);

        self::assertSame(100.0, $result->getRate30Percent());
        self::assertSame(2, $result->getEmitted30Count());
        self::assertSame(2, $result->getConverted30Count());
    }

    public function testZeroConversionReturns0Percent(): void
    {
        $records = [
            new OrderConversionRecord('perdu', $this->now->modify('-5 days')),
            new OrderConversionRecord('abandonne', $this->now->modify('-10 days')),
        ];

        $result = $this->calculator->calculate($records, $this->now);

        self::assertSame(0.0, $result->getRate30Percent());
        self::assertSame(2, $result->getEmitted30Count());
        self::assertSame(0, $result->getConverted30Count());
    }

    public function testMixedConversion50Percent(): void
    {
        $records = [
            new OrderConversionRecord('signe', $this->now->modify('-5 days')),
            new OrderConversionRecord('perdu', $this->now->modify('-10 days')),
            new OrderConversionRecord('gagne', $this->now->modify('-15 days')),
            new OrderConversionRecord('abandonne', $this->now->modify('-20 days')),
        ];

        $result = $this->calculator->calculate($records, $this->now);

        self::assertSame(50.0, $result->getRate30Percent());
        self::assertSame(4, $result->getEmitted30Count());
        self::assertSame(2, $result->getConverted30Count());
    }

    public function testStandbyExcludedFromDenominator(): void
    {
        $records = [
            new OrderConversionRecord('signe', $this->now->modify('-5 days')),
            new OrderConversionRecord('standby', $this->now->modify('-10 days')),
            new OrderConversionRecord('standby', $this->now->modify('-15 days')),
        ];

        $result = $this->calculator->calculate($records, $this->now);

        // 1 converti / 1 émis hors standby = 100 %
        self::assertSame(100.0, $result->getRate30Percent());
        self::assertSame(1, $result->getEmitted30Count());
        self::assertSame(1, $result->getConverted30Count());
    }

    public function testATargetSignerAndTermineExcluded(): void
    {
        $records = [
            new OrderConversionRecord('a_signer', $this->now->modify('-5 days')),
            new OrderConversionRecord('termine', $this->now->modify('-10 days')),
            new OrderConversionRecord('signe', $this->now->modify('-15 days')),
        ];

        $result = $this->calculator->calculate($records, $this->now);

        // a_signer + termine exclus du dénominateur (hors décision)
        // Reste : 1 signé / 1 émis = 100 %
        self::assertSame(100.0, $result->getRate30Percent());
        self::assertSame(1, $result->getEmitted30Count());
    }

    public function testWindowSeparates30And90And365(): void
    {
        $records = [
            new OrderConversionRecord('signe', $this->now->modify('-5 days')),     // dans 30/90/365
            new OrderConversionRecord('perdu', $this->now->modify('-60 days')),    // dans 90/365 seulement
            new OrderConversionRecord('signe', $this->now->modify('-200 days')),   // dans 365 seulement
        ];

        $result = $this->calculator->calculate($records, $this->now);

        self::assertSame(100.0, $result->getRate30Percent());                       // 1/1
        self::assertEqualsWithDelta(50.0, $result->getRate90Percent(), 0.1);        // 1/2
        self::assertEqualsWithDelta(66.7, $result->getRate365Percent(), 0.1);       // 2/3
    }

    public function testTrendUpWhenCurrentHigherThanPrevious(): void
    {
        $records = [
            // Période courante 30j : 2/2 = 100 %
            new OrderConversionRecord('signe', $this->now->modify('-5 days')),
            new OrderConversionRecord('signe', $this->now->modify('-10 days')),
            // Période précédente [-60..-30] : 0/2 = 0 %
            new OrderConversionRecord('perdu', $this->now->modify('-40 days')),
            new OrderConversionRecord('perdu', $this->now->modify('-50 days')),
        ];

        $result = $this->calculator->calculate($records, $this->now);

        self::assertSame(ConversionTrend::Up, $result->getTrend30());
    }

    public function testTrendDownWhenCurrentLowerThanPrevious(): void
    {
        $records = [
            // Période courante 30j : 0/2 = 0 %
            new OrderConversionRecord('perdu', $this->now->modify('-5 days')),
            new OrderConversionRecord('abandonne', $this->now->modify('-10 days')),
            // Période précédente : 2/2 = 100 %
            new OrderConversionRecord('signe', $this->now->modify('-40 days')),
            new OrderConversionRecord('gagne', $this->now->modify('-50 days')),
        ];

        $result = $this->calculator->calculate($records, $this->now);

        self::assertSame(ConversionTrend::Down, $result->getTrend30());
    }

    public function testTrendStableWhenDeltaUnderThreshold(): void
    {
        $records = [
            // Même taux que période précédente
            new OrderConversionRecord('signe', $this->now->modify('-5 days')),
            new OrderConversionRecord('perdu', $this->now->modify('-10 days')),
            new OrderConversionRecord('signe', $this->now->modify('-40 days')),
            new OrderConversionRecord('perdu', $this->now->modify('-50 days')),
        ];

        $result = $this->calculator->calculate($records, $this->now);

        self::assertSame(ConversionTrend::Stable, $result->getTrend30());
    }

    public function testOldRecordsOutsideAllWindowsAreIgnored(): void
    {
        $records = [
            new OrderConversionRecord('signe', $this->now->modify('-500 days')),
        ];

        $result = $this->calculator->calculate($records, $this->now);

        self::assertSame(0.0, $result->getRate365Percent());
        self::assertSame(0, $result->getEmitted30Count());
    }

    public function testGeneratorInputIsConsumedOnceForMultipleWindows(): void
    {
        $now = $this->now;
        $generator = (function () use ($now) {
            yield new OrderConversionRecord('signe', $now->modify('-5 days'));
        })();

        $result = $this->calculator->calculate($generator, $this->now);

        // Generator matérialisé une fois — fenêtres 30 ET 365 calculées correctement
        self::assertSame(100.0, $result->getRate30Percent());
        self::assertSame(100.0, $result->getRate365Percent());
    }
}
