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

        static::assertInstanceOf(InvalidStatusTransitionException::class, $exception);
        static::assertInstanceOf(DomainException::class, $exception);
        static::assertStringContainsString(VacationStatus::APPROVED->value, $exception->getMessage());
        static::assertStringContainsString(VacationStatus::PENDING->value, $exception->getMessage());
    }

    public function testCreateMessageFormat(): void
    {
        $exception = InvalidStatusTransitionException::create(
            VacationStatus::CANCELLED,
            VacationStatus::APPROVED,
        );

        static::assertStringContainsString('Cannot transition vacation', $exception->getMessage());
    }
}
