<?php

declare(strict_types=1);

namespace App\Tests\Unit\MessageHandler;

use App\Message\CheckAlertsMessage;
use App\MessageHandler\CheckAlertsMessageHandler;
use App\Service\AlertDetectionService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit test for CheckAlertsMessageHandler (TEST-007, sprint-004).
 *
 * Thin wrapper around AlertDetectionService::checkAllAlerts():
 * the test verifies the handler logs the start + the per-bucket counts
 * and the aggregated total, and that it forwards the call without
 * mutating the result.
 */
final class CheckAlertsMessageHandlerTest extends TestCase
{
    private AlertDetectionService&MockObject $service;
    private LoggerInterface&MockObject $logger;
    private CheckAlertsMessageHandler $handler;

    protected function setUp(): void
    {
        $this->service = $this->createMock(AlertDetectionService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->handler = new CheckAlertsMessageHandler($this->service, $this->logger);
    }

    public function testHandlerLogsStartAndAggregatedStats(): void
    {
        $stats = [
            'budget_alerts' => 3,
            'margin_alerts' => 1,
            'overload_alerts' => 5,
            'payment_alerts' => 2,
        ];

        $this->service
            ->expects(self::once())
            ->method('checkAllAlerts')
            ->willReturn($stats);

        $logCalls = [];
        $this->logger
            ->expects(self::exactly(2))
            ->method('info')
            ->willReturnCallback(static function (string $message, array $context = []) use (&$logCalls): void {
                $logCalls[] = ['message' => $message, 'context' => $context];
            });

        ($this->handler)(new CheckAlertsMessage());

        self::assertCount(2, $logCalls);
        self::assertSame('Starting alert check...', $logCalls[0]['message']);
        self::assertSame([], $logCalls[0]['context']);

        self::assertSame('Alert check completed', $logCalls[1]['message']);
        self::assertSame(3, $logCalls[1]['context']['budget_alerts']);
        self::assertSame(1, $logCalls[1]['context']['margin_alerts']);
        self::assertSame(5, $logCalls[1]['context']['overload_alerts']);
        self::assertSame(2, $logCalls[1]['context']['payment_alerts']);
        self::assertSame(11, $logCalls[1]['context']['total_alerts']);
    }

    public function testHandlerHandlesZeroAlertsCleanly(): void
    {
        $this->service
            ->method('checkAllAlerts')
            ->willReturn([
                'budget_alerts' => 0,
                'margin_alerts' => 0,
                'overload_alerts' => 0,
                'payment_alerts' => 0,
            ]);

        $captured = null;
        $this->logger
            ->method('info')
            ->willReturnCallback(static function (string $message, array $context = []) use (&$captured): void {
                if ($message === 'Alert check completed') {
                    $captured = $context;
                }
            });

        ($this->handler)(new CheckAlertsMessage());

        self::assertNotNull($captured);
        self::assertSame(0, $captured['total_alerts']);
    }
}
