<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Project;
use App\Service\ProfitabilityPredictor;
use PHPUnit\Framework\TestCase;

class ProfitabilityPredictorTest extends TestCase
{
    private ProfitabilityPredictor $service;

    protected function setUp(): void
    {
        $this->service = new ProfitabilityPredictor();
    }

    public function testPredictProfitabilityReturnsFalseWhenProgressLessThan30Percent(): void
    {
        $project = $this->createMock(Project::class);
        $project->method('getGlobalProgress')->willReturn('25');

        $result = $this->service->predictProfitability($project);

        $this->assertFalse($result['canPredict']);
        $this->assertEquals(25.0, $result['currentProgress']);
        $this->assertStringContainsString('progression < 30%', $result['message']);
    }

    public function testPredictProfitabilityReturnsFalseWhenInsufficientData(): void
    {
        $project = $this->createMock(Project::class);
        $project->method('getGlobalProgress')->willReturn('50');
        $project->method('getTotalSoldAmount')->willReturn('0'); // Missing CA
        $project->method('getTotalTasksSoldHours')->willReturn('100');
        $project->method('getTotalTasksSpentHours')->willReturn('50');

        $result = $this->service->predictProfitability($project);

        $this->assertFalse($result['canPredict']);
        $this->assertArrayHasKey('message', $result);
    }

    public function testPredictProfitabilityReturnsValidPredictionWhenSufficientData(): void
    {
        $project = $this->createMock(Project::class);
        $project->method('getGlobalProgress')->willReturn('50');
        $project->method('getTotalSoldAmount')->willReturn('100000');
        $project->method('getTotalTasksSoldHours')->willReturn('700'); // 100 days * 7h
        $project->method('getTotalTasksSpentHours')->willReturn('350'); // 50 days * 7h (half)

        $result = $this->service->predictProfitability($project);

        // Assertions
        $this->assertTrue($result['canPredict']);
        $this->assertEquals(50.0, $result['currentProgress']);
        $this->assertArrayHasKey('currentMargin', $result);
        $this->assertArrayHasKey('predictedMargin', $result);
        $this->assertArrayHasKey('budgetDrift', $result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertArrayHasKey('scenarios', $result);

        // Check predicted margin structure (actual keys returned by service)
        $this->assertArrayHasKey('projected', $result['predictedMargin']);
        $this->assertArrayHasKey('budgeted', $result['predictedMargin']);
        $this->assertArrayHasKey('difference', $result['predictedMargin']);

        // Check scenarios structure (realistic, optimistic, pessimistic are in scenarios, not predictedMargin)
        $this->assertArrayHasKey('realistic', $result['scenarios']);
        $this->assertArrayHasKey('optimistic', $result['scenarios']);
        $this->assertArrayHasKey('pessimistic', $result['scenarios']);

        // Check budget drift structure (actual keys returned by service)
        $this->assertArrayHasKey('overrunPercentage', $result['budgetDrift']);
        $this->assertArrayHasKey('severity', $result['budgetDrift']);
    }

    public function testPredictProfitabilityDetectsBudgetOverrun(): void
    {
        $project = $this->createMock(Project::class);
        $project->method('getGlobalProgress')->willReturn('40');
        $project->method('getTotalSoldAmount')->willReturn('50000');
        $project->method('getTotalTasksSoldHours')->willReturn('350'); // 50 days * 7h
        $project->method('getTotalTasksSpentHours')->willReturn('280'); // 40 days * 7h (80% of budget for 40% progress = overrun)

        $result = $this->service->predictProfitability($project);

        $this->assertTrue($result['canPredict']);

        // Budget drift should be detected (using actual key 'overrunPercentage')
        $this->assertGreaterThan(0, $result['budgetDrift']['overrunPercentage']);
        $this->assertContains($result['budgetDrift']['severity'], ['medium', 'high', 'critical']);

        // Recommendations should be provided
        $this->assertNotEmpty($result['recommendations']);
    }

    public function testPredictProfitabilityGeneratesScenarios(): void
    {
        $project = $this->createMock(Project::class);
        $project->method('getGlobalProgress')->willReturn('60');
        $project->method('getTotalSoldAmount')->willReturn('100000');
        $project->method('getTotalTasksSoldHours')->willReturn('700');
        $project->method('getTotalTasksSpentHours')->willReturn('420'); // 60 days * 7h

        $result = $this->service->predictProfitability($project);

        $this->assertTrue($result['canPredict']);
        $this->assertArrayHasKey('scenarios', $result);
        $this->assertNotEmpty($result['scenarios']);

        // Check each scenario has required keys (actual structure from service)
        $this->assertArrayHasKey('realistic', $result['scenarios']);
        $this->assertArrayHasKey('optimistic', $result['scenarios']);
        $this->assertArrayHasKey('pessimistic', $result['scenarios']);

        // Check that each scenario has the expected structure
        foreach ($result['scenarios'] as $scenario) {
            $this->assertArrayHasKey('label', $scenario);
            $this->assertArrayHasKey('totalHours', $scenario);
            $this->assertArrayHasKey('margin', $scenario);
            $this->assertArrayHasKey('totalCost', $scenario);
        }
    }

    public function testPredictProfitabilityGeneratesRecommendationsForLowMargin(): void
    {
        $project = $this->createMock(Project::class);
        $project->method('getGlobalProgress')->willReturn('50');
        $project->method('getTotalSoldAmount')->willReturn('30000'); // Low revenue
        $project->method('getTotalTasksSoldHours')->willReturn('350'); // 50 days
        $project->method('getTotalTasksSpentHours')->willReturn('350'); // Already at 100% budget usage = negative margin

        $result = $this->service->predictProfitability($project);

        $this->assertTrue($result['canPredict']);

        // Should have negative or very low predicted margin (use 'projected' key, not 'realistic')
        $this->assertLessThan(10, $result['predictedMargin']['projected']);

        // Should have recommendations
        $this->assertNotEmpty($result['recommendations']);

        // Check recommendations contain actionable items
        $recommendationTexts = array_column($result['recommendations'], 'message');
        $this->assertNotEmpty($recommendationTexts);
    }

    public function testPredictProfitabilityOptimisticScenarioIsBetterThanPessimistic(): void
    {
        $project = $this->createMock(Project::class);
        $project->method('getGlobalProgress')->willReturn('40');
        $project->method('getTotalSoldAmount')->willReturn('80000');
        $project->method('getTotalTasksSoldHours')->willReturn('560');
        $project->method('getTotalTasksSpentHours')->willReturn('224');

        $result = $this->service->predictProfitability($project);

        $this->assertTrue($result['canPredict']);

        // Optimistic margin should be higher than realistic (scenarios are in 'scenarios', not 'predictedMargin')
        $this->assertGreaterThan(
            $result['scenarios']['realistic']['margin'],
            $result['scenarios']['optimistic']['margin'],
        );

        // Pessimistic margin should be lower than realistic (scenarios are in 'scenarios', not 'predictedMargin')
        $this->assertLessThan(
            $result['scenarios']['realistic']['margin'],
            $result['scenarios']['pessimistic']['margin'],
        );
    }
}
