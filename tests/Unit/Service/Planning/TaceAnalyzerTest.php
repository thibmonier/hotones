<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Planning;

use App\Entity\Analytics\FactStaffingMetrics;
use App\Entity\Contributor;
use App\Repository\ContributorRepository;
use App\Repository\StaffingMetricsRepository;
use App\Service\Planning\TaceAnalyzer;
use DateTime;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Comprehensive unit tests for TaceAnalyzer.
 */
#[AllowMockObjectsWithoutExpectations]
class TaceAnalyzerTest extends TestCase
{
    private function createService(
        ?ContributorRepository $contributorRepository = null,
        ?StaffingMetricsRepository $staffingMetricsRepository = null,
    ): TaceAnalyzer {
        $contributorRepository ??= $this->createMock(ContributorRepository::class);
        $staffingMetricsRepository ??= $this->createMock(StaffingMetricsRepository::class);

        return new TaceAnalyzer($contributorRepository, $staffingMetricsRepository);
    }

    private function createMockMetric(float $tace, float $availableDays, float $workedDays): FactStaffingMetrics
    {
        $metric = $this->createStub(FactStaffingMetrics::class);
        $metric->method('getTace')->willReturn((string) $tace);
        $metric->method('getAvailableDays')->willReturn((string) $availableDays);
        $metric->method('getWorkedDays')->willReturn((string) $workedDays);

        return $metric;
    }

    public function testGetThresholds(): void
    {
        $service = $this->createService();
        $result = $service->getThresholds();

        static::assertIsArray($result);
        static::assertArrayHasKey('ideal_min', $result);
        static::assertArrayHasKey('ideal_max', $result);
        static::assertArrayHasKey('critical_low', $result);
        static::assertArrayHasKey('critical_high', $result);

        static::assertSame(70, $result['ideal_min']);
        static::assertSame(90, $result['ideal_max']);
        static::assertSame(50, $result['critical_low']);
        static::assertSame(110, $result['critical_high']);
    }

    public function testAnalyzeContributorWithNoMetrics(): void
    {
        $contributor = $this->createStub(Contributor::class);

        $staffingMetricsRepository = $this->createMock(StaffingMetricsRepository::class);
        $staffingMetricsRepository->method('findByPeriod')->willReturn([]);

        $service = $this->createService(null, $staffingMetricsRepository);
        $startDate = new DateTime('2024-01-01');
        $endDate = new DateTime('2024-01-31');

        $result = $service->analyzeContributor($contributor, $startDate, $endDate);

        static::assertNull($result['tace']);
        static::assertSame(0, $result['availability']);
        static::assertSame(0, $result['workload']);
        static::assertSame('no_data', $result['status']);
        static::assertSame(0, $result['severity']);
        static::assertSame(0, $result['deviation']);
        static::assertIsArray($result['recommendations']);
    }

    public function testAnalyzeContributorWithOptimalTace(): void
    {
        $contributor = $this->createStub(Contributor::class);

        // Create metrics with TACE = 80 (optimal: between 70 and 90)
        $metrics = [
            $this->createMockMetric(80.0, 20.0, 16.0),
        ];

        $staffingMetricsRepository = $this->createMock(StaffingMetricsRepository::class);
        $staffingMetricsRepository->method('findByPeriod')->willReturn($metrics);

        $service = $this->createService(null, $staffingMetricsRepository);
        $startDate = new DateTime('2024-01-01');
        $endDate = new DateTime('2024-01-31');

        $result = $service->analyzeContributor($contributor, $startDate, $endDate);

        static::assertSame(80.0, $result['tace']);
        static::assertSame(20.0, $result['availability']);
        static::assertSame(16.0, $result['workload']);
        static::assertSame('optimal', $result['status']);
        static::assertSame(0, $result['severity']); // No deviation from ideal center (80)
        static::assertSame(0.0, $result['deviation']);
    }

    public function testAnalyzeContributorWithOverloadedTace(): void
    {
        $contributor = $this->createStub(Contributor::class);

        // Create metrics with TACE = 95 (overloaded: > 90 but < 110)
        $metrics = [
            $this->createMockMetric(95.0, 20.0, 19.0),
        ];

        $staffingMetricsRepository = $this->createMock(StaffingMetricsRepository::class);
        $staffingMetricsRepository->method('findByPeriod')->willReturn($metrics);

        $service = $this->createService(null, $staffingMetricsRepository);
        $startDate = new DateTime('2024-01-01');
        $endDate = new DateTime('2024-01-31');

        $result = $service->analyzeContributor($contributor, $startDate, $endDate);

        static::assertSame(95.0, $result['tace']);
        static::assertSame('overloaded', $result['status']);
        static::assertSame(30, $result['severity']); // abs(95 - 80) * 2 = 30
        static::assertSame(15.0, $result['deviation']); // 95 - 80
    }

