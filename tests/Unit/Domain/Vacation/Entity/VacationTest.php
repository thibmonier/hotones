<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Vacation\Entity;

use App\Domain\Vacation\Entity\Vacation;
use App\Domain\Vacation\Event\VacationApproved;
use App\Domain\Vacation\Event\VacationCancelled;
use App\Domain\Vacation\Event\VacationRejected;
use App\Domain\Vacation\Event\VacationRequested;
use App\Domain\Vacation\Exception\InvalidStatusTransitionException;
use App\Domain\Vacation\ValueObject\DailyHours;
use App\Domain\Vacation\ValueObject\DateRange;
use App\Domain\Vacation\ValueObject\VacationId;
use App\Domain\Vacation\ValueObject\VacationStatus;
use App\Domain\Vacation\ValueObject\VacationType;
use App\Entity\Company;
use App\Entity\Contributor;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class VacationTest extends TestCase
{
    public function testRequestCreatesVacationWithPendingStatus(): void
    {
        $vacation = $this->createVacation();

        static::assertSame(VacationStatus::PENDING, $vacation->getStatus());
        static::assertSame(VacationType::PAID_LEAVE, $vacation->getType());
        static::assertNotNull($vacation->getCreatedAt());
        static::assertNull($vacation->getApprovedAt());
        static::assertNull($vacation->getApprovedBy());
    }

    public function testRequestRecordsDomainEvent(): void
    {
        $vacation = $this->createVacation();
        $events = $vacation->pullDomainEvents();

        static::assertCount(1, $events);
        static::assertInstanceOf(VacationRequested::class, $events[0]);
    }

    public function testApproveChangesStatusAndRecordsEvent(): void
    {
        $vacation = $this->createVacation();
        $vacation->pullDomainEvents(); // clear creation event

        $user = $this->createStub(User::class);
        $user->method('getId')->willReturn(42);

        $vacation->approve($user);

        static::assertSame(VacationStatus::APPROVED, $vacation->getStatus());
        static::assertNotNull($vacation->getApprovedAt());
        static::assertSame($user, $vacation->getApprovedBy());

        $events = $vacation->pullDomainEvents();
        static::assertCount(1, $events);
        static::assertInstanceOf(VacationApproved::class, $events[0]);
    }

    public function testRejectChangesStatusAndRecordsEvent(): void
    {
        $vacation = $this->createVacation();
        $vacation->pullDomainEvents();

        $vacation->reject();

        static::assertSame(VacationStatus::REJECTED, $vacation->getStatus());

        $events = $vacation->pullDomainEvents();
        static::assertCount(1, $events);
        static::assertInstanceOf(VacationRejected::class, $events[0]);
    }

    public function testCancelChangesStatusAndRecordsEvent(): void
    {
        $vacation = $this->createVacation();
        $vacation->pullDomainEvents();

        $vacation->cancel();

        static::assertSame(VacationStatus::CANCELLED, $vacation->getStatus());

        $events = $vacation->pullDomainEvents();
        static::assertCount(1, $events);
        static::assertInstanceOf(VacationCancelled::class, $events[0]);
    }

    public function testCannotApproveAlreadyApprovedVacation(): void
    {
        $vacation = $this->createVacation();
        $user = $this->createStub(User::class);
        $user->method('getId')->willReturn(42);

        $vacation->approve($user);

        $this->expectException(InvalidStatusTransitionException::class);
        $vacation->approve($user);
    }

    public function testManagerCanCancelApprovedVacation(): void
    {
        // US-069: a manager-initiated cancellation must succeed on an APPROVED vacation.
        $vacation = $this->createVacation();
        $user = $this->createStub(User::class);
        $user->method('getId')->willReturn(42);

        $vacation->approve($user);
        $vacation->cancel();

        static::assertSame(VacationStatus::CANCELLED, $vacation->getStatus());
    }

    public function testRejectStoresOptionalRejectionReason(): void
    {
        $vacation = $this->createVacation();

        $vacation->reject('Planning sature');

        static::assertSame(VacationStatus::REJECTED, $vacation->getStatus());
        static::assertSame('Planning sature', $vacation->getRejectionReason());
    }

    public function testRejectWithoutReasonKeepsRejectionReasonNull(): void
    {
        $vacation = $this->createVacation();

        $vacation->reject();

        static::assertNull($vacation->getRejectionReason());
    }

    public function testGetTotalHours(): void
    {
        $vacation = $this->createVacation();

        // 5 days (Jan 10-14) * 8h = 40h
        static::assertSame('40.00', $vacation->getTotalHours());
    }

    public function testGetNumberOfWorkingDays(): void
    {
        // Mon Jan 6 to Fri Jan 10 = 5 working days
        $vacation = Vacation::request(
            VacationId::generate(),
            $this->createStub(Company::class),
            $this->createStub(Contributor::class),
            DateRange::fromStrings('2025-01-06', '2025-01-10'),
            VacationType::PAID_LEAVE,
            DailyHours::fullDay(),
        );

        static::assertSame(5, $vacation->getNumberOfWorkingDays());
    }

    public function testPullDomainEventsClearsEvents(): void
    {
        $vacation = $this->createVacation();

        $events1 = $vacation->pullDomainEvents();
        $events2 = $vacation->pullDomainEvents();

        static::assertCount(1, $events1);
        static::assertCount(0, $events2);
    }

    private function createVacation(): Vacation
    {
        return Vacation::request(
            VacationId::generate(),
            $this->createStub(Company::class),
            $this->createStub(Contributor::class),
            DateRange::fromStrings('2025-01-10', '2025-01-14'),
            VacationType::PAID_LEAVE,
            DailyHours::fullDay(),
        );
    }
}
