<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Analytics;

use App\Entity\Analytics\DimTime;
use App\Entity\Project;
use App\Repository\ProjectRepository;
use App\Security\CompanyContext;
use App\Service\Analytics\MetricsCalculationService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class MetricsCalculationServiceTest extends TestCase
{
    private MockObject $entityManager;
    private MockObject $companyContext;
    private MockObject $logger;
    private MetricsCalculationService $service;

    protected function setUp(): void
    {
        $this->entityManager  = $this->createMock(EntityManagerInterface::class);
        $this->companyContext = $this->createMock(CompanyContext::class);
        $this->logger         = $this->createMock(LoggerInterface::class);

        $this->service = new MetricsCalculationService($this->entityManager, $this->companyContext, $this->logger);
    }

    public function testCalculateMetricsForPeriodWithMonthlyGranularity(): void
    {
        $date = new DateTime('2025-01-15');

        // Mock DimTime repository
        $dimTimeRepo = $this->createMock(EntityRepository::class);
        $dimTime     = $this->createMock(DimTime::class);
        $dimTime->method('getDate')->willReturn($date);

        $dimTimeRepo->expects($this->once())->method('findOneBy')->with(['date' => $date])->willReturn($dimTime);

        // Mock Project repository
        $projectRepo  = $this->createMock(ProjectRepository::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query        = $this->createMock(Query::class);

        $projectRepo->expects($this->once())->method('createQueryBuilder')->with('p')->willReturn($queryBuilder);

        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);

        // Return empty projects list for simplicity
        $query->expects($this->once())->method('getResult')->willReturn([]);

        // Mock repository creation
        $this->entityManager
            ->expects($this->exactly(2))
            ->method('getRepository')
            ->willReturnCallback(function ($entityClass) use ($dimTimeRepo, $projectRepo) {
                if ($entityClass === DimTime::class) {
                    return $dimTimeRepo;
                }
                if ($entityClass === Project::class) {
                    return $projectRepo;
                }

                return null;
            });

        // Expect flush to be called
        $this->entityManager->expects($this->once())->method('flush');

        // Expect logging
        $this->logger->expects($this->exactly(2))->method('info');

        $this->service->calculateMetricsForPeriod($date, 'monthly');
    }

    public function testCalculateMetricsForPeriodWithQuarterlyGranularity(): void
    {
        $date = new DateTime('2025-04-01'); // Q2

        // Mock DimTime repository
        $dimTimeRepo = $this->createMock(EntityRepository::class);
        $dimTime     = new DimTime();
        $dimTime->setDate($date);

        $dimTimeRepo->expects($this->once())->method('findOneBy')->willReturn($dimTime);

        // Mock Project repository
        $projectRepo  = $this->createMock(ProjectRepository::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query        = $this->createMock(Query::class);

        $projectRepo->method('createQueryBuilder')->willReturn($queryBuilder);
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);
        $query->method('getResult')->willReturn([]);

        $this->entityManager
            ->method('getRepository')
            ->willReturnCallback(function ($entityClass) use ($dimTimeRepo, $projectRepo) {
                if ($entityClass === DimTime::class) {
                    return $dimTimeRepo;
                }
                if ($entityClass === Project::class) {
                    return $projectRepo;
                }

                return null;
            });

        $this->entityManager->expects($this->once())->method('flush');

        $this->logger->expects($this->exactly(2))->method('info');

        $this->service->calculateMetricsForPeriod($date, 'quarterly');
    }

    public function testCalculateMetricsForPeriodWithYearlyGranularity(): void
    {
        $date = new DateTime('2025-01-01');

        // Mock repositories
        $dimTimeRepo = $this->createMock(EntityRepository::class);
        $dimTime     = new DimTime();
        $dimTime->setDate($date);
        $dimTimeRepo->method('findOneBy')->willReturn($dimTime);

        $projectRepo  = $this->createMock(ProjectRepository::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query        = $this->createMock(Query::class);

        $projectRepo->method('createQueryBuilder')->willReturn($queryBuilder);
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);
        $query->method('getResult')->willReturn([]);

        $this->entityManager
            ->method('getRepository')
            ->willReturnCallback(function ($entityClass) use ($dimTimeRepo, $projectRepo) {
                if ($entityClass === DimTime::class) {
                    return $dimTimeRepo;
                }
                if ($entityClass === Project::class) {
                    return $projectRepo;
                }

                return null;
            });

        $this->entityManager->expects($this->once())->method('flush');
        $this->logger->expects($this->exactly(2))->method('info');

        $this->service->calculateMetricsForPeriod($date, 'yearly');
    }

    public function testCalculateMetricsForPeriodCreatesNewDimTimeWhenNotExists(): void
    {
        $date = new DateTime('2025-01-15');

        // Mock DimTime repository - return null (doesn't exist)
        $dimTimeRepo = $this->createMock(EntityRepository::class);
        $dimTimeRepo->expects($this->once())->method('findOneBy')->with(['date' => $date])->willReturn(null);

        // Mock Project repository
        $projectRepo  = $this->createMock(ProjectRepository::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query        = $this->createMock(Query::class);

        $projectRepo->method('createQueryBuilder')->willReturn($queryBuilder);
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);
        $query->method('getResult')->willReturn([]);

        $this->entityManager
            ->method('getRepository')
            ->willReturnCallback(function ($entityClass) use ($dimTimeRepo, $projectRepo) {
                if ($entityClass === DimTime::class) {
                    return $dimTimeRepo;
                }
                if ($entityClass === Project::class) {
                    return $projectRepo;
                }

                return null;
            });

        // Expect persist to be called for new DimTime
        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(DimTime::class));

        // Expect flush to be called twice: once for DimTime, once at the end
        $this->entityManager->expects($this->exactly(2))->method('flush');

        $this->service->calculateMetricsForPeriod($date, 'monthly');
    }

    public function testCalculateMetricsForPeriodLogsErrorOnException(): void
    {
        $date         = new DateTime('2025-01-15');
        $errorMessage = 'Database connection failed';

        // Mock repository to throw exception
        $dimTimeRepo = $this->createMock(EntityRepository::class);
        $dimTimeRepo->expects($this->once())->method('findOneBy')->willThrowException(new Exception($errorMessage));

        $this->entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(DimTime::class)
            ->willReturn($dimTimeRepo);

        // Expect error logging
        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Erreur lors du calcul des métriques',
                $this->callback(fn ($context): bool => isset($context['error']) && $context['error'] === $errorMessage),
            );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage($errorMessage);

        $this->service->calculateMetricsForPeriod($date, 'monthly');
    }

    public function testRecalculateMetricsForYearDeletesExistingMetrics(): void
    {
        $year = 2025;

        // Mock DQL delete query
        $deleteQuery = $this->createMock(Query::class);
        $deleteQuery->method('getResult')->willReturn([]);

        $this->entityManager
            ->expects($this->once())
            ->method('createQuery')
            ->with($this->stringContains('DELETE FROM App\Entity\Analytics\FactProjectMetrics'))
            ->willReturn($deleteQuery);

        // Mock repositories for metric calculations
        $dimTimeRepo  = $this->createMock(EntityRepository::class);
        $projectRepo  = $this->createMock(ProjectRepository::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query        = $this->createMock(Query::class);

        $dimTimeRepo->method('findOneBy')->willReturn(new DimTime());
        $projectRepo->method('createQueryBuilder')->willReturn($queryBuilder);
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);
        $query->method('getResult')->willReturn([]);

        $this->entityManager
            ->method('getRepository')
            ->willReturnCallback(function ($entityClass) use ($dimTimeRepo, $projectRepo) {
                if ($entityClass === DimTime::class) {
                    return $dimTimeRepo;
                }
                if ($entityClass === Project::class) {
                    return $projectRepo;
                }

                return null;
            });

        // Expect initial info log
        $this->logger->expects($this->atLeastOnce())->method('info');

        $this->service->recalculateMetricsForYear($year);
    }

    public function testRecalculateMetricsForYearCalculatesMonthlyQuarterlyAndYearly(): void
    {
        $year = 2025;

        // Mock delete query
        $deleteQuery = $this->createMock(Query::class);
        $deleteQuery->method('getResult')->willReturn([]);
        $this->entityManager->method('createQuery')->willReturn($deleteQuery);

        // Mock repositories
        $dimTimeRepo = $this->createMock(EntityRepository::class);
        $dimTimeRepo->method('findOneBy')->willReturn(new DimTime());

        $projectRepo  = $this->createMock(ProjectRepository::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query        = $this->createMock(Query::class);

        $projectRepo->method('createQueryBuilder')->willReturn($queryBuilder);
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);
        $query->method('getResult')->willReturn([]);

        $this->entityManager
            ->method('getRepository')
            ->willReturnCallback(function ($entityClass) use ($dimTimeRepo, $projectRepo) {
                if ($entityClass === DimTime::class) {
                    return $dimTimeRepo;
                }
                if ($entityClass === Project::class) {
                    return $projectRepo;
                }

                return null;
            });

        // Expect flush to be called:
        // 12 months + 4 quarters + 1 year = 17 times
        $this->entityManager->expects($this->exactly(17))->method('flush');

        $this->service->recalculateMetricsForYear($year);
    }
}
