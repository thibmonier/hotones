<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Project\EventListener;

use App\Application\Project\EventListener\SendBillingLeadTimeRedAlertOnInvoiceCreated;
use App\Application\Project\Query\BillingLeadTimeKpi\ComputeBillingLeadTimeKpiHandler;
use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Company\ValueObject\CompanyId;
use App\Domain\Invoice\Event\InvoiceCreatedEvent;
use App\Domain\Invoice\ValueObject\InvoiceId;
use App\Domain\Invoice\ValueObject\InvoiceNumber;
use App\Domain\Project\Repository\BillingLeadTimeReadModelRepositoryInterface;
use App\Domain\Project\Service\BillingLeadTimeCalculator;
use App\Domain\Project\Service\QuoteInvoiceRecord;
use App\Service\Alerting\AlertSeverity;
use App\Service\Alerting\SlackAlertingInterface;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class SendBillingLeadTimeRedAlertOnInvoiceCreatedTest extends TestCase
{
    public function testSendsSlackAlertWhenMedian30DaysAboveRedThreshold(): void
    {
        $handler = $this->buildHandlerWithMedian(40);

        $slack = $this->createMock(SlackAlertingInterface::class);
        $slack->expects(self::once())
            ->method('sendAlert')
            ->with(
                self::stringContains('Temps de facturation médian 30j'),
                self::stringContains('40.0 j'),
                AlertSeverity::CRITICAL,
            )
            ->willReturn(true);

        $listener = new SendBillingLeadTimeRedAlertOnInvoiceCreated(
            computeBillingLeadTimeKpi: $handler,
            slackAlertingService: $slack,
            logger: new NullLogger(),
        );

        $listener($this->makeEvent());
    }

    public function testDoesNotAlertWhenMedianAtOrBelowRedThreshold(): void
    {
        $handler = $this->buildHandlerWithMedian(20);

        $slack = $this->createMock(SlackAlertingInterface::class);
        $slack->expects(self::never())->method('sendAlert');

        $listener = new SendBillingLeadTimeRedAlertOnInvoiceCreated(
            $handler,
            $slack,
            new NullLogger(),
        );

        $listener($this->makeEvent());
    }

    public function testLogsAlertOutcome(): void
    {
        $handler = $this->buildHandlerWithMedian(35);

        $slack = self::createStub(SlackAlertingInterface::class);
        $slack->method('sendAlert')->willReturn(true);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('info')
            ->with(
                self::stringContains('Billing lead time red alert triggered'),
                self::callback(fn (array $ctx) => isset($ctx['median_30_days'], $ctx['threshold_days'], $ctx['slack_sent'])
                    && $ctx['threshold_days'] === SendBillingLeadTimeRedAlertOnInvoiceCreated::DEFAULT_RED_THRESHOLD_DAYS
                    && $ctx['slack_sent'] === true),
            );

        $listener = new SendBillingLeadTimeRedAlertOnInvoiceCreated($handler, $slack, $logger);
        $listener($this->makeEvent());
    }

    public function testIncludesTopSlowClientsInBody(): void
    {
        $handler = $this->buildHandlerWithRecords([
            $this->record(daysAgoEmitted: 5, leadTimeDays: 40, clientId: 1, clientName: 'Acme'),
            $this->record(daysAgoEmitted: 5, leadTimeDays: 60, clientId: 2, clientName: 'Beta'),
        ]);

        $slack = $this->createMock(SlackAlertingInterface::class);
        $slack->expects(self::once())
            ->method('sendAlert')
            ->with(
                self::anything(),
                self::stringContains('Beta'),
                AlertSeverity::CRITICAL,
            )
            ->willReturn(true);

        $listener = new SendBillingLeadTimeRedAlertOnInvoiceCreated(
            $handler,
            $slack,
            new NullLogger(),
        );

        $listener($this->makeEvent());
    }

    private function buildHandlerWithMedian(int $leadTimeDays): ComputeBillingLeadTimeKpiHandler
    {
        return $this->buildHandlerWithRecords([
            $this->record(daysAgoEmitted: 5, leadTimeDays: $leadTimeDays, clientId: 1, clientName: 'Test'),
        ]);
    }

    /**
     * @param list<QuoteInvoiceRecord> $records
     */
    private function buildHandlerWithRecords(array $records): ComputeBillingLeadTimeKpiHandler
    {
        $repo = new class($records) implements BillingLeadTimeReadModelRepositoryInterface {
            /** @param list<QuoteInvoiceRecord> $records */
            public function __construct(private readonly array $records)
            {
            }

            public function findEmittedInRollingWindow(int $windowDays, DateTimeImmutable $now): array
            {
                return $this->records;
            }
        };

        return new ComputeBillingLeadTimeKpiHandler($repo, new BillingLeadTimeCalculator());
    }

    private function record(int $daysAgoEmitted, int $leadTimeDays, ?int $clientId, ?string $clientName): QuoteInvoiceRecord
    {
        $now = new DateTimeImmutable('2026-05-12');
        $emittedAt = $now->modify('-'.$daysAgoEmitted.' days');
        $signedAt = $emittedAt->modify('-'.$leadTimeDays.' days');

        return new QuoteInvoiceRecord(
            signedAt: $signedAt,
            emittedAt: $emittedAt,
            clientId: $clientId,
            clientName: $clientName,
        );
    }

    private function makeEvent(): InvoiceCreatedEvent
    {
        return new InvoiceCreatedEvent(
            invoiceId: InvoiceId::fromLegacyInt(1),
            invoiceNumber: InvoiceNumber::fromString('F202605001'),
            companyId: CompanyId::fromLegacyInt(1),
            clientId: ClientId::fromLegacyInt(1),
        );
    }
}
