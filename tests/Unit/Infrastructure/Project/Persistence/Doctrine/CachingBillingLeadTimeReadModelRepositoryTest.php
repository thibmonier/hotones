<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Project\Persistence\Doctrine;

use App\Domain\Project\Repository\BillingLeadTimeReadModelRepositoryInterface;
use App\Domain\Project\Service\QuoteInvoiceRecord;
use App\Entity\Company;
use App\Infrastructure\Project\Persistence\Doctrine\CachingBillingLeadTimeReadModelRepository;
use App\Security\CompanyContext;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class CachingBillingLeadTimeReadModelRepositoryTest extends TestCase
{
    public function testDelegatesToInnerOnCacheMiss(): void
    {
        $inner = $this->createInnerSpy([$this->makeRecord(daysAgoEmitted: 5, leadTimeDays: 10)]);
        $cache = new ArrayAdapter();
        $repository = new CachingBillingLeadTimeReadModelRepository(
            inner: $inner,
            kpiCache: $cache,
            companyContext: $this->companyContextWithId(42),
        );

        $records = $repository->findEmittedInRollingWindow(30, new DateTimeImmutable('2026-05-12'));

        static::assertCount(1, $records);
        static::assertSame(1, $inner->callCount);
    }

    public function testReturnsCachedResultOnHit(): void
    {
        $inner = $this->createInnerSpy([$this->makeRecord(5, 10)]);
        $cache = new ArrayAdapter();
        $repository = new CachingBillingLeadTimeReadModelRepository(
            $inner,
            $cache,
            $this->companyContextWithId(42),
        );

        $now = new DateTimeImmutable('2026-05-12');
        $repository->findEmittedInRollingWindow(30, $now);
        $repository->findEmittedInRollingWindow(30, $now);
        $repository->findEmittedInRollingWindow(30, $now);

        static::assertSame(1, $inner->callCount, 'inner called once thanks to cache');
    }

    public function testCacheKeyDifferentiatesWindowDays(): void
    {
        $inner = $this->createInnerSpy([$this->makeRecord(5, 10)]);
        $cache = new ArrayAdapter();
        $repository = new CachingBillingLeadTimeReadModelRepository(
            $inner,
            $cache,
            $this->companyContextWithId(42),
        );

        $now = new DateTimeImmutable('2026-05-12');
        $repository->findEmittedInRollingWindow(30, $now);
        $repository->findEmittedInRollingWindow(90, $now);
        $repository->findEmittedInRollingWindow(365, $now);

        static::assertSame(3, $inner->callCount);
    }

    public function testCacheKeyDifferentiatesCompany(): void
    {
        $inner = $this->createInnerSpy([$this->makeRecord(5, 10)]);
        $cache = new ArrayAdapter();

        $repoA = new CachingBillingLeadTimeReadModelRepository($inner, $cache, $this->companyContextWithId(1));
        $repoB = new CachingBillingLeadTimeReadModelRepository($inner, $cache, $this->companyContextWithId(2));

        $now = new DateTimeImmutable('2026-05-12');
        $repoA->findEmittedInRollingWindow(30, $now);
        $repoB->findEmittedInRollingWindow(30, $now);

        static::assertSame(2, $inner->callCount, 'multi-tenant isolation');
    }

    public function testCacheKeyDifferentiatesDay(): void
    {
        $inner = $this->createInnerSpy([$this->makeRecord(5, 10)]);
        $cache = new ArrayAdapter();
        $repository = new CachingBillingLeadTimeReadModelRepository(
            $inner,
            $cache,
            $this->companyContextWithId(42),
        );

        $repository->findEmittedInRollingWindow(30, new DateTimeImmutable('2026-05-12'));
        $repository->findEmittedInRollingWindow(30, new DateTimeImmutable('2026-05-13'));

        static::assertSame(2, $inner->callCount, 'rolling window rebuilds at day boundary');
    }

    /**
     * @param list<QuoteInvoiceRecord> $records
     */
    private function createInnerSpy(array $records): object
    {
        return new class($records) implements BillingLeadTimeReadModelRepositoryInterface {
            public int $callCount = 0;

            /** @param list<QuoteInvoiceRecord> $records */
            public function __construct(private readonly array $records)
            {
            }

            public function findEmittedInRollingWindow(int $windowDays, DateTimeImmutable $now): array
            {
                ++$this->callCount;

                return $this->records;
            }
        };
    }

    private function makeRecord(int $daysAgoEmitted, int $leadTimeDays): QuoteInvoiceRecord
    {
        $now = new DateTimeImmutable('2026-05-12');
        $emittedAt = $now->modify('-'.$daysAgoEmitted.' days');
        $signedAt = $emittedAt->modify('-'.$leadTimeDays.' days');

        return new QuoteInvoiceRecord(
            signedAt: $signedAt,
            emittedAt: $emittedAt,
            clientId: 1,
            clientName: 'Test Client',
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
