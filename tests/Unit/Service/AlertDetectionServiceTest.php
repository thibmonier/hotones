<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Order;
use App\Entity\OrderPaymentSchedule;
use App\Entity\Project;
use App\Event\LowMarginAlertEvent;
use App\Event\PaymentDueAlertEvent;
use App\Event\ProjectBudgetAlertEvent;
use App\Repository\ContributorRepository;
use App\Repository\OrderRepository;
use App\Repository\ProjectRepository;
use App\Repository\StaffingMetricsRepository;
use App\Repository\UserRepository;
use App\Service\AlertDetectionService;
use App\Service\ProfitabilityPredictor;
use App\Service\Workload\WorkloadCalculatorInterface;
use App\Entity\Contributor;
use App\Event\ContributorOverloadAlertEvent;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Unit tests for AlertDetectionService threshold/dispatch behaviour.
 *
 * - Sprint-004 / TEST-007 covered budget / margin / payment branches.
 *   Workload was deferred because it built a Doctrine QueryBuilder inline.
 * - Sprint-005 / TEST-WORKLOAD-001 extracts that QueryBuilder into
 *   `WorkloadCalculatorInterface` and adds the workload-alert tests below.
 */
final class AlertDetectionServiceTest extends TestCase
{
    private ProjectRepository&MockObject $projectRepository;
    private OrderRepository&MockObject $orderRepository;
    private UserRepository&MockObject $userRepository;
    private ContributorRepository&MockObject $contributorRepository;
    private StaffingMetricsRepository&MockObject $staffingMetricsRepository;
    private ProfitabilityPredictor&MockObject $profitabilityPredictor;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private WorkloadCalculatorInterface&MockObject $workloadCalculator;
    private AlertDetectionService $service;

    protected function setUp(): void
    {
        $this->projectRepository = $this->createMock(ProjectRepository::class);
        $this->orderRepository = $this->createMock(OrderRepository::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->contributorRepository = $this->createMock(ContributorRepository::class);
        $this->staffingMetricsRepository = $this->createMock(StaffingMetricsRepository::class);
        $this->profitabilityPredictor = $this->createMock(ProfitabilityPredictor::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->workloadCalculator = $this->createMock(WorkloadCalculatorInterface::class);

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
            $this->workloadCalculator,
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
            ->with(self::isInstanceOf(ProjectBudgetAlertEvent::class));

        $stats = $this->service->checkAllAlerts();

        self::assertSame(1, $stats['budget_alerts']);
    }

    public function testBudgetAlertNotDispatchedBelowConsumedThreshold(): void
    {
        $project = $this->makeProject(budgetedDays: 100.0, spentHours: 50.0, globalProgress: 30.0);
        $this->projectRepository->method('findBy')->willReturn([$project]);
        $this->orderRepository->method('findBy')->willReturn([]);
        $this->profitabilityPredictor->method('predictProfitability')->willReturn(['canPredict' => false]);

        $this->eventDispatcher
            ->expects(self::never())
            ->method('dispatch');

        $stats = $this->service->checkAllAlerts();

        self::assertSame(0, $stats['budget_alerts']);
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
        self::assertSame(0, $stats['budget_alerts']);
    }

    public function testBudgetAlertSkipsProjectsWithoutBudget(): void
    {
        $project = $this->makeProject(budgetedDays: 0.0, spentHours: 100.0, globalProgress: 99.0);
        $this->projectRepository->method('findBy')->willReturn([$project]);
        $this->orderRepository->method('findBy')->willReturn([]);
        $this->profitabilityPredictor->method('predictProfitability')->willReturn(['canPredict' => false]);

        $this->eventDispatcher->expects(self::never())->method('dispatch');

        $stats = $this->service->checkAllAlerts();
        self::assertSame(0, $stats['budget_alerts']);
    }

    public function testMarginAlertDispatchedAtCriticalSeverity(): void
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

        $captured = null;
        $this->eventDispatcher
            ->method('dispatch')
            ->willReturnCallback(static function ($event) use (&$captured) {
                if ($event instanceof LowMarginAlertEvent) {
                    $captured = $event;
                }

                return $event;
            });

        $stats = $this->service->checkAllAlerts();

        self::assertSame(1, $stats['margin_alerts']);
        self::assertNotNull($captured);
        self::assertSame('critical', $captured->getSeverity());
    }

    public function testMarginAlertDispatchedAtWarningSeverity(): void
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

        $captured = null;
        $this->eventDispatcher
            ->method('dispatch')
            ->willReturnCallback(static function ($event) use (&$captured) {
                if ($event instanceof LowMarginAlertEvent) {
                    $captured = $event;
                }

                return $event;
            });

        $stats = $this->service->checkAllAlerts();

        self::assertSame(1, $stats['margin_alerts']);
        self::assertNotNull($captured);
        self::assertSame('warning', $captured->getSeverity());
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

        $stats = $this->service->checkAllAlerts();
        self::assertSame(0, $stats['margin_alerts']);
    }

    public function testMarginAlertSkippedWhenPredictorCannotPredict(): void
    {
        $project = $this->makeProject();
        $this->projectRepository->method('findBy')->willReturn([$project]);
        $this->orderRepository->method('findBy')->willReturn([]);
        $this->profitabilityPredictor
            ->method('predictProfitability')
            ->willReturn(['canPredict' => false]);

        $this->eventDispatcher->expects(self::never())->method('dispatch');

        $stats = $this->service->checkAllAlerts();
        self::assertSame(0, $stats['margin_alerts']);
    }

