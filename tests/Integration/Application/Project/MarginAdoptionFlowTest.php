<?php

declare(strict_types=1);

namespace App\Tests\Integration\Application\Project;

use App\Application\Project\Alerting\CheckMarginAdoptionRedThresholdHandler;
use App\Application\Project\Query\MarginAdoptionKpi\ComputeMarginAdoptionKpiHandler;
use App\Domain\Project\Repository\MarginAdoptionReadModelRepositoryInterface;
use App\Factory\ProjectFactory;
use App\Service\Alerting\AlertSeverity;
use App\Service\Alerting\SlackAlertingInterface;
use App\Tests\Support\MultiTenantTestTrait;
use DateTimeImmutable;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * End-to-end integration test for the margin adoption flow.
 *
 * Covers US-112 T-112-05 :
 *   1. {@see App\Infrastructure\Project\Persistence\Doctrine\DoctrineMarginAdoptionReadModelRepository} (T-112-02)
 *   2. {@see App\Domain\Project\Service\MarginAdoptionCalculator} (T-112-01)
 *   3. {@see ComputeMarginAdoptionKpiHandler} (T-112-03)
 *   4. {@see CheckMarginAdoptionRedThresholdHandler} (T-112-04) avec persistance state
 */
final class MarginAdoptionFlowTest extends KernelTestCase
{
    use Factories;
    use MultiTenantTestTrait;
    use ResetDatabase;

    private CacheItemPoolInterface $kpiCache;
    private ComputeMarginAdoptionKpiHandler $computeKpi;
    private MarginAdoptionReadModelRepositoryInterface $repository;
    private DateTimeImmutable $today;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->setUpMultiTenant();

        $this->kpiCache = static::getContainer()->get('cache.kpi');
        $this->computeKpi = static::getContainer()->get(ComputeMarginAdoptionKpiHandler::class);
        $this->repository = static::getContainer()->get(MarginAdoptionReadModelRepositoryInterface::class);
        $this->today = new DateTimeImmutable('2026-05-12T09:00:00+00:00');