    public function testAnalyzeContributorWithUnderutilizedTace(): void
    {
        $contributor = $this->createStub(Contributor::class);

        // Create metrics with TACE = 60 (underutilized: < 70 but > 50)
        $metrics = [
            $this->createMockMetric(60.0, 20.0, 12.0),
        ];

        $staffingMetricsRepository = $this->createMock(StaffingMetricsRepository::class);
        $staffingMetricsRepository->method('findByPeriod')->willReturn($metrics);

        $service = $this->createService(null, $staffingMetricsRepository);
        $startDate = new DateTime('2024-01-01');
        $endDate = new DateTime('2024-01-31');

        $result = $service->analyzeContributor($contributor, $startDate, $endDate);

        static::assertSame(60.0, $result['tace']);
        static::assertSame('underutilized', $result['status']);
        static::assertSame(40, $result['severity']); // abs(60 - 80) * 2 = 40
        static::assertEquals(-20.0, $result['deviation']); // 60 - 80
    }

    public function testAnalyzeContributorWithCriticalHighTace(): void
    {
        $contributor = $this->createStub(Contributor::class);

        // Create metrics with TACE = 115 (critical_high: >= 110)
        $metrics = [
            $this->createMockMetric(115.0, 20.0, 23.0),
        ];

        $staffingMetricsRepository = $this->createMock(StaffingMetricsRepository::class);
        $staffingMetricsRepository->method('findByPeriod')->willReturn($metrics);

        $service = $this->createService(null, $staffingMetricsRepository);
        $startDate = new DateTime('2024-01-01');
        $endDate = new DateTime('2024-01-31');

        $result = $service->analyzeContributor($contributor, $startDate, $endDate);

        static::assertSame(115.0, $result['tace']);
        static::assertSame('critical_high', $result['status']);
        static::assertSame(70, $result['severity']); // abs(115 - 80) * 2 = 70
        static::assertSame(35.0, $result['deviation']); // 115 - 80
    }

    public function testAnalyzeContributorWithCriticalLowTace(): void
    {
        $contributor = $this->createStub(Contributor::class);

        // Create metrics with TACE = 45 (critical_low: <= 50)
        $metrics = [
            $this->createMockMetric(45.0, 20.0, 9.0),
        ];

        $staffingMetricsRepository = $this->createMock(StaffingMetricsRepository::class);
        $staffingMetricsRepository->method('findByPeriod')->willReturn($metrics);

        $service = $this->createService(null, $staffingMetricsRepository);
        $startDate = new DateTime('2024-01-01');
        $endDate = new DateTime('2024-01-31');

        $result = $service->analyzeContributor($contributor, $startDate, $endDate);

        static::assertSame(45.0, $result['tace']);
        static::assertSame('critical_low', $result['status']);
        static::assertSame(70, $result['severity']); // abs(45 - 80) * 2 = 70
        static::assertEquals(-35.0, $result['deviation']); // 45 - 80
    }

    public function testAnalyzeContributorWithMultipleMetricsCalculatesAverage(): void
    {
        $contributor = $this->createStub(Contributor::class);

        // Create metrics with varying TACE values
        $metrics = [
            $this->createMockMetric(75.0, 20.0, 15.0),
            $this->createMockMetric(85.0, 20.0, 17.0),
            $this->createMockMetric(80.0, 20.0, 16.0),
        ];

        $staffingMetricsRepository = $this->createMock(StaffingMetricsRepository::class);
        $staffingMetricsRepository->method('findByPeriod')->willReturn($metrics);

        $service = $this->createService(null, $staffingMetricsRepository);
        $startDate = new DateTime('2024-01-01');
        $endDate = new DateTime('2024-01-31');

        $result = $service->analyzeContributor($contributor, $startDate, $endDate);

        // Average: (75 + 85 + 80) / 3 = 80
        static::assertSame(80.0, $result['tace']);
        static::assertSame(20.0, $result['availability']); // (20+20+20)/3
        static::assertSame(16.0, $result['workload']); // (15+17+16)/3
        static::assertSame('optimal', $result['status']);
    }

