<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\WorkItem\Service;

use App\Domain\Contributor\ValueObject\ContributorId;
use App\Domain\EmploymentPeriod\Exception\NoActiveEmploymentPeriodException;
use App\Domain\EmploymentPeriod\Repository\EmploymentPeriodRepositoryInterface;
use App\Domain\EmploymentPeriod\Snapshot\EmploymentPeriodSnapshot;
use App\Domain\EmploymentPeriod\ValueObject\WeeklyHours;
use App\Domain\EmploymentPeriod\ValueObject\WorkTimePercentage;
use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\WorkItem\Entity\WorkItem;
use App\Domain\WorkItem\Repository\WorkItemRepositoryInterface;
use App\Domain\WorkItem\Service\DailyHoursValidator;
use App\Domain\WorkItem\ValueObject\HourlyRate;
use App\Domain\WorkItem\ValueObject\WorkedHours;
use App\Domain\WorkItem\ValueObject\WorkItemId;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class DailyHoursValidatorTest extends TestCase
{
    public function testDailyMaxHoursStandardFullTime(): void
    {
        $contributorId = ContributorId::fromLegacyInt(42);
        $date = new DateTimeImmutable('2026-05-12');

        $snapshot = new EmploymentPeriodSnapshot(
            weeklyHours: WeeklyHours::fromFloat(35.0),
            workTimePercentage: WorkTimePercentage::fromFloat(100.0),
        );

        $employmentRepo = $this->createMock(EmploymentPeriodRepositoryInterface::class);
        $employmentRepo->expects(self::once())
            ->method('findActiveSnapshotForContributor')
            ->with($contributorId, $date)
            ->willReturn($snapshot);

        $workItemRepo = $this->createMock(WorkItemRepositoryInterface::class);

        $validator = new DailyHoursValidator($employmentRepo, $workItemRepo);

        self::assertSame(7.0, $validator->dailyMaxHours($contributorId, $date)->getValue());
    }

    public function testDailyMaxHoursPartTime80Percent(): void
    {
        $contributorId = ContributorId::fromLegacyInt(42);
        $date = new DateTimeImmutable('2026-05-12');

        $snapshot = new EmploymentPeriodSnapshot(
            weeklyHours: WeeklyHours::fromFloat(35.0),
            workTimePercentage: WorkTimePercentage::fromFloat(80.0),
        );

        $employmentRepo = $this->createMock(EmploymentPeriodRepositoryInterface::class);
        $employmentRepo->method('findActiveSnapshotForContributor')->willReturn($snapshot);

        $workItemRepo = $this->createMock(WorkItemRepositoryInterface::class);

        $validator = new DailyHoursValidator($employmentRepo, $workItemRepo);

        self::assertSame(5.6, $validator->dailyMaxHours($contributorId, $date)->getValue());
    }

    public function testDailyMaxHoursThrowsWhenNoActivePeriod(): void
    {
        $contributorId = ContributorId::fromLegacyInt(42);
        $date = new DateTimeImmutable('2026-05-12');

        $employmentRepo = $this->createMock(EmploymentPeriodRepositoryInterface::class);
        $employmentRepo->method('findActiveSnapshotForContributor')->willReturn(null);

        $workItemRepo = $this->createMock(WorkItemRepositoryInterface::class);

        $validator = new DailyHoursValidator($employmentRepo, $workItemRepo);

        $this->expectException(NoActiveEmploymentPeriodException::class);
        $validator->dailyMaxHours($contributorId, $date);
    }

    public function testIsExceededTrueWhenDailyTotalPlusAdditionalAboveMax(): void
    {
        $contributorId = ContributorId::fromLegacyInt(42);
        $date = new DateTimeImmutable('2026-05-12');

        $snapshot = new EmploymentPeriodSnapshot(
            weeklyHours: WeeklyHours::fromFloat(35.0),
            workTimePercentage: WorkTimePercentage::fromFloat(100.0),
        );

        $existing = $this->makeWorkItem($contributorId, $date, 6.0);

        $employmentRepo = $this->createMock(EmploymentPeriodRepositoryInterface::class);
        $employmentRepo->method('findActiveSnapshotForContributor')->willReturn($snapshot);

        $workItemRepo = $this->createMock(WorkItemRepositoryInterface::class);
        $workItemRepo->method('findByContributorAndDate')->willReturn([$existing]);

        $validator = new DailyHoursValidator($employmentRepo, $workItemRepo);

        // existing 6h + additional 2h = 8h > maxHours 7h
        self::assertTrue($validator->isExceeded($contributorId, $date, WorkedHours::fromFloat(2.0)));
    }

    public function testIsExceededFalseWhenSumWithinMax(): void
    {
        $contributorId = ContributorId::fromLegacyInt(42);
        $date = new DateTimeImmutable('2026-05-12');

        $snapshot = new EmploymentPeriodSnapshot(
            weeklyHours: WeeklyHours::fromFloat(35.0),
            workTimePercentage: WorkTimePercentage::fromFloat(100.0),
        );

        $existing = $this->makeWorkItem($contributorId, $date, 5.0);

        $employmentRepo = $this->createMock(EmploymentPeriodRepositoryInterface::class);
        $employmentRepo->method('findActiveSnapshotForContributor')->willReturn($snapshot);

        $workItemRepo = $this->createMock(WorkItemRepositoryInterface::class);
        $workItemRepo->method('findByContributorAndDate')->willReturn([$existing]);

        $validator = new DailyHoursValidator($employmentRepo, $workItemRepo);

        // existing 5h + additional 1h = 6h <= maxHours 7h
        self::assertFalse($validator->isExceeded($contributorId, $date, WorkedHours::fromFloat(1.0)));
    }

    public function testCurrentDailyTotalSumsHours(): void
    {
        $contributorId = ContributorId::fromLegacyInt(42);
        $date = new DateTimeImmutable('2026-05-12');

        $existing = [
            $this->makeWorkItem($contributorId, $date, 3.5),
            $this->makeWorkItem($contributorId, $date, 1.5),
        ];

        $employmentRepo = $this->createMock(EmploymentPeriodRepositoryInterface::class);
        $workItemRepo = $this->createMock(WorkItemRepositoryInterface::class);
        $workItemRepo->method('findByContributorAndDate')->willReturn($existing);

        $validator = new DailyHoursValidator($employmentRepo, $workItemRepo);

        self::assertSame(5.0, $validator->currentDailyTotal($contributorId, $date));
    }

    public function testCurrentDailyTotalZeroWhenNoWorkItems(): void
    {
        $contributorId = ContributorId::fromLegacyInt(42);
        $date = new DateTimeImmutable('2026-05-12');

        $employmentRepo = $this->createMock(EmploymentPeriodRepositoryInterface::class);
        $workItemRepo = $this->createMock(WorkItemRepositoryInterface::class);
        $workItemRepo->method('findByContributorAndDate')->willReturn([]);

        $validator = new DailyHoursValidator($employmentRepo, $workItemRepo);

        self::assertSame(0.0, $validator->currentDailyTotal($contributorId, $date));
    }

    private function makeWorkItem(ContributorId $contributorId, DateTimeImmutable $date, float $hours): WorkItem
    {
        return WorkItem::create(
            id: WorkItemId::generate(),
            projectId: ProjectId::generate(),
            contributorId: $contributorId,
            workedOn: $date,
            hours: WorkedHours::fromFloat($hours),
            costRate: HourlyRate::fromAmount(50.0),
            billedRate: HourlyRate::fromAmount(100.0),
        );
    }
}
