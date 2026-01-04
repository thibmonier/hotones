<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Analytics;

use App\Security\CompanyContext;
use App\Service\Analytics\DashboardReadService;
use App\Service\MetricsCalculationService as RealTimeService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Tests unitaires pour DashboardReadService.
 */
class DashboardReadServiceTest extends TestCase
{
    private EntityManagerInterface|MockObject $entityManager;
    private RealTimeService|MockObject $realTimeService;
    private CompanyContext|MockObject $companyContext;
    private LoggerInterface|MockObject $logger;
    private CacheInterface|MockObject $cache;
    private DashboardReadService $service;

    protected function setUp(): void
    {
        $this->entityManager   = $this->createMock(EntityManagerInterface::class);
        $this->realTimeService = $this->createMock(RealTimeService::class);
        $this->companyContext  = $this->createMock(CompanyContext::class);
        $this->logger          = $this->createMock(LoggerInterface::class);
        $this->cache           = $this->createMock(CacheInterface::class);

        $this->service = new DashboardReadService(
            $this->entityManager,
            $this->realTimeService,
            $this->companyContext,
            $this->logger,
            $this->cache,
        );
    }

    public function testGetKPIsReturnsDataFromStarSchema(): void
    {
        $startDate = new DateTime('2025-01-01');
        $endDate   = new DateTime('2025-01-31');

        // Mock QueryBuilder et Query
        $query = $this->createMock(Query::class);
        $query->expects($this->once())
            ->method('getSingleResult')
            ->willReturn([
                'totalRevenue'        => '10000.00',
                'totalCosts'          => '7000.00',
                'grossMargin'         => '3000.00',
                'avgMarginPercentage' => '30.00',
                'totalProjects'       => '5',
                'activeProjects'      => '3',
                'completedProjects'   => '2',
                'totalOrders'         => '10',
                'pendingOrders'       => '3',
                'wonOrders'           => '7',
                'pendingRevenue'      => '5000.00',
                'totalWorkedDays'     => '100.00',
                'totalSoldDays'       => '120.00',
                'avgUtilization'      => '83.33',
            ]);
        $query->method('getResult')->willReturn([]);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('join')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $this->entityManager->expects($this->any())
            ->method('createQueryBuilder')
            ->willReturn($qb);

        // Mock cache to execute callback immediately
        $this->cache->expects($this->once())
            ->method('get')
            ->willReturnCallback(fn ($key, $callback) => $callback($this->createMock(\Symfony\Contracts\Cache\ItemInterface::class)));

        $result = $this->service->getKPIs($startDate, $endDate);

        $this->assertIsArray($result);
        $this->assertEquals(10000.00, $result['revenue']['total_revenue']);
        $this->assertEquals(7000.00, $result['revenue']['total_cost']);
        $this->assertEquals(3000.00, $result['revenue']['total_margin']);
        $this->assertEquals(30.00, $result['revenue']['margin_rate']);
        $this->assertEquals(5, $result['projects']['total']);
        $this->assertEquals(3, $result['projects']['active']);
    }

    public function testGetKPIsFallbackToRealTimeWhenNoData(): void
    {
        $startDate = new DateTime('2025-01-01');
        $endDate   = new DateTime('2025-01-31');

        // Mock QueryBuilder qui retourne des données vides
        $query = $this->createMock(Query::class);
        $query->expects($this->once())
            ->method('getSingleResult')
            ->willReturn([
                'totalRevenue' => '0',
                'totalCosts'   => '0',
                'grossMargin'  => '0',
            ]);
        $query->method('getResult')->willReturn([]);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('join')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $this->entityManager->expects($this->any())
            ->method('createQueryBuilder')
            ->willReturn($qb);

        // Vérifier que le service temps réel est appelé en fallback
        $realTimeData = [
            'period'  => ['start' => $startDate, 'end' => $endDate],
            'revenue' => [
                'total_revenue' => 15000.00,
                'total_cost'    => 10000.00,
                'total_margin'  => 5000.00,
                'margin_rate'   => 33.33,
            ],
            'projects' => ['total' => 10, 'active' => 5, 'completed' => 5],
        ];

        $this->realTimeService->expects($this->once())
            ->method('calculateKPIs')
            ->with($startDate, $endDate, [])
            ->willReturn($realTimeData);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                $this->stringContains('Aucune donnée dans le modèle en étoile'),
                $this->anything(),
            );

        // Mock cache to execute callback immediately
        $this->cache->expects($this->once())
            ->method('get')
            ->willReturnCallback(fn ($key, $callback) => $callback($this->createMock(\Symfony\Contracts\Cache\ItemInterface::class)));

        $result = $this->service->getKPIs($startDate, $endDate);

        $this->assertEquals($realTimeData, $result);
    }

    public function testGetMonthlyEvolutionReturnsFormattedData(): void
    {
        // Mock Query qui retourne l'évolution mensuelle
        $query = $this->createMock(Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([
                [
                    'year'         => 2025,
                    'month'        => 1,
                    'monthName'    => 'Janvier 2025',
                    'totalRevenue' => '10000.00',
                    'totalCosts'   => '7000.00',
                    'grossMargin'  => '3000.00',
                ],
                [
                    'year'         => 2025,
                    'month'        => 2,
                    'monthName'    => 'Février 2025',
                    'totalRevenue' => '12000.00',
                    'totalCosts'   => '8000.00',
                    'grossMargin'  => '4000.00',
                ],
            ]);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('join')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('groupBy')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('addOrderBy')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $this->entityManager->expects($this->any())
            ->method('createQueryBuilder')
            ->willReturn($qb);

        // Mock cache to execute callback immediately
        $this->cache->expects($this->once())
            ->method('get')
            ->willReturnCallback(fn ($key, $callback) => $callback($this->createMock(\Symfony\Contracts\Cache\ItemInterface::class)));

        $result = $this->service->getMonthlyEvolution(12);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('Janvier 2025', $result[0]['month']);
        $this->assertEquals(10000.00, $result[0]['revenue']);
        $this->assertEquals(7000.00, $result[0]['costs']);
        $this->assertEquals(3000.00, $result[0]['margin']);
    }

    public function testGetMonthlyEvolutionFallbackWhenNoData(): void
    {
        // Mock Query qui retourne aucune donnée
        $query = $this->createMock(Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('join')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('groupBy')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('addOrderBy')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $this->entityManager->expects($this->any())
            ->method('createQueryBuilder')
            ->willReturn($qb);

        $realTimeEvolution = [
            ['month' => 'Jan 2025', 'revenue' => 5000, 'costs' => 3000, 'margin' => 2000],
        ];

        $this->realTimeService->expects($this->once())
            ->method('calculateMonthlyEvolution')
            ->with(12, [])
            ->willReturn($realTimeEvolution);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('Pas de données d\'évolution'));

        // Mock cache to execute callback immediately
        $this->cache->expects($this->once())
            ->method('get')
            ->willReturnCallback(fn ($key, $callback) => $callback($this->createMock(\Symfony\Contracts\Cache\ItemInterface::class)));

        $result = $this->service->getMonthlyEvolution(12);

        $this->assertEquals($realTimeEvolution, $result);
    }
}
