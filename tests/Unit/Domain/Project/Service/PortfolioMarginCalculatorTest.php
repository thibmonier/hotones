<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Project\Service;

use App\Domain\Project\Service\PortfolioMarginCalculator;
use App\Domain\Project\Service\PortfolioMarginRecord;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PortfolioMarginCalculatorTest extends TestCase
{
    private PortfolioMarginCalculator $calculator;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->calculator = new PortfolioMarginCalculator();
        $this->now = new DateTimeImmutable('2026-06-24T00:00:00+00:00');
    }

    public function testEmptyPortfolioReturnsZero(): void
    {
        $result = $this->calculator->calculate([], $this->now);

        self::assertSame(0.0, $result->getAveragePercent());
        self::assertSame(0, $result->getProjectsWithSnapshot());
        self::assertSame(0, $result->totalActiveProjects());
    }

    public function testSingleProjectWithSnapshot(): void
    {
        $records = [
            // marge = (10000 - 8000) / 10000 = 20 %
            $this->record(1, 'Alpha', cout: 8_000_00, facture: 10_000_00),
        ];

        $result = $this->calculator->calculate($records, $this->now);

        self::assertEqualsWithDelta(20.0, $result->getAveragePercent(), 0.1);
        self::assertSame(1, $result->getProjectsWithSnapshot());
        self::assertSame(1, $result->getProjectsAboveTarget()); // ≥ 20% défaut
        self::assertSame(0, $result->getProjectsBelowTarget());
    }

    public function testWeightedAverageByFactureTotal(): void
    {
        $records = [
            // 30 % marge sur petit projet (1000 €)
            $this->record(1, 'Small', cout: 700_00, facture: 1_000_00),
            // 10 % marge sur grand projet (10 000 €)
            $this->record(2, 'Big', cout: 9_000_00, facture: 10_000_00),
        ];

        $result = $this->calculator->calculate($records, $this->now);

        // Pondéré : (30 × 1000 + 10 × 10000) / (1000 + 10000) = 130000 / 11000 ≈ 11.8 %
        self::assertEqualsWithDelta(11.8, $result->getAveragePercent(), 0.2);
        self::assertSame(2, $result->getProjectsWithSnapshot());
    }

    public function testProjectsWithoutSnapshotCountedSeparately(): void
    {
        $records = [
            $this->record(1, 'WithSnap', cout: 8_000_00, facture: 10_000_00),
            new PortfolioMarginRecord(2, 'NoCalc', 0, 5_000_00, null),
            new PortfolioMarginRecord(3, 'NoFacture', 0, null, $this->now),
        ];

        $result = $this->calculator->calculate($records, $this->now);

        self::assertSame(1, $result->getProjectsWithSnapshot());
        self::assertSame(2, $result->getProjectsWithoutSnapshot());
        self::assertSame(3, $result->totalActiveProjects());
        // Pondéré sur le seul projet avec snapshot
        self::assertEqualsWithDelta(20.0, $result->getAveragePercent(), 0.1);
    }

    public function testFactureZeroExcludedFromCalculation(): void
    {
        $records = [
            // factureTotalCents = 0 → exclus (division par zéro)
            new PortfolioMarginRecord(1, 'Zero', 100_00, 0, $this->now),
            $this->record(2, 'Valid', cout: 8_000_00, facture: 10_000_00),
        ];

        $result = $this->calculator->calculate($records, $this->now);

        self::assertSame(1, $result->getProjectsWithSnapshot());
        self::assertSame(1, $result->getProjectsWithoutSnapshot());
        self::assertEqualsWithDelta(20.0, $result->getAveragePercent(), 0.1);
    }

    public function testNegativeMarginCapsAtMinus100(): void
    {
        $records = [
            // marge = (1000 - 5000) / 1000 = -400 % → cappé à -100
            $this->record(1, 'Loss', cout: 5_000_00, facture: 1_000_00),
        ];

        $result = $this->calculator->calculate($records, $this->now);

        self::assertEqualsWithDelta(-100.0, $result->getAveragePercent(), 0.1);
        self::assertSame(0, $result->getProjectsAboveTarget());
        self::assertSame(1, $result->getProjectsBelowTarget());
    }

    public function testAboveAndBelowTargetBreakdown(): void
    {
        $records = [
            $this->record(1, 'Good1', cout: 7_000_00, facture: 10_000_00), // 30 %
            $this->record(2, 'Good2', cout: 8_000_00, facture: 10_000_00), // 20 % (= cible défaut)
            $this->record(3, 'Bad', cout: 9_500_00, facture: 10_000_00),   // 5 %
        ];

        $result = $this->calculator->calculate($records, $this->now);

        self::assertSame(2, $result->getProjectsAboveTarget());
        self::assertSame(1, $result->getProjectsBelowTarget());
    }

    public function testCustomTargetMarginPercent(): void
    {
        $records = [
            $this->record(1, 'A', cout: 7_500_00, facture: 10_000_00), // 25 %
            $this->record(2, 'B', cout: 8_500_00, facture: 10_000_00), // 15 %
        ];

        // Cible 20 % défaut : 1 above, 1 below
        $result = $this->calculator->calculate($records, $this->now, 20.0);
        self::assertSame(1, $result->getProjectsAboveTarget());

        // Cible 30 % : 0 above, 2 below
        $result = $this->calculator->calculate($records, $this->now, 30.0);
        self::assertSame(0, $result->getProjectsAboveTarget());
        self::assertSame(2, $result->getProjectsBelowTarget());
    }

    public function testInvalidTargetThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculator->calculate([], $this->now, 150.0);
    }

    public function testGeneratorInputIsConsumedOnce(): void
    {
        $now = $this->now;
        $generator = (function () use ($now) {
            yield new PortfolioMarginRecord(1, 'G1', 7_000_00, 10_000_00, $now);
        })();

        $result = $this->calculator->calculate($generator, $this->now);

        self::assertEqualsWithDelta(30.0, $result->getAveragePercent(), 0.1);
        self::assertSame(1, $result->getProjectsWithSnapshot());
    }

    private function record(int $id, string $name, int $cout, int $facture): PortfolioMarginRecord
    {
        return new PortfolioMarginRecord(
            projectId: $id,
            projectName: $name,
            coutTotalCents: $cout,
            factureTotalCents: $facture,
            margeCalculatedAt: $this->now->modify('-1 day'),
        );
    }
}
