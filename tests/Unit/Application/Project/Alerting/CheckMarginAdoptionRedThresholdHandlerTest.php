<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Project\Alerting;

use App\Application\Project\Alerting\CheckMarginAdoptionRedThresholdHandler;
use App\Application\Project\Query\MarginAdoptionKpi\ComputeMarginAdoptionKpiHandler;
use App\Domain\Project\Repository\MarginAdoptionReadModelRepositoryInterface;
use App\Domain\Project\Service\MarginAdoptionCalculator;
use App\Domain\Project\Service\ProjectMarginSnapshotRecord;
use App\Entity\Company;
use App\Security\CompanyContext;
use App\Service\Alerting\AlertSeverity;
use App\Service\Alerting\SlackAlertingInterface;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class CheckMarginAdoptionRedThresholdHandlerTest extends TestCase
{
    public function testSkipsAlertWhenNoActiveProjects(): void
    {
        $slack = $this->createMock(SlackAlertingInterface::class);
        $slack->expects(self::never())->method('sendAlert');

        $handler = $this->buildHandler(records: [], slack: $slack);

        $handler(new DateTimeImmutable('2026-05-12'));
    }

    public function testDoesNotAlertWhenFreshPercentAboveRedThreshold(): void
    {
        $records = [
            $this->record(1, 50), // 50 % fresh > 40 %
            $this->record(2, 1),
        ];

        $slack = $this->createMock(SlackAlertingInterface::class);
        $slack->expects(self::never())->method('sendAlert');

        $handler = $this->buildHandler($records, $slack);
        $handler(new DateTimeImmutable('2026-05-12'));
    }

    public function testDoesNotAlertWhenStreakBelow7Days(): void
    {
        // freshPercent = 0 → red
        $records = [$this->record(1, daysAgo: 60)];

        $slack = $this->createMock(SlackAlertingInterface::class);
        $slack->expects(self::never())->method('sendAlert');

        $cache = new ArrayAdapter();
        $handler = $this->buildHandler($records, $slack, cache: $cache);

        // 6 consecutive days red → no alert yet
        for ($i = 6; $i >= 1; --$i) {
            $handler((new DateTimeImmutable('2026-05-12'))->modify(sprintf('-%d days', $i)));
        }
    }

    public function testFiresAlertWhenStreakReaches7Days(): void
    {
        $records = [$this->record(1, daysAgo: 60)];

        $slack = $this->createMock(SlackAlertingInterface::class);
        $slack->expects(self::once())
            ->method('sendAlert')
            ->with(
                static::stringContains('Adoption marge sous seuil rouge depuis 7 jours'),
                static::stringContains('0.0 %'),
                AlertSeverity::CRITICAL,
            )
            ->willReturn(true);

        $cache = new ArrayAdapter();
        $handler = $this->buildHandler($records, $slack, cache: $cache);

        // 7 consecutive days red → alert fires day 7
        for ($i = 6; $i >= 0; --$i) {
            $handler((new DateTimeImmutable('2026-05-12'))->modify(sprintf('-%d days', $i)));
        }
    }

    public function testGreenDayResetsStreak(): void
    {
        $cache = new ArrayAdapter();
        $slack = $this->createMock(SlackAlertingInterface::class);
        $slack->expects(self::never())->method('sendAlert');

        // 5 red days → green → 2 red days = streak should be 2, not 7
        $redRecords = [$this->record(1, daysAgo: 60)];
        $greenRecords = [$this->record(1, daysAgo: 2)]; // fresh

        $redHandler = $this->buildHandler($redRecords, $slack, cache: $cache);
        $greenHandler = $this->buildHandler($greenRecords, $slack, cache: $cache);

        for ($i = 11; $i >= 7; --$i) {
            $redHandler((new DateTimeImmutable('2026-05-12'))->modify(sprintf('-%d days', $i)));
        }
        $greenHandler((new DateTimeImmutable('2026-05-12'))->modify('-6 days'));
        for ($i = 5; $i >= 4; --$i) {
            $redHandler((new DateTimeImmutable('2026-05-12'))->modify(sprintf('-%d days', $i)));
        }
    }

    /**
     * @param list<ProjectMarginSnapshotRecord> $records
     */
    private function buildHandler(
        array $records,
        SlackAlertingInterface $slack,
        ?ArrayAdapter $cache = null,
    ): CheckMarginAdoptionRedThresholdHandler {
        $cache ??= new ArrayAdapter();
        $repo = new class($records) implements MarginAdoptionReadModelRepositoryInterface {
            /** @param list<ProjectMarginSnapshotRecord> $records */
            public function __construct(private readonly array $records)
            {
            }

            public function findActiveWithMarginSnapshot(): array
            {
                return $this->records;
            }
        };

        $kpiHandler = new ComputeMarginAdoptionKpiHandler($repo, new MarginAdoptionCalculator());

        return new CheckMarginAdoptionRedThresholdHandler(
            computeMarginAdoptionKpi: $kpiHandler,
            companyContext: $this->companyContextWithId(42),
            kpiCache: $cache,
            slackAlertingService: $slack,
            logger: new NullLogger(),
        );
    }

    private function record(int $id, int $daysAgo): ProjectMarginSnapshotRecord
    {
        $now = new DateTimeImmutable('2026-05-12');

        return new ProjectMarginSnapshotRecord(
            projectId: $id,
            projectName: "Project{$id}",
            marginCalculatedAt: $now->modify(sprintf('-%d days', $daysAgo)),
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
