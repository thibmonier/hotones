<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Project\Persistence\Doctrine;

use App\Domain\Project\Repository\DsoReadModelRepositoryInterface;
use App\Domain\Project\Service\InvoicePaymentRecord;
use App\Entity\Company;
use App\Infrastructure\Project\Persistence\Doctrine\CachingDsoReadModelRepository;
use App\Security\CompanyContext;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class CachingDsoReadModelRepositoryTest extends TestCase
{
    public function testDelegatesToInnerOnCacheMiss(): void
    {
        $inner = $this->createInnerSpy([$this->makeRecord(5, 10_000)]);
        $cache = new ArrayAdapter();
        $repository = new CachingDsoReadModelRepository(
            inner: $inner,
            kpiCache: $cache,
            companyContext: $this->companyContextWithId(42),
        );

        $records = $repository->findPaidInRollingWindow(30, new DateTimeImmutable('2026-05-12'));

        static::assertCount(1, $records);
        static::assertSame(1, $inner->callCount);
    }

    public function testReturnsCachedResultOnHit(): void
    {
        $inner = $this->createInnerSpy([$this->makeRecord(5, 10_000)]);
        $cache = new ArrayAdapter();
        $repository = new CachingDsoReadModelRepository(
            inner: $inner,
            kpiCache: $cache,
            companyContext: $this->companyContextWithId(42),
        );

        $now = new DateTimeImmutable('2026-05-12');
        $repository->findPaidInRollingWindow(30, $now);
        $repository->findPaidInRollingWindow(30, $now);
        $repository->findPaidInRollingWindow(30, $now);

        static::assertSame(1, $inner->callCount, 'inner should be called once thanks to cache');
    }

    public function testCacheKeyDifferentiatesWindowDays(): void
    {
        $inner = $this->createInnerSpy([$this->makeRecord(5, 10_000)]);
        $cache = new ArrayAdapter();
        $repository = new CachingDsoReadModelRepository(
            inner: $inner,
            kpiCache: $cache,
            companyContext: $this->companyContextWithId(42),
        );

        $now = new DateTimeImmutable('2026-05-12');
        $repository->findPaidInRollingWindow(30, $now);
        $repository->findPaidInRollingWindow(90, $now);
        $repository->findPaidInRollingWindow(365, $now);

        static::assertSame(3, $inner->callCount, 'each window size triggers its own cache entry');
    }

    public function testCacheKeyDifferentiatesCompany(): void
    {
        $inner = $this->createInnerSpy([$this->makeRecord(5, 10_000)]);
        $cache = new ArrayAdapter();

        $repositoryA = new CachingDsoReadModelRepository($inner, $cache, $this->companyContextWithId(1));
        $repositoryB = new CachingDsoReadModelRepository($inner, $cache, $this->companyContextWithId(2));

        $now = new DateTimeImmutable('2026-05-12');
        $repositoryA->findPaidInRollingWindow(30, $now);
        $repositoryB->findPaidInRollingWindow(30, $now);

        static::assertSame(2, $inner->callCount, 'each tenant has its own cache entry (multi-tenant isolation)');
    }

    public function testCacheKeyDifferentiatesDay(): void
    {
        $inner = $this->createInnerSpy([$this->makeRecord(5, 10_000)]);
        $cache = new ArrayAdapter();
        $repository = new CachingDsoReadModelRepository(
            inner: $inner,
            kpiCache: $cache,
            companyContext: $this->companyContextWithId(42),
        );

        $repository->findPaidInRollingWindow(30, new DateTimeImmutable('2026-05-12'));
        $repository->findPaidInRollingWindow(30, new DateTimeImmutable('2026-05-13'));

        static::assertSame(2, $inner->callCount, 'rolling window rebuilds at day boundary');
    }

    /**
     * Returns an anonymous-class spy implementing the interface.
     */
    private function createInnerSpy(array $records): object
    {
        return new class($records) implements DsoReadModelRepositoryInterface {
            public int $callCount = 0;

            public function __construct(private readonly array $records)
            {
            }

            public function findPaidInRollingWindow(int $windowDays, DateTimeImmutable $now): array
            {
                ++$this->callCount;

                return $this->records;
            }
        };
    }

    private function makeRecord(int $delayDays, int $amountCents): InvoicePaymentRecord
    {
        $issuedAt = new DateTimeImmutable('2026-05-01');

        return new InvoicePaymentRecord(
            issuedAt: $issuedAt,
            paidAt: $issuedAt->modify('+'.$delayDays.' days'),
            amountPaidCents: $amountCents,
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
