<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Vacation\ValueObject;

use App\Domain\Vacation\Exception\InvalidStatusTransitionException;
use App\Domain\Vacation\ValueObject\VacationStatus;
use PHPUnit\Framework\TestCase;

final class VacationStatusTest extends TestCase
{
    public function testPendingCanTransitionToApproved(): void
    {
        $status = VacationStatus::PENDING;

        self::assertTrue($status->canTransitionTo(VacationStatus::APPROVED));
    }

    public function testPendingCanTransitionToRejected(): void
    {
        $status = VacationStatus::PENDING;

        self::assertTrue($status->canTransitionTo(VacationStatus::REJECTED));
    }

    public function testPendingCanTransitionToCancelled(): void
    {
        $status = VacationStatus::PENDING;

        self::assertTrue($status->canTransitionTo(VacationStatus::CANCELLED));
    }

    public function testApprovedCannotTransition(): void
    {
        $status = VacationStatus::APPROVED;

        self::assertFalse($status->canTransitionTo(VacationStatus::PENDING));
        self::assertFalse($status->canTransitionTo(VacationStatus::REJECTED));
        self::assertFalse($status->canTransitionTo(VacationStatus::CANCELLED));
    }

    public function testRejectedCannotTransition(): void
    {
        $status = VacationStatus::REJECTED;

        self::assertFalse($status->canTransitionTo(VacationStatus::PENDING));
        self::assertFalse($status->canTransitionTo(VacationStatus::APPROVED));
    }

    public function testTransitionToThrowsOnInvalidTransition(): void
    {
        $status = VacationStatus::APPROVED;

        $this->expectException(InvalidStatusTransitionException::class);

        $status->transitionTo(VacationStatus::PENDING);
    }

    public function testTransitionToReturnsNewStatus(): void
    {
        $result = VacationStatus::PENDING->transitionTo(VacationStatus::APPROVED);

        self::assertSame(VacationStatus::APPROVED, $result);
    }
}
