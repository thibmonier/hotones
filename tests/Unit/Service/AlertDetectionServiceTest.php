<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Order;
use App\Entity\OrderPaymentSchedule;
use App\Entity\Project;
use App\Event\PaymentDueAlertEvent;
use App\Event\ProjectBudgetAlertEvent;
use App\Repository\ContributorRepository;
use App\Repository\OrderRepository;
use App\Repository\ProjectRepository;
use App\Repository\StaffingMetricsRepository;
use App\Repository\UserRepository;
use App\Service\AlertDetectionService;
use App\Service\ProfitabilityPredictor;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Unit tests for AlertDetectionService threshold/dispatch behaviour
 * (TEST-007, sprint-004 — gap-analysis Critical #4).
 *
 * Each detection branch (budget / margin / payment) is exercised in
 * isolation. Workload alerts are excluded because they rely on a Doctrine
 * QueryBuilder against StaffingMetricsRepository, which would require an
 * integration-test setup; that path is left to a follow-up integration
 * story (sprint-005 candidate).
 */
#[AllowMockObjectsWithoutExpectations]
final class AlertDetectionServiceTest extends TestCase
{
    private ProjectRepository&Stub $projectRepository;
    private OrderRepository&Stub $orderRepository;
    private UserRepository&Stub $userRepository;
    private ContributorRepository&Stub $contributorRepository;
    private StaffingMetricsRepository&Stub $staffingMetricsRepository;
    private ProfitabilityPredictor&Stub $profitabilityPredictor;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private MessageBusInterface&MockObject $messageBus;
    private AlertDetectionService $service;

    protected function setUp(): void
    {
        $this->projectRepository = $this->createStub(ProjectRepository::class);
        $this->orderRepository = $this->createStub(OrderRepository::class);
        $this->userRepository = $this->createStub(UserRepository::class);
        $this->contributorRepository = $this->createStub(ContributorRepository::class);
        $this->staffingMetricsRepository = $this->createStub(StaffingMetricsRepository::class);
        $this->profitabilityPredictor = $this->createStub(ProfitabilityPredictor::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->messageBus->method('dispatch')
            ->willReturnCallback(static fn (object $e): Envelope => new Envelope($e));

        $this->userRepository->method('findByRole')->willReturn([]);
        $this->contributorRepository->method('findActiveContributors')->willReturn([]);

        $this->service = new AlertDetectionService(
            $this->projectRepository,
            $this->orderRepository,
            $this->userRepository,
            $this->contributorRepository,
            $this->staffingMetricsRepository,
            $this->profitabilityPredictor,
            $this->eventDispatcher,
            $this->messageBus,
        );
    }

    public function testBudgetAlertDispatchedWhenConsumedAbove80AndTimeRemainingBelow20(): void
    {
        // consumedPct = 85/100 = 85% (>=80) && timeRemaining = 100-90 = 10% (<20) -> alert
        $project = $this->makeProject(budgetedDays: 100.0, spentHours: 85.0, globalProgress: 90.0);
        $this->projectRepository->method('findBy')->willReturn([$project]);
        $this->orderRepository->method('findBy')->willReturn([]);
        $this->profitabilityPredictor->method('predictProfitability')->willReturn(['canPredict' => false]);

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(static::isInstanceOf(ProjectBudgetAlertEvent::class));

        $stats = $this->service->checkAllAlerts();

        static::assertSame(1, $stats['budget_alerts']);
    }

    public function testBudgetAlertNotDispatchedBelowConsumedThreshold(): void
    {
        $project = $this->makeProject(budgetedDays: 100.0, spentHours: 50.0, globalProgress: 30.0);
        $this->projectRepository->method('findBy')->willReturn([$project]);
        $this->orderRepository->method('findBy')->willReturn([]);
        $this->profitabilityPredictor->method('predictProfitability')->willReturn(['canPredict' => false]);

        $this->eventDispatcher->expects(self::never())->method('dispatch');

        $stats = $this->service->checkAllAlerts();

        static::assertSame(0, $stats['budget_alerts']);
    }

    public function testBudgetAlertNotDispatchedWhenStillEarlyInTheTimeline(): void
    {
        // Consumed reached threshold (85% > 80%), but only 50% project progress means
        // 50% time remaining (>20%), so no alert is fired.
        $project = $this->makeProject(budgetedDays: 100.0, spentHours: 85.0, globalProgress: 50.0);
        $this->projectRepository->method('findBy')->willReturn([$project]);
        $this->orderRepository->method('findBy')->willReturn([]);
        $this->profitabilityPredictor->method('predictProfitability')->willReturn(['canPredict' => false]);

        $this->eventDispatcher->expects(self::never())->method('dispatch');

        $stats = $this->service->checkAllAlerts();
        static::assertSame(0, $stats['budget_alerts']);
    }

    public function testBudgetAlertSkipsProjectsWithoutBudget(): void
    {
        $project = $this->makeProject(budgetedDays: 0.0, spentHours: 100.0, globalProgress: 99.0);
        $this->projectRepository->method('findBy')->willReturn([$project]);
        $this->orderRepository->method('findBy')->willReturn([]);
        $this->profitabilityPredictor->method('predictProfitability')->willReturn(['canPredict' => false]);

        $this->eventDispatcher->expects(self::never())->method('dispatch');

        $stats = $this->service->checkAllAlerts();
        static::assertSame(0, $stats['budget_alerts']);
    }

    public function testMarginAlertDispatchedAtCriticalSeverityViaDomainEvent(): void
    {
        $project = $this->makeProject();
        $this->projectRepository->method('findBy')->willReturn([$project]);
        $this->orderRepository->method('findBy')->willReturn([]);
        $this->profitabilityPredictor
            ->method('predictProfitability')
            ->willReturn([
                'canPredict' => true,
                'predictedMargin' => ['projected' => 5.0],
            ]);

        // Sprint-023 US-106 (AT-3.3 strangler fig completion) : legacy
        // LowMarginAlertEvent supprimé, seul Domain Event dispatched via
        // messageBus.
        $domainEventCaptured = null;
        $this->messageBus
            ->method('dispatch')
            ->willReturnCallback(static function (object $event) use (&$domainEventCaptured): Envelope {
                if ($event instanceof \App\Domain\Project\Event\MarginThresholdExceededEvent) {
                    $domainEventCaptured = $event;
                }

                return new Envelope($event);
            });

        $stats = $this->service->checkAllAlerts();

        static::assertSame(1, $stats['margin_alerts']);
        static::assertNotNull($domainEventCaptured);
        static::assertSame(10.0, $domainEventCaptured->thresholdPercent); // critical = threshold 10
    }

    public function testMarginAlertDispatchedAtWarningSeverityViaDomainEvent(): void
    {
        $project = $this->makeProject();
        $this->projectRepository->method('findBy')->willReturn([$project]);
        $this->orderRepository->method('findBy')->willReturn([]);
        $this->profitabilityPredictor
            ->method('predictProfitability')
            ->willReturn([
                'canPredict' => true,
                'predictedMargin' => ['projected' => 15.0],
            ]);

        $domainEventCaptured = null;
        $this->messageBus
            ->method('dispatch')
            ->willReturnCallback(static function (object $event) use (&$domainEventCaptured): Envelope {
                if ($event instanceof \App\Domain\Project\Event\MarginThresholdExceededEvent) {
                    $domainEventCaptured = $event;
                }

                return new Envelope($event);
            });

        $stats = $this->service->checkAllAlerts();

        static::assertSame(1, $stats['margin_alerts']);
        static::assertNotNull($domainEventCaptured);
        static::assertSame(20.0, $domainEventCaptured->thresholdPercent); // warning = threshold 20
    }

    public function testMarginAlertNoLegacyEventDispatched(): void
    {
        // Sprint-023 US-106 strangler fig completion : legacy
        // LowMarginAlertEvent supprimé. eventDispatcher ne reçoit PLUS
        // d'événement margin (autres alertes Budget/Workload/Payment
        // continuent via eventDispatcher).
        $project = $this->makeProject();
        $this->projectRepository->method('findBy')->willReturn([$project]);
        $this->orderRepository->method('findBy')->willReturn([]);
        $this->profitabilityPredictor
            ->method('predictProfitability')
            ->willReturn([
                'canPredict' => true,
                'predictedMargin' => ['projected' => 5.0],
            ]);

        $domainEventCaptured = null;
        $this->messageBus
            ->expects(self::atLeastOnce())
            ->method('dispatch')
            ->willReturnCallback(static function (object $event) use (&$domainEventCaptured): Envelope {
                if ($event instanceof \App\Domain\Project\Event\MarginThresholdExceededEvent) {
                    $domainEventCaptured = $event;
                }

                return new Envelope($event);
            });

        $stats = $this->service->checkAllAlerts();

        static::assertSame(1, $stats['margin_alerts']);
        static::assertNotNull($domainEventCaptured);
        static::assertSame(5.0, $domainEventCaptured->marginPercent);
        static::assertSame(10.0, $domainEventCaptured->thresholdPercent); // critical
    }

    public function testMarginAlertSkippedAboveWarningThreshold(): void
    {
        $project = $this->makeProject();
        $this->projectRepository->method('findBy')->willReturn([$project]);
        $this->orderRepository->method('findBy')->willReturn([]);
        $this->profitabilityPredictor
            ->method('predictProfitability')
            ->willReturn([
                'canPredict' => true,
                'predictedMargin' => ['projected' => 35.0],
            ]);

        $this->eventDispatcher->expects(self::never())->method('dispatch');
        $this->messageBus->expects(self::never())->method('dispatch');

        $stats = $this->service->checkAllAlerts();
        static::assertSame(0, $stats['margin_alerts']);
    }

    public function testMarginAlertSkippedWhenPredictorCannotPredict(): void
    {
        $project = $this->makeProject();
        $this->projectRepository->method('findBy')->willReturn([$project]);
        $this->orderRepository->method('findBy')->willReturn([]);
        $this->profitabilityPredictor->method('predictProfitability')->willReturn(['canPredict' => false]);

        $this->eventDispatcher->expects(self::never())->method('dispatch');

        $stats = $this->service->checkAllAlerts();
        static::assertSame(0, $stats['margin_alerts']);
    }

    public function testPaymentAlertDispatchedWhenScheduleDueWithinSevenDays(): void
    {
        $this->projectRepository->method('findBy')->willReturn([]);

        $order = $this->createStub(Order::class);
        $schedule = $this->createStub(OrderPaymentSchedule::class);
        $schedule->method('getBillingDate')->willReturn(new DateTimeImmutable('+3 days'));
        $order
            ->method('getPaymentSchedules')
            ->willReturn(new \Doctrine\Common\Collections\ArrayCollection([$schedule]));

        $this->orderRepository->method('findBy')->willReturn([$order]);

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(static::isInstanceOf(PaymentDueAlertEvent::class));

        $stats = $this->service->checkAllAlerts();
        static::assertSame(1, $stats['payment_alerts']);
    }

    public function testPaymentAlertSkippedWhenScheduleFarInFuture(): void
    {
        $this->projectRepository->method('findBy')->willReturn([]);

        $order = $this->createStub(Order::class);
        $schedule = $this->createStub(OrderPaymentSchedule::class);
        $schedule->method('getBillingDate')->willReturn(new DateTimeImmutable('+30 days'));
        $order
            ->method('getPaymentSchedules')
            ->willReturn(new \Doctrine\Common\Collections\ArrayCollection([$schedule]));

        $this->orderRepository->method('findBy')->willReturn([$order]);

        $this->eventDispatcher->expects(self::never())->method('dispatch');

        $stats = $this->service->checkAllAlerts();
        static::assertSame(0, $stats['payment_alerts']);
    }

    private function makeProject(
        float $budgetedDays = 100.0,
        float $spentHours = 0.0,
        float $globalProgress = 0.0,
    ): Project&Stub {
        $project = $this->createStub(Project::class);
        $project->method('calculateBudgetedDays')->willReturn($budgetedDays);
        $project->method('getTotalTasksSpentHours')->willReturn((string) $spentHours);
        $project->method('getGlobalProgress')->willReturn((string) $globalProgress);
        $project->method('getProjectManager')->willReturn(null);
        $project->method('getKeyAccountManager')->willReturn(null);
        $project->method('getId')->willReturn(42);
        $project->method('getName')->willReturn('Test Project');
        $project->method('getTotalSoldAmount')->willReturn('10000.00');

        return $project;
    }
}
