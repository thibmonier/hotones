<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\WorkItem\UseCase\RecordWorkItem;

use App\Application\WorkItem\UseCase\RecordWorkItem\RecordWorkItemCommand;
use App\Application\WorkItem\UseCase\RecordWorkItem\RecordWorkItemUseCase;
use App\Domain\Contributor\ValueObject\ContributorId;
use App\Domain\EmploymentPeriod\Repository\EmploymentPeriodRepositoryInterface;
use App\Domain\EmploymentPeriod\Snapshot\EmploymentPeriodSnapshot;
use App\Domain\EmploymentPeriod\ValueObject\WeeklyHours;
use App\Domain\EmploymentPeriod\ValueObject\WorkTimePercentage;
use App\Domain\WorkItem\Entity\WorkItem;
use App\Domain\WorkItem\Exception\DailyHoursWarningException;
use App\Domain\WorkItem\Repository\WorkItemRepositoryInterface;
use App\Domain\WorkItem\Service\DailyHoursValidator;
use App\Domain\WorkItem\ValueObject\WorkItemStatus;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class RecordWorkItemUseCaseTest extends TestCase
{
    public function testCreatesWorkItemInDraftWhenAuthorIsNotManager(): void
    {
        $saved = null;
        $useCase = $this->makeUseCase(
            snapshot: $this->fullTimeSnapshot(),
            existingWorkItems: [],
            savedRef: $saved,
        );

        $command = new RecordWorkItemCommand(
            contributorIdLegacy: 42,
            projectIdLegacy: 100,
            date: '2026-05-12',
            hours: 5.0,
            costRateAmount: 50.0,
            billedRateAmount: 100.0,
            authorIsManager: false,
        );

        $useCase->execute($command);

        static::assertNotNull($saved);
        static::assertSame(WorkItemStatus::DRAFT, $saved->getStatus());
    }

    public function testCreatesWorkItemValidatedWhenAuthorIsManager(): void
    {
        $saved = null;
        $useCase = $this->makeUseCase(
            snapshot: $this->fullTimeSnapshot(),
            existingWorkItems: [],
            savedRef: $saved,
        );

        $command = new RecordWorkItemCommand(
            contributorIdLegacy: 42,
            projectIdLegacy: 100,
            date: '2026-05-12',
            hours: 5.0,
            costRateAmount: 50.0,
            billedRateAmount: 100.0,
            authorIsManager: true,
        );

        $useCase->execute($command);

        static::assertNotNull($saved);
        static::assertSame(WorkItemStatus::VALIDATED, $saved->getStatus());
    }

    public function testThrowsDailyHoursWarningExceptionWhenExceededWithoutOverride(): void
    {
        $existing = $this->makeExistingWorkItem(42, '2026-05-12', 6.0);
        $saved = null;

        $useCase = $this->makeUseCase(
            snapshot: $this->fullTimeSnapshot(),
            existingWorkItems: [$existing],
            savedRef: $saved,
        );

        $command = new RecordWorkItemCommand(
            contributorIdLegacy: 42,
            projectIdLegacy: 100,
            date: '2026-05-12',
            hours: 2.0, // existing 6h + 2h = 8h > maxHours 7h
            costRateAmount: 50.0,
            billedRateAmount: 100.0,
            userOverride: false,
        );

        $this->expectException(DailyHoursWarningException::class);
        $useCase->execute($command);
    }

    public function testAllowsOverrideAndLogsWhenUserOverrideTrue(): void
    {
        $existing = $this->makeExistingWorkItem(42, '2026-05-12', 6.0);
        $saved = null;

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('warning')
            ->with(
                static::stringContains('override accepted'),
                static::callback(static fn (array $context): bool => ($context['daily_total_after_override'] ?? null) === 8.0
                    && ($context['daily_max_hours'] ?? null) === 7.0),
            );

        $useCase = $this->makeUseCase(
            snapshot: $this->fullTimeSnapshot(),
            existingWorkItems: [$existing],
            savedRef: $saved,
            logger: $logger,
        );

        $command = new RecordWorkItemCommand(
            contributorIdLegacy: 42,
            projectIdLegacy: 100,
            date: '2026-05-12',
            hours: 2.0,
            costRateAmount: 50.0,
            billedRateAmount: 100.0,
            userOverride: true,
        );

        $useCase->execute($command);

        static::assertNotNull($saved);
    }

    public function testDispatchesDomainEventsViaMessageBus(): void
    {
        $eventBus = $this->createMock(MessageBusInterface::class);
        $eventBus->expects(self::atLeastOnce())
            ->method('dispatch')
            ->willReturnCallback(static fn (object $event): Envelope => new Envelope($event));

        $saved = null;
        $useCase = $this->makeUseCase(
            snapshot: $this->fullTimeSnapshot(),
            existingWorkItems: [],
            savedRef: $saved,
            eventBus: $eventBus,
        );

        $command = new RecordWorkItemCommand(
            contributorIdLegacy: 42,
            projectIdLegacy: 100,
            date: '2026-05-12',
            hours: 4.0,
            costRateAmount: 50.0,
            billedRateAmount: 100.0,
        );

        $useCase->execute($command);
    }

    private function fullTimeSnapshot(): EmploymentPeriodSnapshot
    {
        return new EmploymentPeriodSnapshot(
            weeklyHours: WeeklyHours::fromFloat(35.0),
            workTimePercentage: WorkTimePercentage::fromFloat(100.0),
        );
    }

    private function makeExistingWorkItem(int $contributorIdLegacy, string $date, float $hours): WorkItem
    {
        return WorkItem::create(
            id: \App\Domain\WorkItem\ValueObject\WorkItemId::generate(),
            projectId: \App\Domain\Project\ValueObject\ProjectId::generate(),
            contributorId: ContributorId::fromLegacyInt($contributorIdLegacy),
            workedOn: new DateTimeImmutable($date),
            hours: \App\Domain\WorkItem\ValueObject\WorkedHours::fromFloat($hours),
            costRate: \App\Domain\WorkItem\ValueObject\HourlyRate::fromAmount(50.0),
            billedRate: \App\Domain\WorkItem\ValueObject\HourlyRate::fromAmount(100.0),
        );
    }

    private function makeUseCase(
        EmploymentPeriodSnapshot $snapshot,
        array $existingWorkItems,
        ?WorkItem &$savedRef = null,
        ?LoggerInterface $logger = null,
        ?MessageBusInterface $eventBus = null,
    ): RecordWorkItemUseCase {
        $workItemRepo = $this->createMock(WorkItemRepositoryInterface::class);
        $workItemRepo->method('findByContributorAndDate')->willReturn($existingWorkItems);
        $workItemRepo->method('save')
            ->willReturnCallback(static function (WorkItem $w) use (&$savedRef): void {
                $savedRef = $w;
            });

        $employmentRepo = $this->createMock(EmploymentPeriodRepositoryInterface::class);
        $employmentRepo->method('findActiveSnapshotForContributor')->willReturn($snapshot);

        $validator = new DailyHoursValidator($employmentRepo, $workItemRepo);

        $logger ??= $this->createMock(LoggerInterface::class);

        if ($eventBus === null) {
            $defaultBus = $this->createMock(MessageBusInterface::class);
            $defaultBus->method('dispatch')
                ->willReturnCallback(static fn (object $event): Envelope => new Envelope($event));
            $eventBus = $defaultBus;
        }

        return new RecordWorkItemUseCase($workItemRepo, $validator, $eventBus, $logger);
    }
}