    public function testAnalyzeContributorRoundingToTwoDecimals(): void
    {
        $contributor = $this->createStub(Contributor::class);

        // Create metrics that will produce decimals when averaged
        $metrics = [
            $this->createMockMetric(77.777, 20.555, 15.333),
            $this->createMockMetric(88.888, 19.444, 16.777),
        ];

        $staffingMetricsRepository = $this->createMock(StaffingMetricsRepository::class);
        $staffingMetricsRepository->method('findByPeriod')->willReturn($metrics);

        $service = $this->createService(null, $staffingMetricsRepository);
        $startDate = new DateTime('2024-01-01');
        $endDate = new DateTime('2024-01-31');

        $result = $service->analyzeContributor($contributor, $startDate, $endDate);

        // Verify rounding to 2 decimals
        static::assertSame(83.33, $result['tace']); // (77.777 + 88.888) / 2 = 83.3325 → 83.33
        static::assertSame(20.0, $result['availability']); // (20.555 + 19.444) / 2 = 19.9995 → 20.00
        static::assertSame(16.06, $result['workload']); // (15.333 + 16.777) / 2 = 16.055 → 16.06
    }

    public function testAnalyzeAllContributorsWithNoActiveContributors(): void
    {
        $contributorRepository = $this->createMock(ContributorRepository::class);
        $contributorRepository->expects(self::once())->method('findBy')->with(['active' => true])->willReturn([]);

        $service = $this->createService($contributorRepository);
        $result = $service->analyzeAllContributors();

        static::assertIsArray($result);
        static::assertArrayHasKey('overloaded', $result);
        static::assertArrayHasKey('underutilized', $result);
        static::assertArrayHasKey('optimal', $result);
        static::assertArrayHasKey('critical', $result);

        static::assertEmpty($result['overloaded']);
        static::assertEmpty($result['underutilized']);
        static::assertEmpty($result['optimal']);
        static::assertEmpty($result['critical']);
    }

    public function testAnalyzeAllContributorsWithDefaultDates(): void
    {
        $contributor = $this->createStub(Contributor::class);

        $contributorRepository = $this->createMock(ContributorRepository::class);
        $contributorRepository
            ->expects(self::once())
            ->method('findBy')
            ->with(['active' => true])
            ->willReturn([$contributor]);

        $staffingMetricsRepository = $this->createMock(StaffingMetricsRepository::class);

        // Expect findByPeriod to be called with dates from current month
        $staffingMetricsRepository
            ->expects($this->once())
            ->method('findByPeriod')
            ->with(
                static::callback(static function ($startDate) {
                    $expectedStart = new DateTime('first day of this month');

                    return $startDate->format('Y-m-d') === $expectedStart->format('Y-m-d');
                }),
                static::callback(static function ($endDate) {
                    $expectedEnd = new DateTime('last day of this month');

                    return $endDate->format('Y-m-d') === $expectedEnd->format('Y-m-d');
                }),
                'weekly',
                null,
                $contributor,
            )
            ->willReturn([]);

        $service = $this->createService($contributorRepository, $staffingMetricsRepository);
        $service->analyzeAllContributors(); // No dates provided
    }

    public function testAnalyzeAllContributorsCategorizesCorrectly(): void
    {
        $optimalContributor = $this->createStub(Contributor::class);
        $overloadedContributor = $this->createStub(Contributor::class);
        $underutilizedContributor = $this->createStub(Contributor::class);
        $criticalHighContributor = $this->createStub(Contributor::class);
        $criticalLowContributor = $this->createStub(Contributor::class);

        $contributorRepository = $this->createMock(ContributorRepository::class);
        $contributorRepository
            ->method('findBy')
            ->willReturn([
                $optimalContributor,
                $overloadedContributor,
                $underutilizedContributor,
                $criticalHighContributor,
                $criticalLowContributor,
            ]);

        $staffingMetricsRepository = $this->createMock(StaffingMetricsRepository::class);
        $staffingMetricsRepository
            ->method('findByPeriod')
            ->willReturnCallback(function ($start, $end, $granularity, $profile, $contributor) use (
                $optimalContributor,
                $overloadedContributor,
                $underutilizedContributor,
                $criticalHighContributor,
                $criticalLowContributor,
            ) {
                if ($contributor === $optimalContributor) {
                    return [$this->createMockMetric(80.0, 20.0, 16.0)];
                }
                if ($contributor === $overloadedContributor) {
                    return [$this->createMockMetric(95.0, 20.0, 19.0)];
                }
                if ($contributor === $underutilizedContributor) {
                    return [$this->createMockMetric(60.0, 20.0, 12.0)];
                }
                if ($contributor === $criticalHighContributor) {
                    return [$this->createMockMetric(115.0, 20.0, 23.0)];
                }
                if ($contributor === $criticalLowContributor) {
                    return [$this->createMockMetric(45.0, 20.0, 9.0)];
                }

                return [];
            });

        $service = $this->createService($contributorRepository, $staffingMetricsRepository);
        $startDate = new DateTime('2024-01-01');
        $endDate = new DateTime('2024-01-31');

        $result = $service->analyzeAllContributors($startDate, $endDate);

        static::assertCount(1, $result['optimal']);
        static::assertCount(1, $result['overloaded']);
        static::assertCount(1, $result['underutilized']);
        static::assertCount(2, $result['critical']); // Both critical_high and critical_low

        static::assertSame($optimalContributor, $result['optimal'][0]['contributor']);
        static::assertSame($overloadedContributor, $result['overloaded'][0]['contributor']);
        static::assertSame($underutilizedContributor, $result['underutilized'][0]['contributor']);
    }

