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
        $project = $this->createStub(Project::class);
        $project->method('getGlobalProgress')->willReturn('25');

        $result = $this->service->predictProfitability($project);

        static::assertFalse($result['canPredict']);
        static::assertSame(25.0, $result['currentProgress']);
        static::assertStringContainsString('progression < 30%', $result['message']);
    }

    public function testPredictProfitabilityReturnsFalseWhenInsufficientData(): void
    {
        $project = $this->createStub(Project::class);
        $project->method('getGlobalProgress')->willReturn('50');
        $project->method('getTotalSoldAmount')->willReturn('0'); // Missing CA
        $project->method('getTotalTasksSoldHours')->willReturn('100');
        $project->method('getTotalTasksSpentHours')->willReturn('50');

        $result = $this->service->predictProfitability($project);

        static::assertFalse($result['canPredict']);
        static::assertArrayHasKey('message', $result);
    }

    public function testPredictProfitabilityReturnsValidPredictionWhenSufficientData(): void
    {
        $project = $this->createStub(Project::class);
        $project->method('getGlobalProgress')->willReturn('50');
        $project->method('getTotalSoldAmount')->willReturn('100000');
        $project->method('getTotalTasksSoldHours')->willReturn('700'); // 100 days * 7h
        $project->method('getTotalTasksSpentHours')->willReturn('350'); // 50 days * 7h (half)

        $result = $this->service->predictProfitability($project);

        // Assertions
        static::assertTrue($result['canPredict']);
        static::assertSame(50.0, $result['currentProgress']);
        static::assertArrayHasKey('currentMargin', $result);
        static::assertArrayHasKey('predictedMargin', $result);
        static::assertArrayHasKey('budgetDrift', $result);
        static::assertArrayHasKey('recommendations', $result);
        static::assertArrayHasKey('scenarios', $result);

        // Check predicted margin structure (actual keys returned by service)
        static::assertArrayHasKey('projected', $result['predictedMargin']);
        static::assertArrayHasKey('budgeted', $result['predictedMargin']);
        static::assertArrayHasKey('difference', $result['predictedMargin']);

        // Check scenarios structure (realistic, optimistic, pessimistic are in scenarios, not predictedMargin)
        static::assertArrayHasKey('realistic', $result['scenarios']);
        static::assertArrayHasKey('optimistic', $result['scenarios']);
        static::assertArrayHasKey('pessimistic', $result['scenarios']);

        // Check budget drift structure (actual keys returned by service)
        static::assertArrayHasKey('overrunPercentage', $result['budgetDrift']);
        static::assertArrayHasKey('severity', $result['budgetDrift']);
    }

    public function testPredictProfitabilityDetectsBudgetOverrun(): void
    {
        $project = $this->createStub(Project::class);
        $project->method('getGlobalProgress')->willReturn('40');
        $project->method('getTotalSoldAmount')->willReturn('50000');
        $project->method('getTotalTasksSoldHours')->willReturn('350'); // 50 days * 7h
        $project->method('getTotalTasksSpentHours')->willReturn('280'); // 40 days * 7h (80% of budget for 40% progress = overrun)

        $result = $this->service->predictProfitability($project);

        static::assertTrue($result['canPredict']);

        // Budget drift should be detected (using actual key 'overrunPercentage')
        static::assertGreaterThan(0, $result['budgetDrift']['overrunPercentage']);
        static::assertContains($result['budgetDrift']['severity'], ['medium', 'high', 'critical']);

        // Recommendations should be provided
        static::assertNotEmpty($result['recommendations']);
    }

    public function testPredictProfitabilityGeneratesScenarios(): void
    {
        $project = $this->createStub(Project::class);
        $project->method('getGlobalProgress')->willReturn('60');
        $project->method('getTotalSoldAmount')->willReturn('100000');
        $project->method('getTotalTasksSoldHours')->willReturn('700');
        $project->method('getTotalTasksSpentHours')->willReturn('420'); // 60 days * 7h

        $result = $this->service->predictProfitability($project);

        static::assertTrue($result['canPredict']);
        static::assertArrayHasKey('scenarios', $result);
        static::assertNotEmpty($result['scenarios']);

        // Check each scenario has required keys (actual structure from service)
        static::assertArrayHasKey('realistic', $result['scenarios']);
        static::assertArrayHasKey('optimistic', $result['scenarios']);
        static::assertArrayHasKey('pessimistic', $result['scenarios']);

        // Check that each scenario has the expected structure
        foreach ($result['scenarios'] as $scenario) {
            static::assertArrayHasKey('label', $scenario);
            static::assertArrayHasKey('totalHours', $scenario);
            static::assertArrayHasKey('margin', $scenario);
            static::assertArrayHasKey('totalCost', $scenario);
        }
    }

    public function testPredictProfitabilityGeneratesRecommendationsForLowMargin(): void
    {
        $project = $this->createStub(Project::class);
        $project->method('getGlobalProgress')->willReturn('50');
        $project->method('getTotalSoldAmount')->willReturn('30000'); // Low revenue
        $project->method('getTotalTasksSoldHours')->willReturn('350'); // 50 days
        $project->method('getTotalTasksSpentHours')->willReturn('350'); // Already at 100% budget usage = negative margin

        $result = $this->service->predictProfitability($project);

        static::assertTrue($result['canPredict']);

        // Should have negative or very low predicted margin (use 'projected' key, not 'realistic')
        static::assertLessThan(10, $result['predictedMargin']['projected']);

        // Should have recommendations
        static::assertNotEmpty($result['recommendations']);

        // Check recommendations contain actionable items
        $recommendationTexts = array_column($result['recommendations'], 'message');
        static::assertNotEmpty($recommendationTexts);
    }

    public function testPredictProfitabilityOptimisticScenarioIsBetterThanPessimistic(): void
    {
        $project = $this->createStub(Project::class);
        $project->method('getGlobalProgress')->willReturn('40');
        $project->method('getTotalSoldAmount')->willReturn('80000');
        $project->method('getTotalTasksSoldHours')->willReturn('560');
        $project->method('getTotalTasksSpentHours')->willReturn('224');

        $result = $this->service->predictProfitability($project);

        static::assertTrue($result['canPredict']);

        // Optimistic margin should be higher than realistic (scenarios are in 'scenarios', not 'predictedMargin')
        static::assertGreaterThan(
            $result['scenarios']['realistic']['margin'],
            $result['scenarios']['optimistic']['margin'],
        );

        // Pessimistic margin should be lower than realistic (scenarios are in 'scenarios', not 'predictedMargin')
        static::assertLessThan(
            $result['scenarios']['realistic']['margin'],
            $result['scenarios']['pessimistic']['margin'],
        );
    }
}
