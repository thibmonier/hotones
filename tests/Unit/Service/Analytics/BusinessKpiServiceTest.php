<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Analytics;

use App\Entity\Company;
use App\Repository\ProjectRepository;
use App\Security\CompanyContext;
use App\Service\Analytics\BusinessKpiService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;

#[AllowMockObjectsWithoutExpectations]
final class BusinessKpiServiceTest extends TestCase
{
    public function testComputeAllReturnsCachedPayload(): void
    {
        $cachedPayload = [
            'dau' => 10,
            'mau' => 50,
            'projects_per_day' => 1.2,
            'signed_quotes_this_month' => 5,
            'invoices_this_month_count' => 8,
            'invoices_this_month_amount' => 12_500.50,
            'conversion_rate_pct' => 35.0,
            'revenue_trail_30d' => 25_000.0,
            'avg_project_margin' => 3500.0,
        ];

        $cache = $this->createMock(CacheInterface::class);
        $cache->method('get')->willReturn($cachedPayload);

        $service = new BusinessKpiService(
            $this->createMock(EntityManagerInterface::class),
            $this->makeCompanyContext(),
            $cache,
            $this->createMock(ProjectRepository::class),
        );

        $kpis = $service->computeAll();

        static::assertSame($cachedPayload, $kpis);
    }

    public function testComputeAllReturnsAllExpectedKeys(): void
    {
        $cachedPayload = [
            'dau' => 0,
            'mau' => 0,
            'projects_per_day' => 0.0,
            'signed_quotes_this_month' => 0,
            'invoices_this_month_count' => 0,
            'invoices_this_month_amount' => 0.0,
            'conversion_rate_pct' => 0.0,
            'revenue_trail_30d' => 0.0,
            'avg_project_margin' => 0.0,
        ];

        $cache = $this->createMock(CacheInterface::class);
        $cache->method('get')->willReturn($cachedPayload);

        $service = new BusinessKpiService(
            $this->createMock(EntityManagerInterface::class),
            $this->makeCompanyContext(),
            $cache,
            $this->createMock(ProjectRepository::class),
        );

        $kpis = $service->computeAll();

        $expectedKeys = [
            'dau', 'mau', 'projects_per_day', 'signed_quotes_this_month',
            'invoices_this_month_count', 'invoices_this_month_amount',
            'conversion_rate_pct', 'revenue_trail_30d', 'avg_project_margin',
        ];

        foreach ($expectedKeys as $key) {
            static::assertArrayHasKey($key, $kpis, sprintf('KPI key "%s" missing', $key));
        }

        // 9 keys total
        static::assertCount(9, $kpis);
    }

    public function testComputeAllUsesTenantSpecificCacheKey(): void
    {
        $capturedKey = null;
        $cache = $this->createMock(CacheInterface::class);
        $cache->method('get')->willReturnCallback(static function (string $key) use (&$capturedKey): array {
            $capturedKey = $key;

            return [
                'dau' => 0, 'mau' => 0, 'projects_per_day' => 0.0,
                'signed_quotes_this_month' => 0, 'invoices_this_month_count' => 0,
                'invoices_this_month_amount' => 0.0, 'conversion_rate_pct' => 0.0,
                'revenue_trail_30d' => 0.0, 'avg_project_margin' => 0.0,
            ];
        });

        $service = new BusinessKpiService(
            $this->createMock(EntityManagerInterface::class),
            $this->makeCompanyContext(),
            $cache,
            $this->createMock(ProjectRepository::class),
        );

        $service->computeAll();

        static::assertNotNull($capturedKey);
        static::assertStringContainsString('business_kpis_company_', $capturedKey);
    }

    private function makeCompanyContext(): CompanyContext
    {
        $ctx = $this->createMock(CompanyContext::class);
        $ctx->method('getCurrentCompany')->willReturn(new Company());

        return $ctx;
    }
}
