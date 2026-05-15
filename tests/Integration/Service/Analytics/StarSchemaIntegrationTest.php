<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\Analytics;

use App\Entity\Analytics\DimTime;
use App\Entity\Analytics\FactProjectMetrics;
use App\Factory\DimProjectTypeFactory;
use App\Factory\DimTimeFactory;
use App\Service\Analytics\DashboardReadService;
use App\Tests\Support\MultiTenantTestTrait;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Tests d'intégration pour le modèle en étoile (Star Schema).
 * Vérifie que les données sont correctement stockées et lues depuis les tables dimensionnelles.
 */
class StarSchemaIntegrationTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;
    use MultiTenantTestTrait;

    private EntityManagerInterface $entityManager;
    private DashboardReadService $dashboardReadService;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->dashboardReadService = static::getContainer()->get(DashboardReadService::class);
        $this->setUpMultiTenant();
    }

    public function testDimTimeCanBeCreatedAndQueried(): void
    {
        // Create DimTime entry - only set date, other fields auto-calculated
        $dimTime1 = DimTimeFactory::createOne([
            'date' => new DateTime('2025-01-15'),
        ]);

        $this->entityManager->flush();
        $this->entityManager->clear();

        // Query it back
        $found = $this->entityManager->getRepository(DimTime::class)->find($dimTime1->getId());

        static::assertNotNull($found);
        static::assertSame(2025, $found->getYear());
        static::assertSame(1, $found->getMonth());
        static::assertSame('Janvier 2025', $found->getMonthName());
    }

    public function testFactProjectMetricsCanBeCreatedAndAggregated(): void
    {
        // Create test data - only set date, other fields auto-calculated
        $dimTime1 = DimTimeFactory::createOne([
            'date' => new DateTime('2025-01-01'),
        ]);

        $dimTime2 = DimTimeFactory::createOne([
            'date' => new DateTime('2025-02-01'),
        ]);

        $dimProjectType = DimProjectTypeFactory::createOne([
            'projectType' => 'forfait',
            'status' => 'active',
        ]);

        $fact1 = new FactProjectMetrics()
            ->setCompany($this->getTestCompany())
            ->setDimTime($dimTime1)
            ->setDimProjectType($dimProjectType)
            ->setGranularity('monthly')
            ->setTotalRevenue('10000.00')
            ->setTotalCosts('7000.00')
            ->setGrossMargin('3000.00')
            ->setMarginPercentage('30.00')
            ->setProjectCount(5)
            ->setActiveProjectCount(3)
            ->setCompletedProjectCount(2);

        $this->entityManager->persist($fact1);

        $fact2 = new FactProjectMetrics()
            ->setCompany($this->getTestCompany())
            ->setDimTime($dimTime2)
            ->setDimProjectType($dimProjectType)
            ->setGranularity('monthly')
            ->setTotalRevenue('12000.00')
            ->setTotalCosts('8000.00')
            ->setGrossMargin('4000.00')
            ->setMarginPercentage('33.33')
            ->setProjectCount(6)
            ->setActiveProjectCount(4)
            ->setCompletedProjectCount(2);

        $this->entityManager->persist($fact2);

        $this->entityManager->flush();
        $this->entityManager->clear();

        // Query aggregated data
        $qb = $this->entityManager->createQueryBuilder();
        $result = $qb
            ->select('SUM(f.totalRevenue) as totalRevenue', 'SUM(f.projectCount) as totalProjects')
            ->from(FactProjectMetrics::class, 'f')
            ->join('f.dimTime', 'dt')
            ->where('dt.year = :year')
            ->setParameter('year', 2025)
            ->getQuery()
            ->getSingleResult();

        static::assertSame(22_000.0, (float) $result['totalRevenue']);
        static::assertSame(11, (int) $result['totalProjects']);
    }

    public function testDashboardReadServiceReadsFromStarSchema(): void
    {
        // Create test data for January 2025 - only set date
        $dimTime = DimTimeFactory::createOne([
            'date' => new DateTime('2025-01-15'),
        ]);

        $dimProjectType = DimProjectTypeFactory::createOne([
            'projectType' => 'forfait',
            'status' => 'active',
        ]);

        $fact = new FactProjectMetrics()
            ->setCompany($this->getTestCompany())
            ->setDimTime($dimTime)
            ->setDimProjectType($dimProjectType)
            ->setGranularity('monthly')
            ->setTotalRevenue('15000.00')
            ->setTotalCosts('10000.00')
            ->setGrossMargin('5000.00')
            ->setMarginPercentage('33.33')
            ->setProjectCount(8)
            ->setActiveProjectCount(5)
            ->setCompletedProjectCount(3)
            ->setOrderCount(12)
            ->setWonOrderCount(8)
            ->setPendingOrderCount(4)
            ->setPendingRevenue('20000.00')
            ->setTotalWorkedDays('100.00')
            ->setTotalSoldDays('120.00');

        $this->entityManager->persist($fact);

        $this->entityManager->flush();

        // Use the service to read KPIs
        $kpis = $this->dashboardReadService->getKPIs(new DateTime('2025-01-01'), new DateTime('2025-01-31'));

        // Verify the structure and values
        static::assertArrayHasKey('revenue', $kpis);
        static::assertArrayHasKey('projects', $kpis);
        static::assertArrayHasKey('orders', $kpis);
        static::assertArrayHasKey('time', $kpis);

        static::assertSame(15_000.0, $kpis['revenue']['total_revenue']);
        static::assertSame(10_000.0, $kpis['revenue']['total_cost']);
        static::assertSame(5000.0, $kpis['revenue']['total_margin']);
        static::assertSame(8, $kpis['projects']['total']);
        static::assertSame(5, $kpis['projects']['active']);
        static::assertSame(3, $kpis['projects']['completed']);
    }

    public function testMonthlyEvolutionReturnsCorrectData(): void
    {
        // Create dimension for project type
        $dimProjectType = DimProjectTypeFactory::createOne([
            'projectType' => 'forfait',
            'status' => 'active',
        ]);

        // Create 3 months of data using relative dates (current month and 2 previous months)
        // This ensures data is always within the "last 12 months" range
        $baseDate = new DateTime('first day of this month');
        $expectedMonths = [];

        for ($i = 2; $i >= 0; --$i) {
            $date = (clone $baseDate)->modify("-{$i} months")->modify('+14 days');
            $dimTime = DimTimeFactory::createOne([
                'date' => $date,
            ]);

            $multiplier = 3 - $i; // 1, 2, 3
            $fact = new FactProjectMetrics()
                ->setCompany($this->getTestCompany())
                ->setDimTime($dimTime)
                ->setDimProjectType($dimProjectType)
                ->setGranularity('monthly')
                ->setTotalRevenue((string) ($multiplier * 10_000))
                ->setTotalCosts((string) ($multiplier * 7000))
                ->setGrossMargin((string) ($multiplier * 3000));

            $this->entityManager->persist($fact);
            $expectedMonths[] = $dimTime->getMonthName();
        }

        $this->entityManager->flush();

        // Get monthly evolution
        $evolution = $this->dashboardReadService->getMonthlyEvolution(12);

        // Should return 3 months
        static::assertCount(3, $evolution);
        static::assertEquals($expectedMonths[0], $evolution[0]['month']);
        static::assertSame(10_000.0, $evolution[0]['revenue']);
        static::assertSame(7000.0, $evolution[0]['costs']);
        static::assertSame(3000.0, $evolution[0]['margin']);
    }

    public function testQueryPerformanceWithLargeDataset(): void
    {
        // Create dimension for project type
        $dimProjectType = DimProjectTypeFactory::createOne([
            'projectType' => 'forfait',
            'status' => 'active',
        ]);

        // Create 100 entries to test performance - only set date
        for ($i = 1; $i <= 100; ++$i) {
            $date = new DateTime('2025-01-01')->modify("+{$i} days");
            $dimTime = DimTimeFactory::createOne([
                'date' => $date,
            ]);

            $fact = new FactProjectMetrics()
                ->setCompany($this->getTestCompany())
                ->setDimTime($dimTime)
                ->setDimProjectType($dimProjectType)
                ->setGranularity('daily')
                ->setTotalRevenue('1000.00')
                ->setTotalCosts('700.00')
                ->setGrossMargin('300.00');

            $this->entityManager->persist($fact);
        }

        $this->entityManager->flush();

        // Measure query time
        $start = microtime(true);

        $kpis = $this->dashboardReadService->getKPIs(new DateTime('2025-01-01'), new DateTime('2025-12-31'));

        $duration = microtime(true) - $start;

        // Query should complete in less than 1 second
        static::assertLessThan(1.0, $duration, 'Query took too long: '.$duration.'s');
        static::assertSame(100_000.0, $kpis['revenue']['total_revenue']);
    }

    public function testDataIntegrityConstraints(): void
    {
        // Test that DimTime date must be unique - only set date
        $date = new DateTime('2025-01-01');

        DimTimeFactory::createOne([
            'date' => $date,
        ]);

        $this->entityManager->flush();

        // Trying to create another entry with the same date should fail
        $this->expectException(Exception::class);

        $dimTime2 = DimTimeFactory::createOne([
            'date' => $date,
        ]);

        $this->entityManager->flush();
    }
}