        $this->kpiCache->clear();
    }

    public function testEndToEndComputeKpiFromRealDatabase(): void
    {
        $this->createProject('Fresh1', daysAgo: 2);
        $this->createProject('Fresh2', daysAgo: 5);
        $this->createProject('Warning', daysAgo: 15);
        $this->createProject('Critical', daysAgo: 60);
        $this->createProject('Never', daysAgo: null);

        $kpi = ($this->computeKpi)($this->today);

        self::assertSame(5, $kpi->stats->totalActive);
        self::assertSame(2, $kpi->stats->freshCount);
        self::assertSame(1, $kpi->stats->staleWarningCount);
        self::assertSame(2, $kpi->stats->staleCriticalCount);
        self::assertEqualsWithDelta(40.0, $kpi->stats->freshPercent, 0.5);
        self::assertTrue($kpi->warningTriggered, 'freshPercent 40 % < seuil warning 60 %');
    }

    public function testSlackAlertFiredAfter7ConsecutiveRedDays(): void
    {
        // 1 stale project → freshPercent = 0 (red)
        $this->createProject('Stale', daysAgo: 60);

        $slackSpy = new MarginAdoptionSlackAlertingSpy();
        $checker = $this->buildChecker($slackSpy);

        // Days 1 to 6 : red but no alert yet
        for ($i = 6; $i >= 1; --$i) {
            $checker($this->today->modify(sprintf('-%d days', $i)));
        }
        self::assertSame(0, $slackSpy->callCount, 'no alert before day 7');

        // Day 7 : alert fires
        $checker($this->today);

        self::assertSame(1, $slackSpy->callCount);
        self::assertStringContainsString('7 jours', $slackSpy->lastTitle);
        self::assertSame(AlertSeverity::CRITICAL, $slackSpy->lastSeverity);
    }

    public function testGreenDayResetsStreak(): void
    {
        $slackSpy = new MarginAdoptionSlackAlertingSpy();
        $checker = $this->buildChecker($slackSpy);

        $this->createProject('Stale', daysAgo: 60);

        // 5 red days
        for ($i = 11; $i >= 7; --$i) {
            $checker($this->today->modify(sprintf('-%d days', $i)));
        }

        // Convert project to fresh — re-fetch to apply changes
        $this->makeAllProjectsFresh();

        // 1 green day → streak reset
        $checker($this->today->modify('-6 days'));

        // Switch back to all-stale
        $this->makeAllProjectsStale();

        // 5 more red days → streak only 5, no alert (would need 7 consec)
        for ($i = 5; $i >= 1; --$i) {
            $checker($this->today->modify(sprintf('-%d days', $i)));
        }

        self::assertSame(0, $slackSpy->callCount, 'green day reset streak prevents alert');
    }

    public function testNoAlertWhenNoActiveProjects(): void
    {
        $slackSpy = new MarginAdoptionSlackAlertingSpy();
        $checker = $this->buildChecker($slackSpy);

        for ($i = 10; $i >= 1; --$i) {
            $checker($this->today->modify(sprintf('-%d days', $i)));
        }

        self::assertSame(0, $slackSpy->callCount);
    }

    public function testRepositoryFiltersActiveOnly(): void
    {
        $this->createProject('Active', daysAgo: 5, status: 'active');
        ProjectFactory::createOne([
            'company' => $this->getTestCompany(),
            'name' => 'Completed',
            'status' => 'completed',
            'margeCalculatedAt' => $this->today->modify('-5 days'),
        ]);
        ProjectFactory::createOne([
            'company' => $this->getTestCompany(),
            'name' => 'Cancelled',
            'status' => 'cancelled',
            'margeCalculatedAt' => $this->today->modify('-5 days'),
        ]);

        $records = $this->repository->findActiveWithMarginSnapshot();

        self::assertCount(1, $records);
        self::assertSame('Active', $records[0]->projectName);
    }

    private function buildChecker(SlackAlertingInterface $slack): CheckMarginAdoptionRedThresholdHandler
    {
        $companyContext = static::getContainer()->get(\App\Security\CompanyContext::class);

        return new CheckMarginAdoptionRedThresholdHandler(
            computeMarginAdoptionKpi: $this->computeKpi,
            companyContext: $companyContext,
            kpiCache: $this->kpiCache,
            slackAlertingService: $slack,
            logger: new NullLogger(),
        );
    }

    private function createProject(string $name, ?int $daysAgo, string $status = 'active'): void
    {
        $marginAt = $daysAgo === null
            ? null
            : $this->today->modify(sprintf('-%d days', $daysAgo));

        ProjectFactory::createOne([
            'company' => $this->getTestCompany(),
            'name' => $name,
            'status' => $status,
            'margeCalculatedAt' => $marginAt,
        ]);
    }

    private function makeAllProjectsFresh(): void
    {
        $em = $this->getEntityManager();
        $projects = $em->getRepository(\App\Entity\Project::class)->findAll();
        foreach ($projects as $project) {
            $project->margeCalculatedAt = $this->today;
        }
        $em->flush();
    }

    private function makeAllProjectsStale(): void
    {
        $em = $this->getEntityManager();
        $projects = $em->getRepository(\App\Entity\Project::class)->findAll();
        foreach ($projects as $project) {
            $project->margeCalculatedAt = $this->today->modify('-60 days');
        }
        $em->flush();
    }
}

/**
 * @internal slack alerting test double for E2E assertions
 */
final class MarginAdoptionSlackAlertingSpy implements SlackAlertingInterface
{
    public int $callCount = 0;
    public string $lastTitle = '';
    public string $lastBody = '';
    public AlertSeverity $lastSeverity = AlertSeverity::INFO;

    public function sendAlert(string $title, string $body, AlertSeverity $severity = AlertSeverity::INFO): bool
    {
        ++$this->callCount;
        $this->lastTitle = $title;
        $this->lastBody = $body;
        $this->lastSeverity = $severity;

        return true;
    }
}
