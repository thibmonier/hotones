<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Project\Persistence\Doctrine;

use App\Domain\Project\Repository\PortfolioMarginReadModelRepositoryInterface;
use App\Domain\Project\Service\PortfolioMarginRecord;
use App\Entity\Company;
use App\Infrastructure\Project\Persistence\Doctrine\CachingPortfolioMarginReadModelRepository;
use App\Security\CompanyContext;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class CachingPortfolioMarginReadModelRepositoryTest extends TestCase
{
    public function testDelegatesToInnerOnCacheMiss(): void
    {
        $inner = $this->createInnerSpy([$this->makeRecord()]);
        $cache = new ArrayAdapter();
        $repository = new CachingPortfolioMarginReadModelRepository(
            inner: $inner,
            kpiCache: $cache,
            companyContext: $this->companyContextWithId(42),
        );

        $records = $repository->findActiveProjectsWithSnapshot(new DateTimeImmutable('2026-05-15'));

        static::assertCount(1, $records);
        static::assertSame(1, $inner->callCount);
    }

    public function testReturnsCachedResultOnHit(): void
    {
        $inner = $this->createInnerSpy([$this->makeRecord()]);
        $cache = new ArrayAdapter();
        $repository = new CachingPortfolioMarginReadModelRepository(
            $inner,
            $cache,
            $this->companyContextWithId(42),
        );

        $now = new DateTimeImmutable('2026-05-15');
        $repository->findActiveProjectsWithSnapshot($now);
        $repository->findActiveProjectsWithSnapshot($now);
        $repository->findActiveProjectsWithSnapshot($now);

        static::assertSame(1, $inner->callCount, 'inner called once thanks to cache');
    }

    public function testCacheKeyDifferentiatesCompany(): void
    {
        $inner = $this->createInnerSpy([$this->makeRecord()]);
        $cache = new ArrayAdapter();

        $repoA = new CachingPortfolioMarginReadModelRepository($inner, $cache, $this->companyContextWithId(1));
        $repoB = new CachingPortfolioMarginReadModelRepository($inner, $cache, $this->companyContextWithId(2));

        $now = new DateTimeImmutable('2026-05-15');
        $repoA->findActiveProjectsWithSnapshot($now);
        $repoB->findActiveProjectsWithSnapshot($now);

        static::assertSame(2, $inner->callCount, 'multi-tenant isolation');
    }

    public function testCacheKeyDifferentiatesDay(): void
    {
        $inner = $this->createInnerSpy([$this->makeRecord()]);
        $cache = new ArrayAdapter();
        $repository = new CachingPortfolioMarginReadModelRepository(
            $inner,
            $cache,
            $this->companyContextWithId(42),
        );

        $repository->findActiveProjectsWithSnapshot(new DateTimeImmutable('2026-05-15'));
        $repository->findActiveProjectsWithSnapshot(new DateTimeImmutable('2026-05-16'));

        static::assertSame(2, $inner->callCount, 'rebuild quotidien filet sécurité');
    }

    /**
     * @param list<PortfolioMarginRecord> $records
     */
    private function createInnerSpy(array $records): object
    {
        return new class($records) implements PortfolioMarginReadModelRepositoryInterface {
            public int $callCount = 0;

            /** @param list<PortfolioMarginRecord> $records */
            public function __construct(private readonly array $records)
            {
            }

            public function findActiveProjectsWithSnapshot(DateTimeImmutable $now): array
            {
                ++$this->callCount;

                return $this->records;
            }
        };
    }

    private function makeRecord(): PortfolioMarginRecord
    {
        return new PortfolioMarginRecord(
            projectId: 1,
            projectName: 'Test Project',
            coutTotalCents: 80_000_00,
            factureTotalCents: 100_000_00,
            margeCalculatedAt: new DateTimeImmutable('2026-05-15 10:00:00'),
        );
    }

    private function companyContextWithId(int $companyId): CompanyContext
    {
        $company = self::createStub(Company::class);
        $company->method('getId')->willReturn($companyId);

        $context = self::createStub(CompanyContext::class);
        $context->method('getCurrentCompany')->willReturn($company);

        return $context;
    }
}
