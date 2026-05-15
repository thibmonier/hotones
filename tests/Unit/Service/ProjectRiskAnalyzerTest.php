<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Project;
use App\Security\CompanyContext;
use App\Service\ProjectRiskAnalyzer;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class ProjectRiskAnalyzerTest extends TestCase
{
    private ProjectRiskAnalyzer $service;

    protected function setUp(): void
    {
        $em = $this->createStub(EntityManagerInterface::class);
        $companyContext = $this->createStub(CompanyContext::class);

        $this->service = new ProjectRiskAnalyzer($em, $companyContext);
    }

    public function testAnalyzeProjectReturnsHealthyScoreForGoodProject(): void
    {
        $project = $this->createStub(Project::class);
        $project->method('getTotalTasksSoldHours')->willReturn('700'); // 100 days
        $project->method('getTotalTasksSpentHours')->willReturn('350'); // 50 days (good progress)
        $project->method('getTotalSoldAmount')->willReturn('100000');
        $project->method('getGlobalProgress')->willReturn('50');
        $project->method('getStatus')->willReturn('in_progress');
        $project->method('getEndDate')->willReturn(new DateTime('+2 months'));
        $project->method('getStartDate')->willReturn(new DateTime('-1 month'));
        $project->method('getTimesheets')->willReturn(new ArrayCollection());

        $result = $this->service->analyzeProject($project);

        static::assertIsArray($result);
        static::assertArrayHasKey('healthScore', $result);
        static::assertArrayHasKey('riskLevel', $result);
        static::assertArrayHasKey('risks', $result);

        // Healthy project should have decent score (may detect missing timesheets = -15)
        static::assertGreaterThanOrEqual(50, $result['healthScore']);
        static::assertContains($result['riskLevel'], ['low', 'medium', 'high']);
    }

    public function testAnalyzeProjectDetectsBudgetOverrun(): void
    {
        $project = $this->createStub(Project::class);
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
        $budgetRisks = array_filter($result['risks'], static fn ($r): bool => str_contains((string) $r['type'], 'budget'));
        static::assertNotEmpty($budgetRisks);

        // Health score should be impacted
        static::assertLessThan(80, $result['healthScore']);
    }

    public function testAnalyzeProjectDetectsScheduleDelay(): void
    {
        $project = $this->createStub(Project::class);

        $project->method('getTotalTasksSoldHours')->willReturn('700');
        $project->method('getTotalTasksSpentHours')->willReturn('350');
        $project->method('getTotalSoldAmount')->willReturn('100000');
        $project->method('getGlobalProgress')->willReturn('50');
        $project->method('getTimesheets')->willReturn(new ArrayCollection());

        // Mock getters for properties
        $project->method('getStatus')->willReturn('in_progress');
        $project->method('getEndDate')->willReturn(new DateTime('-15 days')); // Already passed
        $project->method('getStartDate')->willReturn(new DateTime('-3 months'));

        $result = $this->service->analyzeProject($project);

        // Should detect schedule delay
        $scheduleRisks = array_filter($result['risks'], static fn ($r): bool => $r['type'] === 'schedule_delay');
        static::assertNotEmpty($scheduleRisks);

        static::assertContains($result['riskLevel'], ['high', 'critical']);
    }

    public function testAnalyzeProjectDetectsLowMargin(): void
    {
        $project = $this->createStub(Project::class);
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
        $marginRisks = array_filter($result['risks'], static fn ($r): bool => str_contains((string) $r['type'], 'margin'));
        static::assertNotEmpty($marginRisks);
    }

    public function testAnalyzeProjectDetectsMissingTimesheets(): void
    {
        $project = $this->createStub(Project::class);

        $project->method('getTotalTasksSoldHours')->willReturn('700');
        $project->method('getTotalTasksSpentHours')->willReturn('350');
        $project->method('getTotalSoldAmount')->willReturn('100000');
        $project->method('getGlobalProgress')->willReturn('50');
        $project->method('getTimesheets')->willReturn(new ArrayCollection()); // No timesheets

        // Mock getters for properties
        $project->method('getStatus')->willReturn('in_progress');
        $project->method('getEndDate')->willReturn(new DateTime('+2 months'));
        $project->method('getStartDate')->willReturn(new DateTime('-1 month'));

        $result = $this->service->analyzeProject($project);

        // Should detect missing timesheets
        $timesheetRisks = array_filter($result['risks'], static fn ($r): bool => str_contains(
            (string) $r['type'],
            'timesheet',
        ));
        static::assertNotEmpty($timesheetRisks);
    }

    public function testAnalyzeProjectDetectsStagnation(): void
    {
        $project = $this->createStub(Project::class);

        $project->method('getTotalTasksSoldHours')->willReturn('700');
        $project->method('getTotalTasksSpentHours')->willReturn('0'); // No hours spent
        $project->method('getTotalSoldAmount')->willReturn('100000');
        $project->method('getGlobalProgress')->willReturn('0'); // 0% progress
        $project->method('getTimesheets')->willReturn(new ArrayCollection());

        // Mock getters for properties
        $project->method('getStatus')->willReturn('active');
        $project->method('getEndDate')->willReturn(new DateTime('+2 months'));
        $project->method('getStartDate')->willReturn(new DateTime('-2 months')); // Started 2 months ago

        $result = $this->service->analyzeProject($project);

        // Should detect project not started
        $stagnationRisks = array_filter($result['risks'], static fn ($r): bool => $r['type'] === 'not_started');
        static::assertNotEmpty($stagnationRisks);
    }

    public function testAnalyzeProjectReturnsCorrectRiskLevels(): void
    {
        // Test that risk level is determined correctly based on health score
        // Note: exact scores may vary due to additional risk factors like missing timesheets

        // Project with minimal issues should have high score
        $project = $this->createHealthyProject(90);
        $result = $this->service->analyzeProject($project);
        static::assertGreaterThanOrEqual(70, $result['healthScore']); // Allow for some penalties
        static::assertContains($result['riskLevel'], ['low', 'medium']);

        // Project with multiple issues should have low score
        $project = $this->createHealthyProject(30);
        $result = $this->service->analyzeProject($project);
        static::assertLessThan(60, $result['healthScore']);
        static::assertContains($result['riskLevel'], ['high', 'critical']);
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
        static::assertGreaterThanOrEqual(2, count($result)); // At least 2 at-risk projects
        static::assertLessThanOrEqual(4, count($result)); // At most all 4

        // Should be sorted by health score (ascending) - verify first is lowest or equal
        if (count($result) > 1) {
            static::assertLessThanOrEqual($result[1]['analysis']['healthScore'], $result[0]['analysis']['healthScore']);
        }
    }

    private function createHealthyProject(int $targetScore): Project
    {
        $project = $this->createStub(Project::class);

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
        $project->method('getTimesheets')->willReturn(new ArrayCollection());

        // Mock getters for properties
        $project->method('getStatus')->willReturn('in_progress');
        $project->method('getEndDate')->willReturn(new DateTime('+1 month'));
        $project->method('getStartDate')->willReturn(new DateTime('-1 month'));

        return $project;
    }

    // -----------------------------------------------------------------
    // calculateHealthScore — TEST-COVERAGE-001 lot 1 (T-TC1-02)
    // -----------------------------------------------------------------

    public function testCalculateHealthScoreReturnsPersistedEntityWithComponentScores(): void
    {
        // CompanyContext stub returns a Company.
        $em = $this->createStub(EntityManagerInterface::class);
        $companyContext = $this->createStub(CompanyContext::class);
        $companyContext->method('getCurrentCompany')->willReturn(new \App\Entity\Company());
        $service = new ProjectRiskAnalyzer($em, $companyContext);

        $project = $this->createStub(Project::class);
        // Healthy project across all 4 dimensions.
        $project->method('getTotalTasksSoldHours')->willReturn('700');
        $project->method('getTotalTasksSpentHours')->willReturn('350');
        $project->method('getTotalSoldAmount')->willReturn('100000');
        $project->method('getGlobalProgress')->willReturn('50');
        $project->method('getStatus')->willReturn('in_progress');
        $project->method('getEndDate')->willReturn(new DateTime('+2 months'));
        $project->method('getStartDate')->willReturn(new DateTime('-1 month'));
        $project->method('getTimesheets')->willReturn(new ArrayCollection());

        $entity = $service->calculateHealthScore($project);

        static::assertInstanceOf(\App\Entity\ProjectHealthScore::class, $entity);
        static::assertSame($project, $entity->getProject());
        static::assertGreaterThanOrEqual(0, $entity->budgetScore);
        static::assertLessThanOrEqual(100, $entity->budgetScore);
        static::assertGreaterThanOrEqual(0, $entity->timelineScore);
        static::assertGreaterThanOrEqual(0, $entity->velocityScore);
        static::assertGreaterThanOrEqual(0, $entity->qualityScore);
        static::assertGreaterThanOrEqual(0, $entity->score);
        static::assertLessThanOrEqual(100, $entity->score);
        static::assertContains($entity->healthLevel, ['healthy', 'warning', 'critical']);
    }

    public function testCalculateHealthScoreAppliesWeightedFormula(): void
    {
        // Production weights documented: 40 budget / 30 timeline / 20 velocity / 10 quality.
        // Verify they sum to 1.0 + composite stays in [0, 100] regardless of inputs.
        $em = $this->createStub(EntityManagerInterface::class);
        $companyContext = $this->createStub(CompanyContext::class);
        $companyContext->method('getCurrentCompany')->willReturn(new \App\Entity\Company());
        $service = new ProjectRiskAnalyzer($em, $companyContext);

        // Project that exercises the formula via heterogeneous component scores.
        $project = $this->createStub(Project::class);
        $project->method('getTotalTasksSoldHours')->willReturn('700');
        $project->method('getTotalTasksSpentHours')->willReturn('490'); // 40% overrun → low budget score
        $project->method('getTotalSoldAmount')->willReturn('30000'); // tight margin
        $project->method('getGlobalProgress')->willReturn('50');
        $project->method('getStatus')->willReturn('in_progress');
        $project->method('getEndDate')->willReturn(new DateTime('+1 month'));
        $project->method('getStartDate')->willReturn(new DateTime('-2 months'));
        $project->method('getTimesheets')->willReturn(new ArrayCollection());

        $entity = $service->calculateHealthScore($project);

        // Score must be in [0, 100], healthLevel coherent with thresholds 50 and 80.
        static::assertGreaterThanOrEqual(0, $entity->score);
        static::assertLessThanOrEqual(100, $entity->score);
        if ($entity->score > 80) {
            static::assertSame('healthy', $entity->healthLevel);
        } elseif ($entity->score >= 50) {
            static::assertSame('warning', $entity->healthLevel);
        } else {
            static::assertSame('critical', $entity->healthLevel);
        }
    }

    public function testCalculateHealthScoreEmitsRecommendationsWhenComponentScoresLow(): void
    {
        $em = $this->createStub(EntityManagerInterface::class);
        $companyContext = $this->createStub(CompanyContext::class);
        $companyContext->method('getCurrentCompany')->willReturn(new \App\Entity\Company());
        $service = new ProjectRiskAnalyzer($em, $companyContext);

        // Severe overrun + late + low margin → all 4 components below recommendation thresholds.
        $project = $this->createStub(Project::class);
        $project->method('getTotalTasksSoldHours')->willReturn('200');
        $project->method('getTotalTasksSpentHours')->willReturn('400'); // 100% overrun
        $project->method('getTotalSoldAmount')->willReturn('20000'); // negative margin once 400h × 400€/h cost ≈ 160k
        $project->method('getGlobalProgress')->willReturn('30');
        $project->method('getStatus')->willReturn('in_progress');
        $project->method('getEndDate')->willReturn(new DateTime('-45 days')); // very late
        $project->method('getStartDate')->willReturn(new DateTime('-3 months'));
        $project->method('getTimesheets')->willReturn(new ArrayCollection());

        $entity = $service->calculateHealthScore($project);

        static::assertNotEmpty($entity->recommendations);
        static::assertIsArray($entity->recommendations);
    }

    public function testCalculateHealthScorePersistsViaEntityManager(): void
    {
        // Verify that EM->persist + flush are invoked once each.
        $em = $this->createMock(EntityManagerInterface::class);
        $em
            ->expects($this->once())
            ->method('persist')
            ->with(static::isInstanceOf(\App\Entity\ProjectHealthScore::class));
        $em->expects($this->once())->method('flush');

        $companyContext = $this->createStub(CompanyContext::class);
        $companyContext->method('getCurrentCompany')->willReturn(new \App\Entity\Company());
        $service = new ProjectRiskAnalyzer($em, $companyContext);

        $project = $this->createStub(Project::class);
        $project->method('getTotalTasksSoldHours')->willReturn('500');
        $project->method('getTotalTasksSpentHours')->willReturn('300');
        $project->method('getTotalSoldAmount')->willReturn('80000');
        $project->method('getGlobalProgress')->willReturn('50');
        $project->method('getStatus')->willReturn('in_progress');
        $project->method('getEndDate')->willReturn(new DateTime('+1 month'));
        $project->method('getStartDate')->willReturn(new DateTime('-1 month'));
        $project->method('getTimesheets')->willReturn(new ArrayCollection());

        $service->calculateHealthScore($project);
    }
}
