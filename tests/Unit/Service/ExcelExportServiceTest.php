<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\ExcelExportService;
use DateTime;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelExportServiceTest extends TestCase
{
    private ExcelExportService $service;

    protected function setUp(): void
    {
        $this->service = new ExcelExportService();
    }

    public function testExportDashboardReturnsStreamedResponse(): void
    {
        $kpis = [
            'revenue' => [
                'total_revenue' => 100000.00,
                'total_cost'    => 60000.00,
                'total_margin'  => 40000.00,
                'margin_rate'   => 40.00,
            ],
            'projects' => [
                'total'     => 10,
                'active'    => 5,
                'completed' => 5,
            ],
        ];

        $monthlyEvolution = [
            [
                'month'   => 'Janvier 2025',
                'revenue' => 10000.00,
                'costs'   => 6000.00,
                'margin'  => 4000.00,
            ],
            [
                'month'   => 'Février 2025',
                'revenue' => 12000.00,
                'costs'   => 7000.00,
                'margin'  => 5000.00,
            ],
        ];

        $startDate = new DateTime('2025-01-01');
        $endDate   = new DateTime('2025-02-28');
        $filters   = [];

        $response = $this->service->exportDashboard($kpis, $monthlyEvolution, $startDate, $endDate, $filters);

        $this->assertInstanceOf(StreamedResponse::class, $response);
    }

    public function testExportDashboardSetsCorrectHeaders(): void
    {
        $kpis = [
            'revenue' => [
                'total_revenue' => 50000.00,
                'total_cost'    => 30000.00,
                'total_margin'  => 20000.00,
                'margin_rate'   => 40.00,
            ],
        ];

        $monthlyEvolution = [];
        $startDate        = new DateTime('2025-01-01');
        $endDate          = new DateTime('2025-12-31');

        $response = $this->service->exportDashboard($kpis, $monthlyEvolution, $startDate, $endDate);

        $headers = $response->headers;

        $this->assertEquals(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $headers->get('Content-Type'),
        );

        $this->assertStringContainsString('attachment', $headers->get('Content-Disposition'));
        $this->assertStringContainsString('dashboard_analytics', $headers->get('Content-Disposition'));
        $this->assertStringContainsString('max-age=0', $headers->get('Cache-Control'));
    }

    public function testExportDashboardGeneratesCorrectFilename(): void
    {
        $kpis             = ['revenue' => ['total_revenue' => 10000.00]];
        $monthlyEvolution = [];
        $startDate        = new DateTime('2025-03-15');
        $endDate          = new DateTime('2025-06-20');

        $response = $this->service->exportDashboard($kpis, $monthlyEvolution, $startDate, $endDate);

        $contentDisposition = $response->headers->get('Content-Disposition');

        $this->assertStringContainsString('dashboard_analytics_2025-03-15_2025-06-20.xlsx', $contentDisposition);
    }

    public function testExportDashboardWithAllOptionalData(): void
    {
        $kpis = [
            'revenue' => [
                'total_revenue' => 100000.00,
                'total_cost'    => 60000.00,
            ],
            'projectsByType' => [
                'forfait' => 5,
                'regie'   => 3,
            ],
            'projectsByCategory' => [
                'development' => 4,
                'design'      => 4,
            ],
            'topContributors' => [
                ['name' => 'John Doe', 'hours' => 160],
                ['name' => 'Jane Smith', 'hours' => 140],
            ],
        ];

        $monthlyEvolution = [
            ['month' => 'Jan', 'revenue' => 10000.00],
        ];

        $startDate = new DateTime('2025-01-01');
        $endDate   = new DateTime('2025-12-31');

        $response = $this->service->exportDashboard($kpis, $monthlyEvolution, $startDate, $endDate);

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertEquals(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('Content-Type'),
        );
    }

    public function testExportDashboardWithFilters(): void
    {
        $kpis             = ['revenue' => ['total_revenue' => 75000.00]];
        $monthlyEvolution = [];
        $startDate        = new DateTime('2025-01-01');
        $endDate          = new DateTime('2025-03-31');
        $filters          = [
            'projectType' => 'forfait',
            'technology'  => 'symfony',
        ];

        $response = $this->service->exportDashboard($kpis, $monthlyEvolution, $startDate, $endDate, $filters);

        $this->assertInstanceOf(StreamedResponse::class, $response);
    }
}
