<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Project\EventListener;

use App\Application\Project\EventListener\SendPortfolioMarginRedAlertOnRecalculated;
use App\Application\Project\Query\PortfolioMarginKpi\ComputePortfolioMarginKpiHandler;
use App\Domain\Project\Event\ProjectMarginRecalculatedEvent;
use App\Domain\Project\Repository\PortfolioMarginReadModelRepositoryInterface;
use App\Domain\Project\Service\PortfolioMarginCalculator;
use App\Domain\Project\Service\PortfolioMarginRecord;
use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\Shared\ValueObject\Money;
use App\Service\Alerting\AlertSeverity;
use App\Service\Alerting\SlackAlertingInterface;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class SendPortfolioMarginRedAlertOnRecalculatedTest extends TestCase
{
    public function testSendsSlackAlertWhenMarginBelowRedThreshold(): void
    {
        // marge moyenne pondérée 5 % < 10 % seuil rouge
        $handler = $this->buildHandlerWithMargin(coutPercent: 95);

        $slack = $this->createMock(SlackAlertingInterface::class);
        $slack->expects(self::once())
            ->method('sendAlert')
            ->with(
                static::stringContains('Marge moyenne portefeuille'),
                static::stringContains('5.0 %'),
                AlertSeverity::CRITICAL,
            )
            ->willReturn(true);

        $listener = new SendPortfolioMarginRedAlertOnRecalculated(
            computePortfolioMarginKpi: $handler,
            slackAlertingService: $slack,
            logger: new NullLogger(),
        );

        $listener($this->makeEvent());
    }

    public function testDoesNotAlertWhenMarginAtOrAboveRedThreshold(): void
    {
        // marge moyenne pondérée 25 % > 10 % seuil rouge
        $handler = $this->buildHandlerWithMargin(coutPercent: 75);

        $slack = $this->createMock(SlackAlertingInterface::class);
        $slack->expects(self::never())->method('sendAlert');

        $listener = new SendPortfolioMarginRedAlertOnRecalculated(
            computePortfolioMarginKpi: $handler,
            slackAlertingService: $slack,
            logger: new NullLogger(),
        );

        $listener($this->makeEvent());
    }

    public function testDoesNotAlertWhenNoProjectsWithSnapshot(): void
    {
        $handler = $this->buildHandlerWithRecords([]);

        $slack = $this->createMock(SlackAlertingInterface::class);
        $slack->expects(self::never())->method('sendAlert');

        $listener = new SendPortfolioMarginRedAlertOnRecalculated(
            computePortfolioMarginKpi: $handler,
            slackAlertingService: $slack,
            logger: new NullLogger(),
        );

        $listener($this->makeEvent());
    }

    public function testLogsAlertOutcome(): void
    {
        $handler = $this->buildHandlerWithMargin(coutPercent: 95);

        $slack = self::createStub(SlackAlertingInterface::class);
        $slack->method('sendAlert')->willReturn(true);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('info')
            ->with(
                static::stringContains('Portfolio margin red alert triggered'),
                static::callback(static fn (array $ctx) => isset($ctx['average_percent'], $ctx['threshold_percent'], $ctx['slack_sent'])
                    && $ctx['threshold_percent'] === SendPortfolioMarginRedAlertOnRecalculated::DEFAULT_RED_THRESHOLD_PERCENT
                    && $ctx['slack_sent'] === true),
            );

        $listener = new SendPortfolioMarginRedAlertOnRecalculated($handler, $slack, $logger);
        $listener($this->makeEvent());
    }

    private function buildHandlerWithMargin(int $coutPercent): ComputePortfolioMarginKpiHandler
    {
        return $this->buildHandlerWithRecords([
            new PortfolioMarginRecord(
                projectId: 1,
                projectName: 'Test',
                coutTotalCents: $coutPercent * 1_000_00,
                factureTotalCents: 100_000_00,
                margeCalculatedAt: new DateTimeImmutable('2026-05-15 10:00:00'),
            ),
        ]);
    }

    /**
     * @param list<PortfolioMarginRecord> $records
     */
    private function buildHandlerWithRecords(array $records): ComputePortfolioMarginKpiHandler
    {
        $repo = new class($records) implements PortfolioMarginReadModelRepositoryInterface {
            /** @param list<PortfolioMarginRecord> $records */
            public function __construct(private readonly array $records)
            {
            }

            public function findActiveProjectsWithSnapshot(DateTimeImmutable $now): array
            {
                return $this->records;
            }
        };

        return new ComputePortfolioMarginKpiHandler($repo, new PortfolioMarginCalculator());
    }

    private function makeEvent(): ProjectMarginRecalculatedEvent
    {
        return ProjectMarginRecalculatedEvent::create(
            projectId: ProjectId::fromLegacyInt(42),
            projectName: 'Test Project',
            costTotal: Money::fromCents(95_000_00),
            invoicedPaidTotal: Money::fromCents(100_000_00),
            marginPercent: 5.0,
        );
    }
}
