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

        self::assertInstanceOf(InvalidOrderStatusTransitionException::class, $exception);
        self::assertInstanceOf(DomainException::class, $exception);
    }

    public function testCreateMessageContainsBothStatuses(): void
    {
        $exception = InvalidOrderStatusTransitionException::create(
            OrderStatus::ABANDONED,
            OrderStatus::SIGNED,
        );

        self::assertStringContainsString(OrderStatus::ABANDONED->value, $exception->getMessage());
        self::assertStringContainsString(OrderStatus::SIGNED->value, $exception->getMessage());
        self::assertStringContainsString('Cannot transition', $exception->getMessage());
    }
}
