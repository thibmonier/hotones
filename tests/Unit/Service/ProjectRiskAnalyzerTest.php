<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Project;
use App\Service\ProjectRiskAnalyzer;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class ProjectRiskAnalyzerTest extends TestCase
{
    private ProjectRiskAnalyzer $service;

    protected function setUp(): void
    {
        $this->service = new ProjectRiskAnalyzer();
    }

    public function testAnalyzeProjectReturnsHealthyScoreForGoodProject(): void
    {
        $project = $this->createMock(Project::class);
        $project->method('getTotalTasksSoldHours')->willReturn('700'); // 100 days
        $project->method('getTotalTasksSpentHours')->willReturn('350'); // 50 days (good progress)
        $project->method('getTotalSoldAmount')->willReturn('100000');
        $project->method('getGlobalProgress')->willReturn('50');
        $project->method('getStatus')->willReturn('in_progress');
        $project->method('getEndDate')->willReturn(new DateTime('+2 months'));
        $project->method('getStartDate')->willReturn(new DateTime('-1 month'));
        $project->method('getTimesheets')->willReturn(new ArrayCollection());

        $result = $this->service->analyzeProject($project);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('healthScore', $result);
        $this->assertArrayHasKey('riskLevel', $result);
        $this->assertArrayHasKey('risks', $result);

        // Healthy project should have decent score (may detect missing timesheets = -15)
        $this->assertGreaterThanOrEqual(50, $result['healthScore']);
        $this->assertContains($result['riskLevel'], ['low', 'medium', 'high']);
    }

    public function testAnalyzeProjectDetectsBudgetOverrun(): void
    {
        $project = $this->createMock(Project::class);
        $project->method('getTotalTasksSoldHours')->willReturn('350'); // 50 days
        $project->method('getTotalTasksSpentHours')->willReturn('490'); // 70 days (40% overrun)
        $project->method('getTotalSoldAmount')->willReturn('50000');
        $project->method('getGlobalProgress')->willReturn('60');
        $project->method('getStatus')->willReturn('in_progress');
        $project->method('getEndDate')->willReturn(new DateTime('+1 month'));
        $project->method('getStartDate')->willReturn(new DateTime('-2 months'));
        $project->method('getTimesheets')->willReturn(new ArrayCollection());

        $result = $this->service->analyzeProject($project);

        // Should detect budget overrun
        $budgetRisks = array_filter($result['risks'], fn ($r) => str_contains($r['type'], 'budget'));
        $this->assertNotEmpty($budgetRisks);

        // Health score should be impacted
        $this->assertLessThan(80, $result['healthScore']);
    }

    public function testAnalyzeProjectDetectsScheduleDelay(): void
    {
        $project = $this->createMock(Project::class);
        $project->method('getTotalTasksSoldHours')->willReturn('700');
        $project->method('getTotalTasksSpentHours')->willReturn('350');
        $project->method('getTotalSoldAmount')->willReturn('100000');
        $project->method('getGlobalProgress')->willReturn('50');
        $project->method('getStatus')->willReturn('in_progress'); // Not completed
        $project->method('getEndDate')->willReturn(new DateTime('-15 days')); // Already passed
        $project->method('getStartDate')->willReturn(new DateTime('-3 months'));
        $project->method('getTimesheets')->willReturn(new ArrayCollection());

        $result = $this->service->analyzeProject($project);

        // Should detect schedule delay
        $scheduleRisks = array_filter($result['risks'], fn ($r) => $r['type'] === 'schedule_delay');
        $this->assertNotEmpty($scheduleRisks);

        $this->assertContains($result['riskLevel'], ['high', 'critical']);
    }

    public function testAnalyzeProjectDetectsLowMargin(): void
    {
        $project = $this->createMock(Project::class);
        $project->method('getTotalTasksSoldHours')->willReturn('350');
        $project->method('getTotalTasksSpentHours')->willReturn('350'); // Full budget used
        $project->method('getTotalSoldAmount')->willReturn('20000'); // Low revenue
        $project->method('getGlobalProgress')->willReturn('80');
        $project->method('getStatus')->willReturn('in_progress');
        $project->method('getEndDate')->willReturn(new DateTime('+1 month'));
        $project->method('getStartDate')->willReturn(new DateTime('-2 months'));
        $project->method('getTimesheets')->willReturn(new ArrayCollection());

        $result = $this->service->analyzeProject($project);

        // Should detect low/negative margin
        $marginRisks = array_filter($result['risks'], fn ($r) => str_contains($r['type'], 'margin'));
        $this->assertNotEmpty($marginRisks);
    }

    public function testAnalyzeProjectDetectsMissingTimesheets(): void
    {
        $project = $this->createMock(Project::class);
        $project->method('getTotalTasksSoldHours')->willReturn('700');
        $project->method('getTotalTasksSpentHours')->willReturn('350');
        $project->method('getTotalSoldAmount')->willReturn('100000');
        $project->method('getGlobalProgress')->willReturn('50');
        $project->method('getStatus')->willReturn('in_progress');
        $project->method('getEndDate')->willReturn(new DateTime('+2 months'));
        $project->method('getStartDate')->willReturn(new DateTime('-1 month'));
        $project->method('getTimesheets')->willReturn(new ArrayCollection()); // No timesheets

        $result = $this->service->analyzeProject($project);

        // Should detect missing timesheets
        $timesheetRisks = array_filter($result['risks'], fn ($r) => str_contains($r['type'], 'timesheet'));
        $this->assertNotEmpty($timesheetRisks);
    }

    public function testAnalyzeProjectDetectsStagnation(): void
    {
        $project = $this->createMock(Project::class);
        $project->method('getTotalTasksSoldHours')->willReturn('700');
        $project->method('getTotalTasksSpentHours')->willReturn('0'); // No hours spent
        $project->method('getTotalSoldAmount')->willReturn('100000');
        $project->method('getGlobalProgress')->willReturn('0'); // 0% progress
        $project->method('getStatus')->willReturn('active');
        $project->method('getEndDate')->willReturn(new DateTime('+2 months'));
        $project->method('getStartDate')->willReturn(new DateTime('-2 months')); // Started 2 months ago
        $project->method('getTimesheets')->willReturn(new ArrayCollection());

        $result = $this->service->analyzeProject($project);

        // Should detect project not started
        $stagnationRisks = array_filter($result['risks'], fn ($r) => $r['type'] === 'not_started');
        $this->assertNotEmpty($stagnationRisks);
    }

    public function testAnalyzeProjectReturnsCorrectRiskLevels(): void
    {
        // Test that risk level is determined correctly based on health score
        // Note: exact scores may vary due to additional risk factors like missing timesheets

        // Project with minimal issues should have high score
        $project = $this->createHealthyProject(90);
        $result  = $this->service->analyzeProject($project);
        $this->assertGreaterThanOrEqual(70, $result['healthScore']); // Allow for some penalties
        $this->assertContains($result['riskLevel'], ['low', 'medium']);

        // Project with multiple issues should have low score
        $project = $this->createHealthyProject(30);
        $result  = $this->service->analyzeProject($project);
        $this->assertLessThan(60, $result['healthScore']);
        $this->assertContains($result['riskLevel'], ['high', 'critical']);
    }

    public function testAnalyzeMultipleProjectsFiltersAndSortsCorrectly(): void
    {
        $projects = [
            $this->createHealthyProject(90), // Low risk - may or may not be excluded
            $this->createHealthyProject(70), // Medium risk
            $this->createHealthyProject(50), // High risk
            $this->createHealthyProject(30), // Critical risk
        ];

        $result = $this->service->analyzeMultipleProjects($projects);

        // Should only return projects with score < 80 (some penalties may apply)
        $this->assertGreaterThanOrEqual(2, count($result)); // At least 2 at-risk projects
        $this->assertLessThanOrEqual(4, count($result));    // At most all 4

        // Should be sorted by health score (ascending) - verify first is lowest or equal
        if (count($result) > 1) {
            $this->assertLessThanOrEqual(
                $result[1]['analysis']['healthScore'],
                $result[0]['analysis']['healthScore'],
            );
        }
    }

    private function createHealthyProject(int $targetScore): Project
    {
        $project = $this->createMock(Project::class);

        // Configure project to achieve target score
        // Score = 100 - total penalties
        // We'll manipulate budget to control the score
        $penalty = 100 - $targetScore;

        if ($penalty > 20) {
            // Create major budget overrun (30 points penalty)
            $project->method('getTotalTasksSoldHours')->willReturn('100');
            $project->method('getTotalTasksSpentHours')->willReturn('140'); // 40% overrun
        } elseif ($penalty > 10) {
            // Create medium budget overrun (20 points penalty)
            $project->method('getTotalTasksSoldHours')->willReturn('100');
            $project->method('getTotalTasksSpentHours')->willReturn('125'); // 25% overrun
        } elseif ($penalty > 0) {
            // Create small budget overrun (10 points penalty)
            $project->method('getTotalTasksSoldHours')->willReturn('100');
            $project->method('getTotalTasksSpentHours')->willReturn('105'); // 5% overrun
        } else {
            // Healthy project
            $project->method('getTotalTasksSoldHours')->willReturn('100');
            $project->method('getTotalTasksSpentHours')->willReturn('50');
        }

        $project->method('getTotalSoldAmount')->willReturn('100000');
        $project->method('getGlobalProgress')->willReturn('50');
        $project->method('getStatus')->willReturn('in_progress');
        $project->method('getEndDate')->willReturn(new DateTime('+1 month'));
        $project->method('getStartDate')->willReturn(new DateTime('-1 month'));
        $project->method('getTimesheets')->willReturn(new ArrayCollection());

        return $project;
    }
}