    public function testPaymentAlertDispatchedWhenScheduleDueWithinSevenDays(): void
    {
        $this->projectRepository->method('findBy')->willReturn([]);

        $order = $this->createMock(Order::class);
        $schedule = $this->createMock(OrderPaymentSchedule::class);
        $schedule->method('getBillingDate')->willReturn(new DateTimeImmutable('+3 days'));
        $order->method('getPaymentSchedules')->willReturn(new \Doctrine\Common\Collections\ArrayCollection([$schedule]));

        $this->orderRepository->method('findBy')->willReturn([$order]);

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(PaymentDueAlertEvent::class));

        $stats = $this->service->checkAllAlerts();
        self::assertSame(1, $stats['payment_alerts']);
    }

    public function testPaymentAlertSkippedWhenScheduleFarInFuture(): void
    {
        $this->projectRepository->method('findBy')->willReturn([]);

        $order = $this->createMock(Order::class);
        $schedule = $this->createMock(OrderPaymentSchedule::class);
        $schedule->method('getBillingDate')->willReturn(new DateTimeImmutable('+30 days'));
        $order->method('getPaymentSchedules')->willReturn(new \Doctrine\Common\Collections\ArrayCollection([$schedule]));

        $this->orderRepository->method('findBy')->willReturn([$order]);

        $this->eventDispatcher->expects(self::never())->method('dispatch');

        $stats = $this->service->checkAllAlerts();
        self::assertSame(0, $stats['payment_alerts']);
    }

    public function testWorkloadAlertDispatchedWhenCapacityRateAboveOneHundred(): void
    {
        $contributor = $this->makeContributor(42);
        $this->contributorRepository = $this->createMock(ContributorRepository::class);
        $this->contributorRepository->method('findActiveContributors')->willReturn([$contributor]);
        $this->userRepository->method('findByRole')->willReturn([]);

        // The service iterates 3 months: now / +1m / +2m. We return overloaded
        // for the first month and normal for the rest, so a single alert fires.
        $this->workloadCalculator
            ->method('forContributor')
            ->willReturnOnConsecutiveCalls(
                ['totalDays' => 25.0, 'capacityRate' => 110.0],
                ['totalDays' => 18.0, 'capacityRate' => 80.0],
                ['totalDays' => 18.0, 'capacityRate' => 80.0],
            );

        $this->projectRepository->method('findBy')->willReturn([]);
        $this->orderRepository->method('findBy')->willReturn([]);

        $service = $this->buildServiceWithContributors([$contributor]);

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(ContributorOverloadAlertEvent::class));

        $stats = $service->checkAllAlerts();

        self::assertSame(1, $stats['overload_alerts']);
    }

    public function testWorkloadAlertSkippedWhenCapacityWithinBounds(): void
    {
        $contributor = $this->makeContributor(99);
        $this->contributorRepository = $this->createMock(ContributorRepository::class);
        $this->contributorRepository->method('findActiveContributors')->willReturn([$contributor]);
        $this->userRepository->method('findByRole')->willReturn([]);

        $this->workloadCalculator
            ->method('forContributor')
            ->willReturn(['totalDays' => 18.0, 'capacityRate' => 80.0]);

        $this->projectRepository->method('findBy')->willReturn([]);
        $this->orderRepository->method('findBy')->willReturn([]);

        $service = $this->buildServiceWithContributors([$contributor]);

        $this->eventDispatcher->expects(self::never())->method('dispatch');

        $stats = $service->checkAllAlerts();
        self::assertSame(0, $stats['overload_alerts']);
    }

    public function testWorkloadAlertSkippedWhenCalculatorReturnsZero(): void
    {
        $contributor = $this->makeContributor(7);
        $this->contributorRepository = $this->createMock(ContributorRepository::class);
        $this->contributorRepository->method('findActiveContributors')->willReturn([$contributor]);
        $this->userRepository->method('findByRole')->willReturn([]);

        // No metrics for the contributor → calculator returns 0/0 — no alert
        // should fire even though `capacityRate` happens to be the threshold's
        // floor edge.
        $this->workloadCalculator
            ->method('forContributor')
            ->willReturn(['totalDays' => 0.0, 'capacityRate' => 0.0]);

        $this->projectRepository->method('findBy')->willReturn([]);
        $this->orderRepository->method('findBy')->willReturn([]);

        $service = $this->buildServiceWithContributors([$contributor]);

        $this->eventDispatcher->expects(self::never())->method('dispatch');

        $stats = $service->checkAllAlerts();
        self::assertSame(0, $stats['overload_alerts']);
    }

    private function makeProject(
        float $budgetedDays = 100.0,
        float $spentHours = 0.0,
        float $globalProgress = 0.0,
    ): Project&MockObject {
        $project = $this->createMock(Project::class);
        $project->method('calculateBudgetedDays')->willReturn($budgetedDays);
        $project->method('getTotalTasksSpentHours')->willReturn((string) $spentHours);
        $project->method('getGlobalProgress')->willReturn((string) $globalProgress);
        $project->method('getProjectManager')->willReturn(null);
        $project->method('getKeyAccountManager')->willReturn(null);

        return $project;
    }

    private function makeContributor(int $id): Contributor&MockObject
    {
        $contributor = $this->createMock(Contributor::class);
        $contributor->method('getId')->willReturn($id);

        return $contributor;
    }

    /**
     * Rebuild the service with a refreshed contributor repository — the
     * default setUp() returned `[]`, but workload tests need active
     * contributors. This wraps the boilerplate.
     *
     * @param Contributor[] $contributors
     */
    private function buildServiceWithContributors(array $contributors): AlertDetectionService
    {
        return new AlertDetectionService(
            $this->projectRepository,
            $this->orderRepository,
            $this->userRepository,
            $this->contributorRepository,
            $this->staffingMetricsRepository,
            $this->profitabilityPredictor,
            $this->eventDispatcher,
            $this->workloadCalculator,
        );
    }
}
