<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Project\EventListener;

use App\Application\Project\EventListener\SendMarginAlertOnThresholdExceeded;
use App\Domain\Project\Event\MarginThresholdExceededEvent;
use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\Shared\ValueObject\Money;
use App\Service\Alerting\AlertSeverity;
use App\Service\Alerting\SlackAlertingInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class SendMarginAlertOnThresholdExceededTest extends TestCase
{
    public function testSendsCriticalAlertWhenMarginBelowHalfThreshold(): void
    {
        $slack = $this->createMock(SlackAlertingInterface::class);
        $slack->expects(self::once())
            ->method('sendAlert')
            ->with(
                self::stringContains('Marge projet sous seuil'),
                self::stringContains('Project Critical'),
                AlertSeverity::CRITICAL,
            )
            ->willReturn(true);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('info');

        $listener = new SendMarginAlertOnThresholdExceeded($slack, $logger);

        $event = MarginThresholdExceededEvent::create(
            projectId: ProjectId::generate(),
            projectName: 'Project Critical',
            costTotal: Money::fromAmount(10000.00),
            invoicedPaidTotal: Money::fromAmount(10300.00),
            marginPercent: 3.0,
            thresholdPercent: 10.0,
        );

        $listener($event);
    }

    public function testSendsWarningAlertWhenMarginAboveHalfThreshold(): void
    {
        $slack = $this->createMock(SlackAlertingInterface::class);
        $slack->expects(self::once())
            ->method('sendAlert')
            ->with(
                self::anything(),
                self::anything(),
                AlertSeverity::WARNING,
            )
            ->willReturn(true);

        $logger = $this->createMock(LoggerInterface::class);

        $listener = new SendMarginAlertOnThresholdExceeded($slack, $logger);

        $event = MarginThresholdExceededEvent::create(
            projectId: ProjectId::generate(),
            projectName: 'Project Warning',
            costTotal: Money::fromAmount(10000.00),
            invoicedPaidTotal: Money::fromAmount(10800.00),
            marginPercent: 8.0,
            thresholdPercent: 10.0,
        );

        $listener($event);
    }

    public function testHandlesSlackFailureGracefully(): void
    {
        $slack = $this->createMock(SlackAlertingInterface::class);
        $slack->method('sendAlert')->willReturn(false);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('info')
            ->with(
                self::stringContains('MarginThresholdExceeded'),
                self::callback(fn (array $context): bool => ($context['slack_sent'] ?? null) === false),
            );

        $listener = new SendMarginAlertOnThresholdExceeded($slack, $logger);

        $event = MarginThresholdExceededEvent::create(
            projectId: ProjectId::generate(),
            projectName: 'Project',
            costTotal: Money::fromAmount(1000.00),
            invoicedPaidTotal: Money::fromAmount(1080.00),
            marginPercent: 8.0,
            thresholdPercent: 10.0,
        );

        $listener($event);
    }
}
