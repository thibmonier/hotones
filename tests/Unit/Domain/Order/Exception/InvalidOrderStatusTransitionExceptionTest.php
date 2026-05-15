<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Order\Exception;

use App\Domain\Order\Exception\InvalidOrderStatusTransitionException;
use App\Domain\Order\ValueObject\OrderStatus;
use App\Domain\Shared\Exception\DomainException;
use PHPUnit\Framework\TestCase;

final class InvalidOrderStatusTransitionExceptionTest extends TestCase
{
    public function testCreateBuildsExceptionWithFromTo(): void
    {
        $exception = InvalidOrderStatusTransitionException::create(
            OrderStatus::DRAFT,
            OrderStatus::SIGNED,
        );

        static::assertInstanceOf(InvalidOrderStatusTransitionException::class, $exception);
        static::assertInstanceOf(DomainException::class, $exception);
    }

    public function testCreateMessageContainsBothStatuses(): void
    {
        $exception = InvalidOrderStatusTransitionException::create(
            OrderStatus::ABANDONED,
            OrderStatus::SIGNED,
        );

        static::assertStringContainsString(OrderStatus::ABANDONED->value, $exception->getMessage());
        static::assertStringContainsString(OrderStatus::SIGNED->value, $exception->getMessage());
        static::assertStringContainsString('Cannot transition', $exception->getMessage());
    }
}