    public function testAnalyzeAllContributorsSortsOverloadedByTaceDescending(): void
    {
        $contributor1 = $this->createStub(Contributor::class);
        $contributor2 = $this->createStub(Contributor::class);
        $contributor3 = $this->createStub(Contributor::class);

        $contributorRepository = $this->createMock(ContributorRepository::class);
        $contributorRepository->method('findBy')->willReturn([$contributor1, $contributor2, $contributor3]);

        $staffingMetricsRepository = $this->createMock(StaffingMetricsRepository::class);
        $staffingMetricsRepository
            ->method('findByPeriod')
            ->willReturnCallback(function ($start, $end, $granularity, $profile, $contributor) use (
                $contributor1,
                $contributor2,
                $contributor3,
            ) {
                if ($contributor === $contributor1) {
                    return [$this->createMockMetric(92.0, 20.0, 18.4)];
                }
                if ($contributor === $contributor2) {
                    return [$this->createMockMetric(98.0, 20.0, 19.6)];
                }
                if ($contributor === $contributor3) {
                    return [$this->createMockMetric(95.0, 20.0, 19.0)];
                }

                return [];
            });

        $service = $this->createService($contributorRepository, $staffingMetricsRepository);
        $result = $service->analyzeAllContributors(new DateTime('2024-01-01'), new DateTime('2024-01-31'));

        static::assertCount(3, $result['overloaded']);
        // Should be sorted by TACE descending: 98, 95, 92
        static::assertSame(98.0, $result['overloaded'][0]['tace']);
        static::assertSame(95.0, $result['overloaded'][1]['tace']);
        static::assertSame(92.0, $result['overloaded'][2]['tace']);
    }

    public function testAnalyzeAllContributorsSortsUnderutilizedByTaceAscending(): void
    {
        $contributor1 = $this->createStub(Contributor::class);
        $contributor2 = $this->createStub(Contributor::class);
        $contributor3 = $this->createStub(Contributor::class);

        $contributorRepository = $this->createMock(ContributorRepository::class);
        $contributorRepository->method('findBy')->willReturn([$contributor1, $contributor2, $contributor3]);

        $staffingMetricsRepository = $this->createMock(StaffingMetricsRepository::class);
        $staffingMetricsRepository
            ->method('findByPeriod')
            ->willReturnCallback(function ($start, $end, $granularity, $profile, $contributor) use (
                $contributor1,
                $contributor2,
                $contributor3,
            ) {
                if ($contributor === $contributor1) {
                    return [$this->createMockMetric(65.0, 20.0, 13.0)];
                }
                if ($contributor === $contributor2) {
                    return [$this->createMockMetric(55.0, 20.0, 11.0)];
                }
                if ($contributor === $contributor3) {
                    return [$this->createMockMetric(60.0, 20.0, 12.0)];
                }

                return [];
            });

        $service = $this->createService($contributorRepository, $staffingMetricsRepository);
        $result = $service->analyzeAllContributors(new DateTime('2024-01-01'), new DateTime('2024-01-31'));

        static::assertCount(3, $result['underutilized']);
        // Should be sorted by TACE ascending: 55, 60, 65
        static::assertSame(55.0, $result['underutilized'][0]['tace']);
        static::assertSame(60.0, $result['underutilized'][1]['tace']);
        static::assertSame(65.0, $result['underutilized'][2]['tace']);
    }

