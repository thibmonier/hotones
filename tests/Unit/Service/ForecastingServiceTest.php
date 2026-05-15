<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Project;
use App\Repository\FactForecastRepository;
use App\Repository\ProjectRepository;
use App\Security\CompanyContext;
use App\Service\Analytics\DashboardReadService;
use App\Service\ForecastingService;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

/**
 * Comprehensive unit tests for ForecastingService.
 */
#[AllowMockObjectsWithoutExpectations]
class ForecastingServiceTest extends TestCase
{
    private function createService(?ProjectRepository $projectRepository = null): ForecastingService
    {
        $em = $this->createStub(EntityManagerInterface::class);
        $companyContext = $this->createStub(CompanyContext::class);
        $forecastRepository = $this->createStub(FactForecastRepository::class);
        $projectRepository ??= $this->createMock(ProjectRepository::class);
        $dashboardService = $this->createStub(DashboardReadService::class);

        return new ForecastingService($em, $companyContext, $forecastRepository, $projectRepository, $dashboardService);
    }

    public function testForecastRevenueThrowsExceptionForInvalidHorizon(): void
    {
        $service = $this->createService();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Horizon must be 3, 6, or 12 months');

        $service->forecastRevenue(15);
    }

    public function testForecastRevenueAcceptsValidHorizons(): void
    {
        $service = $this->createService();

        // Should not throw exception for valid horizons
        $validHorizons = [3, 6, 12];

        foreach ($validHorizons as $horizon) {
            try {
                // This will fail on insufficient data, but that's expected
                // We just want to verify the horizon validation passes
                $service->forecastRevenue($horizon);
            } catch (RuntimeException $e) {
                // Expected when no data - horizon validation passed
                static::assertStringContainsString('Insufficient historical data', $e->getMessage());
            }
        }

        static::assertTrue(true); // If we get here, validation works
    }

    public function testForecastRevenueThrowsExceptionForInsufficientData(): void
    {
        $projectRepository = $this->createMock(ProjectRepository::class);

        // Mock empty historical data
        $queryBuilder = $this->createStub(QueryBuilder::class);
        $query = $this->createStub(Query::class);

        $projectRepository->expects($this->once())->method('createQueryBuilder')->willReturn($queryBuilder);

        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);
        $query->method('getResult')->willReturn([]);

        $service = $this->createService($projectRepository);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Insufficient historical data for forecasting (minimum 6 months required)');

