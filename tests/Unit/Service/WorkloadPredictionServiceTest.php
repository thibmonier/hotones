<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Repository\OrderRepository;
use App\Service\WorkloadPredictionService;
use PHPUnit\Framework\TestCase;

/**
 * Basic unit tests for WorkloadPredictionService.
 * Note: Full integration tests should be created in tests/Integration/.
 */
class WorkloadPredictionServiceTest extends TestCase
{
    public function testAnalyzePipelineReturnsCorrectStructure(): void
    {
        $orderRepository        = $this->createMock(OrderRepository::class);
        $contributorRepository  = $this->createMock(\App\Repository\ContributorRepository::class);

        $orderRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(
                ['status' => 'a_signer'],
                ['createdAt' => 'DESC'],
            )
            ->willReturn([]);

        $service = new WorkloadPredictionService($orderRepository, $contributorRepository);
        $result  = $service->analyzePipeline();

        // Check structure
        $this->assertIsArray($result);
        $this->assertArrayHasKey('pipeline', $result);
        $this->assertArrayHasKey('workloadByMonth', $result);
        $this->assertArrayHasKey('alerts', $result);
        $this->assertArrayHasKey('totalPotentialDays', $result);
    }

    public function testAnalyzePipelineAcceptsFilters(): void
    {
        $orderRepository       = $this->createMock(OrderRepository::class);
        $contributorRepository = $this->createMock(\App\Repository\ContributorRepository::class);

        $orderRepository
            ->expects($this->once())
            ->method('findBy')
            ->willReturn([]);

        $service = new WorkloadPredictionService($orderRepository, $contributorRepository);

        // Should accept profile and contributor filters without error
        $result = $service->analyzePipeline([1, 2], [5, 6]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('pipeline', $result);
    }
}