    public function testAnalyzeAllContributorsSortsCriticalByDeviationFromIdeal(): void
    {
        $contributor1 = $this->createStub(Contributor::class);
        $contributor2 = $this->createStub(Contributor::class);
        $contributor3 = $this->createStub(Contributor::class);

        $contributorRepository = $this->createMock(ContributorRepository::class);
        $contributorRepository->method('findBy')->willReturn([$contributor1, $contributor2, $contributor3]);

        $staffingMetricsRepository = $this->createMock(StaffingMetricsRepository::class);
        $staffingMetricsRepository
            ->method('findByPeriod')
            ->willReturnCallback(function ($start, $end, $granularity, $profile, $contributor) use (
                $contributor1,
                $contributor2,
                $contributor3,
            ) {
                if ($contributor === $contributor1) {
                    return [$this->createMockMetric(115.0, 20.0, 23.0)]; // deviation from 80 = 35
                }
                if ($contributor === $contributor2) {
                    return [$this->createMockMetric(45.0, 20.0, 9.0)]; // deviation from 80 = -35
                }
                if ($contributor === $contributor3) {
                    return [$this->createMockMetric(120.0, 20.0, 24.0)]; // deviation from 80 = 40 (highest)
                }

                return [];
            });

        $service = $this->createService($contributorRepository, $staffingMetricsRepository);
        $result = $service->analyzeAllContributors(new DateTime('2024-01-01'), new DateTime('2024-01-31'));

        static::assertCount(3, $result['critical']);
        // Should be sorted by abs(tace - 80) descending: 120 (abs=40), 115 (abs=35), 45 (abs=35)
        static::assertSame(120.0, $result['critical'][0]['tace']);
        // 115 and 45 both have abs deviation of 35, order may vary
        static::assertTrue(in_array($result['critical'][1]['tace'], [115.0, 45.0], true));
        static::assertTrue(in_array($result['critical'][2]['tace'], [115.0, 45.0], true));
    }

    public function testCalculateSeverityPrivateMethod(): void
    {
        $service = $this->createService();
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('calculateSeverity');

        // Test various TACE values
        static::assertSame(0, $method->invoke($service, 80.0)); // Ideal center
        static::assertSame(20, $method->invoke($service, 90.0)); // abs(90-80) * 2
        static::assertSame(20, $method->invoke($service, 70.0)); // abs(70-80) * 2
        static::assertSame(60, $method->invoke($service, 110.0)); // abs(110-80) * 2
        static::assertSame(70, $method->invoke($service, 45.0)); // abs(45-80) * 2
        static::assertSame(100, $method->invoke($service, 130.0)); // min(100, abs(130-80) * 2)
    }

    public function testCalculateDeviationPrivateMethod(): void
    {
        $service = $this->createService();
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('calculateDeviation');

        static::assertSame(0.0, $method->invoke($service, 80.0));
        static::assertSame(10.0, $method->invoke($service, 90.0));
        static::assertEquals(-10.0, $method->invoke($service, 70.0));
        static::assertSame(30.0, $method->invoke($service, 110.0));
        static::assertEquals(-35.0, $method->invoke($service, 45.0));
    }

    public function testDetermineStatusPrivateMethod(): void
    {
        $service = $this->createService();
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('determineStatus');

        // Optimal range (70-90)
        static::assertSame('optimal', $method->invoke($service, 70.0));
        static::assertSame('optimal', $method->invoke($service, 80.0));
        static::assertSame('optimal', $method->invoke($service, 90.0));

        // Underutilized (50-70)
        static::assertSame('underutilized', $method->invoke($service, 69.9));
        static::assertSame('underutilized', $method->invoke($service, 60.0));
        static::assertSame('underutilized', $method->invoke($service, 50.1));

        // Overloaded (90-110)
        static::assertSame('overloaded', $method->invoke($service, 90.1));
        static::assertSame('overloaded', $method->invoke($service, 100.0));
        static::assertSame('overloaded', $method->invoke($service, 109.9));

        // Critical low (<=50)
        static::assertSame('critical_low', $method->invoke($service, 50.0));
        static::assertSame('critical_low', $method->invoke($service, 45.0));
        static::assertSame('critical_low', $method->invoke($service, 0.0));

        // Critical high (>=110)
        static::assertSame('critical_high', $method->invoke($service, 110.0));
        static::assertSame('critical_high', $method->invoke($service, 115.0));
        static::assertSame('critical_high', $method->invoke($service, 150.0));
    }
}
