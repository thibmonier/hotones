<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\ForecastingService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Basic unit tests for ForecastingService.
 * Note: Full integration tests should be created in tests/Integration/.
 */
class ForecastingServiceTest extends TestCase
{
    public function testForecastRevenueThrowsExceptionForInvalidHorizon(): void
    {
        $projectRepository = $this->createMock(\App\Repository\ProjectRepository::class);
        $service           = new ForecastingService($projectRepository);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Horizon must be 3, 6, or 12 months');

        $service->forecastRevenue(15);
    }

    public function testForecastRevenueAcceptsValidHorizons(): void
    {
        $projectRepository = $this->createMock(\App\Repository\ProjectRepository::class);
        $service           = new ForecastingService($projectRepository);

        // Should not throw exception for valid horizons
        $validHorizons = [3, 6, 12];

        foreach ($validHorizons as $horizon) {
            try {
                // This will fail on insufficient data, but that's expected
                // We just want to verify the horizon validation passes
                $service->forecastRevenue($horizon);
            } catch (RuntimeException $e) {
                // Expected when no data - horizon validation passed
                $this->assertStringContainsString('Insufficient historical data', $e->getMessage());
            }
        }

        $this->assertTrue(true); // If we get here, validation works
    }
}
