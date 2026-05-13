<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Project\EventListener;

use App\Application\Project\EventListener\SendDsoRedAlertOnInvoicePaid;
use App\Application\Project\Query\DsoKpi\ComputeDsoKpiHandler;
use App\Domain\Invoice\Event\InvoicePaidEvent;
use App\Domain\Invoice\ValueObject\InvoiceId;
use App\Domain\Project\Repository\DsoReadModelRepositoryInterface;
use App\Domain\Project\Service\DsoCalculator;
use App\Domain\Project\Service\InvoicePaymentRecord;
use App\Domain\Shared\ValueObject\Money;
use App\Service\Alerting\AlertSeverity;
use App\Service\Alerting\SlackAlertingInterface;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class SendDsoRedAlertOnInvoicePaidTest extends TestCase
{
    public function testSendsSlackAlertWhenDso30DaysAboveRedThreshold(): void
    {
        $handler = $this->buildHandlerWithDelay(70); // DSO 30j = 70 > seuil 60

        $slack = $this->createMock(SlackAlertingInterface::class);
        $slack->expects(self::once())
            ->method('sendAlert')
            ->with(
                self::stringContains('DSO 30j'),
                self::stringContains('70.0 j'),
                AlertSeverity::CRITICAL,
            )
            ->willReturn(true);

        $listener = new SendDsoRedAlertOnInvoicePaid(
            computeDsoKpi: $handler,
            slackAlertingService: $slack,
            logger: new NullLogger(),
        );

        $listener($this->makeEvent());
    }

    public function testDoesNotAlertWhenDsoAtOrBelowRedThreshold(): void
    {
        $handler = $this->buildHandlerWithDelay(50); // DSO 30j = 50 < seuil 60

        $slack = $this->createMock(SlackAlertingInterface::class);
        $slack->expects(self::never())->method('sendAlert');

        $listener = new SendDsoRedAlertOnInvoicePaid(
            computeDsoKpi: $handler,
            slackAlertingService: $slack,
            logger: new NullLogger(),
        );

        $listener($this->makeEvent());
    }

    public function testLogsAlertOutcome(): void
    {
        $handler = $this->buildHandlerWithDelay(75);

        $slack = self::createStub(SlackAlertingInterface::class);
        $slack->method('sendAlert')->willReturn(true);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('info')
            ->with(
                self::stringContains('DSO red alert triggered'),
                self::callback(fn (array $ctx) => isset($ctx['dso_30_days'], $ctx['threshold_days'], $ctx['slack_sent'])
                    && $ctx['threshold_days'] === SendDsoRedAlertOnInvoicePaid::DEFAULT_RED_THRESHOLD_DAYS
                    && $ctx['slack_sent'] === true),
            );

        $listener = new SendDsoRedAlertOnInvoicePaid($handler, $slack, $logger);
        $listener($this->makeEvent());
    }

    private function buildHandlerWithDelay(int $delayDays): ComputeDsoKpiHandler
    {
        $repo = new class($delayDays) implements DsoReadModelRepositoryInterface {
            public function __construct(private readonly int $delayDays)
            {
            }

            public function findPaidInRollingWindow(int $windowDays, DateTimeImmutable $now): array
            {
                $issuedAt = $now->modify('-90 days');

                return [
                    new InvoicePaymentRecord(
                        issuedAt: $issuedAt,
                        paidAt: $issuedAt->modify('+'.$this->delayDays.' days'),
                        amountPaidCents: 100_000,
                    ),
                ];
            }
        };

        return new ComputeDsoKpiHandler($repo, new DsoCalculator());
    }

    private function makeEvent(): InvoicePaidEvent
    {
        return new InvoicePaidEvent(
            invoiceId: InvoiceId::fromLegacyInt(1),
            amountPaid: Money::fromCents(100_000),
            paidAt: new DateTimeImmutable('2026-05-12'),
        );
    }
}