        $service->forecastRevenue(6);
    }

    public function testForecastRevenueWithSufficientData(): void
    {
        $projectRepository = $this->createMock(ProjectRepository::class);

        // Create 12 months of mock projects
        $projects = [];
        for ($i = 0; $i < 12; ++$i) {
            $project = new Project();
            $project->setName("Project {$i}");
            $project->setStatus('completed');
            $date = new DateTime()->modify("-{$i} months");
            $project->setStartDate($date);

            // Use reflection to set totalSoldAmount (it's calculated from tasks/orders)
            $reflection = new ReflectionClass($project);
            if ($reflection->hasMethod('setTotalSoldAmount')) {
                $project->setTotalSoldAmount((string) (10_000 + ($i * 1000)));
            }

            $projects[] = $project;
        }

        $queryBuilder = $this->createStub(QueryBuilder::class);
        $query = $this->createStub(Query::class);

        $projectRepository->expects($this->once())->method('createQueryBuilder')->willReturn($queryBuilder);

        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);
        $query->method('getResult')->willReturn($projects);

        $service = $this->createService($projectRepository);
        $result = $service->forecastRevenue(6);

        // Verify structure
        static::assertIsArray($result);
        static::assertArrayHasKey('predictions', $result);
        static::assertArrayHasKey('trend', $result);
        static::assertArrayHasKey('confidence', $result);
        static::assertArrayHasKey('historical', $result);

        // Verify predictions count
        static::assertCount(6, $result['predictions']);

        // Verify each prediction has required fields
        foreach ($result['predictions'] as $prediction) {
            static::assertArrayHasKey('month', $prediction);
            static::assertArrayHasKey('predicted', $prediction);
            static::assertArrayHasKey('min', $prediction);
            static::assertArrayHasKey('max', $prediction);
            static::assertGreaterThanOrEqual(0, $prediction['predicted']);
            static::assertLessThanOrEqual($prediction['predicted'], $prediction['min']);
            static::assertGreaterThanOrEqual($prediction['predicted'], $prediction['max']);
        }

        // Verify trend is one of expected values
        static::assertContains($result['trend'], ['growth', 'stable', 'decline', 'insufficient_data']);

        // Verify confidence is a percentage
        static::assertGreaterThanOrEqual(0, $result['confidence']);
        static::assertLessThanOrEqual(100, $result['confidence']);
    }

    public function testCalculateWeightedMovingAverage(): void
    {
        $service = $this->createService();

        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('calculateWeightedMovingAverage');

        // Test with simple data
        $historical = [
            ['month' => '2024-01', 'actual' => 10_000.0],
            ['month' => '2024-02', 'actual' => 15_000.0],
            ['month' => '2024-03', 'actual' => 20_000.0],
        ];

        // Weighted average: (10000*1 + 15000*2 + 20000*3) / (1+2+3) = 100000/6 = 16666.67
        $result = $method->invoke($service, $historical, 3);
        static::assertSame(16_666.67, round($result, 2));
    }

    public function testCalculateSeasonalityFactors(): void
    {
        $service = $this->createService();

        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('calculateSeasonalityFactors');

        // Create historical data spanning multiple years with clear seasonality
        $historical = [
            ['month' => '2023-01', 'actual' => 10_000.0],
            ['month' => '2023-08', 'actual' => 5000.0], // August low
            ['month' => '2023-12', 'actual' => 6000.0], // December low
            ['month' => '2024-01', 'actual' => 12_000.0],
            ['month' => '2024-08', 'actual' => 4000.0], // August low
            ['month' => '2024-12', 'actual' => 5000.0], // December low
        ];

        $result = $method->invoke($service, $historical);

        // Should return array with 12 months
        static::assertCount(12, $result);

        // January should be above 1.0 (above average)
        static::assertGreaterThan(1.0, $result[1]);

        // August and December should be below 1.0 (below average)
        static::assertLessThan(1.0, $result[8]);
        static::assertLessThan(1.0, $result[12]);

        // All months should have a factor
        for ($month = 1; $month <= 12; ++$month) {
            static::assertArrayHasKey($month, $result);
            static::assertGreaterThan(0, $result[$month]);
        }
    }

    public function testCalculateConfidenceWithMinimalData(): void
    {
        $service = $this->createService();

        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('calculateConfidence');

        // Less than 6 months = 40% confidence
        $historical = [
            ['month' => '2024-01', 'actual' => 10_000.0],
            ['month' => '2024-02', 'actual' => 11_000.0],
            ['month' => '2024-03', 'actual' => 12_000.0],
        ];

        $result = $method->invoke($service, $historical);
        static::assertSame(40.0, $result);
    }

    public function testCalculateConfidenceWithModerateData(): void
    {
        $service = $this->createService();

        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('calculateConfidence');

        // 6-11 months = 60% confidence
        $historical = [];
        for ($i = 0; $i < 10; ++$i) {
            $historical[] = ['month' => "2024-0{$i}", 'actual' => 10_000.0 + ($i * 1000)];
        }

        $result = $method->invoke($service, $historical);
        static::assertSame(60.0, $result);
    }

    public function testCalculateConfidenceWithExtensiveData(): void
    {
        $service = $this->createService();

        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('calculateConfidence');

        // 24+ months with low volatility = high confidence
        $historical = [];
        for ($i = 1; $i <= 24; ++$i) {
            $historical[] = ['month' => sprintf('2024-%02d', $i % 12 ?: 12), 'actual' => 10_000.0];
        }

        $result = $method->invoke($service, $historical);
        static::assertGreaterThanOrEqual(85, $result);
        static::assertLessThanOrEqual(95, $result);
    }

    public function testDetermineTrendDirectionGrowth(): void
    {
        $service = $this->createService();

        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('determineTrendDirection');

        // Clear growth: recent months 20% higher
        $historical = [
            ['month' => '2024-01', 'actual' => 10_000.0],
            ['month' => '2024-02', 'actual' => 11_000.0],
            ['month' => '2024-03', 'actual' => 12_000.0],
            ['month' => '2024-04', 'actual' => 14_000.0],
            ['month' => '2024-05', 'actual' => 15_000.0],
            ['month' => '2024-06', 'actual' => 16_000.0],
        ];

        $result = $method->invoke($service, $historical);
        static::assertSame('growth', $result);
    }

    public function testDetermineTrendDirectionStable(): void
    {
        $service = $this->createService();

        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('determineTrendDirection');

        // Stable: recent months within 10% of previous
        $historical = [
            ['month' => '2024-01', 'actual' => 10_000.0],
            ['month' => '2024-02', 'actual' => 10_500.0],
            ['month' => '2024-03', 'actual' => 10_200.0],
            ['month' => '2024-04', 'actual' => 10_300.0],
            ['month' => '2024-05', 'actual' => 10_400.0],
            ['month' => '2024-06', 'actual' => 10_100.0],
        ];

        $result = $method->invoke($service, $historical);
        static::assertSame('stable', $result);
    }

    public function testDetermineTrendDirectionDecline(): void
    {
        $service = $this->createService();

        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('determineTrendDirection');

        // Decline: recent months 20% lower
        $historical = [
            ['month' => '2024-01', 'actual' => 16_000.0],
            ['month' => '2024-02', 'actual' => 15_000.0],
            ['month' => '2024-03', 'actual' => 14_000.0],
            ['month' => '2024-04', 'actual' => 12_000.0],
            ['month' => '2024-05', 'actual' => 11_000.0],
            ['month' => '2024-06', 'actual' => 10_000.0],
        ];

        $result = $method->invoke($service, $historical);
        static::assertSame('decline', $result);
    }

    public function testDetermineTrendDirectionInsufficientData(): void
    {
        $service = $this->createService();

        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('determineTrendDirection');

        // Less than 3 months
        $historical = [
            ['month' => '2024-01', 'actual' => 10_000.0],
            ['month' => '2024-02', 'actual' => 11_000.0],
        ];

        $result = $method->invoke($service, $historical);
        static::assertSame('insufficient_data', $result);
    }

    // -----------------------------------------------------------------
    // T-TC1-02c — pure math helpers (no entity graph)
    // -----------------------------------------------------------------

    public function testLinearRegressionSimpleReturnsZeroSlopeForSinglePoint(): void
    {
        $service = $this->createService();
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('linearRegressionSimple');

        $result = $method->invoke($service, [['x' => 1.0, 'y' => 100.0]]);

        static::assertSame(0.0, $result['slope']);
        static::assertSame(0.0, $result['intercept']);
    }

    public function testLinearRegressionSimpleHandlesEmptyArray(): void
    {
        $service = $this->createService();
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('linearRegressionSimple');

        $result = $method->invoke($service, []);

        static::assertSame(0.0, $result['slope']);
        static::assertSame(0.0, $result['intercept']);
    }

    public function testLinearRegressionSimplePerfectLinearFit(): void
    {
        // y = 2x + 1 → slope=2, intercept=1.
        $service = $this->createService();
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('linearRegressionSimple');

        $points = [
            ['x' => 0.0, 'y' => 1.0],
            ['x' => 1.0, 'y' => 3.0],
            ['x' => 2.0, 'y' => 5.0],
            ['x' => 3.0, 'y' => 7.0],
            ['x' => 4.0, 'y' => 9.0],
        ];

        $result = $method->invoke($service, $points);

        static::assertEqualsWithDelta(2.0, $result['slope'], 0.001);
        static::assertEqualsWithDelta(1.0, $result['intercept'], 0.001);
    }

    public function testGetMonthsDifferenceReturnsZeroForSameMonth(): void
    {
        $service = $this->createService();
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('getMonthsDifference');

        $from = new DateTimeImmutable('2026-05-01');
        $to = new DateTimeImmutable('2026-05-31');

        static::assertSame(0, $method->invoke($service, $from, $to));
    }

    public function testGetMonthsDifferenceReturnsExpectedMonthsAcrossYears(): void
    {
        $service = $this->createService();
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('getMonthsDifference');

        $from = new DateTimeImmutable('2025-01-15');
        $to = new DateTimeImmutable('2026-04-15');

        // 1 year × 12 + 3 months = 15.
        static::assertSame(15, $method->invoke($service, $from, $to));
    }
}
