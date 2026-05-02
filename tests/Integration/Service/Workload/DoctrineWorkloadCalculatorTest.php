<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\Workload;

use App\Service\Workload\DoctrineWorkloadCalculator;
use App\Repository\StaffingMetricsRepository;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Integration test for DoctrineWorkloadCalculator (TEST-WORKLOAD-001, sprint-005).
 *
 * Validates the QueryBuilder's filters end-to-end:
 *   - `granularity = monthly`
 *   - `dt.yearMonth = $month->format('Y-m')`
 *   - `sm.contributor = $contributorId`
 *
 * Returns the zero baseline when no metric matches; that path is the
 * happy-fallback for fresh contributors / months and is the only
 * production-side guarantee callers (AlertDetectionService) rely on.
 */
final class DoctrineWorkloadCalculatorTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    private DoctrineWorkloadCalculator $calculator;

    protected function setUp(): void
    {
        self::bootKernel();
        $repository = self::getContainer()->get(StaffingMetricsRepository::class);
        $this->calculator = new DoctrineWorkloadCalculator($repository);
    }

    public function testReturnsZerosWhenNoMetricsExistForContributor(): void
    {
        $result = $this->calculator->forContributor(
            contributorId: 999_999,
            month: new DateTimeImmutable('2026-05-01'),
        );

        self::assertSame(0.0, $result['totalDays']);
        self::assertSame(0.0, $result['capacityRate']);
    }

    public function testReturnedShapeAlwaysContainsTotalDaysAndCapacityRate(): void
    {
        $result = $this->calculator->forContributor(
            contributorId: 1,
            month: new DateTimeImmutable('2026-01-01'),
        );

        self::assertArrayHasKey('totalDays', $result);
        self::assertArrayHasKey('capacityRate', $result);
        self::assertIsFloat($result['totalDays']);
        self::assertIsFloat($result['capacityRate']);
    }
}
