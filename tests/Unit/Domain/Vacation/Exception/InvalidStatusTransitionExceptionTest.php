<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Vacation\Exception;

use App\Domain\Vacation\Exception\InvalidStatusTransitionException;
use App\Domain\Vacation\ValueObject\VacationStatus;
use DomainException;
use PHPUnit\Framework\TestCase;

final class InvalidStatusTransitionExceptionTest extends TestCase
{
    public function testCreateBuildsMessageWithFromTo(): void
    {
        $exception = InvalidStatusTransitionException::create(
            VacationStatus::APPROVED,
            VacationStatus::PENDING,
        );

        self::assertInstanceOf(InvalidStatusTransitionException::class, $exception);
        self::assertInstanceOf(DomainException::class, $exception);
        self::assertStringContainsString(VacationStatus::APPROVED->value, $exception->getMessage());
        self::assertStringContainsString(VacationStatus::PENDING->value, $exception->getMessage());
    }

    public function testCreateMessageFormat(): void
    {
        $exception = InvalidStatusTransitionException::create(
            VacationStatus::CANCELLED,
            VacationStatus::APPROVED,
        );

        self::assertStringContainsString('Cannot transition vacation', $exception->getMessage());
    }
}
