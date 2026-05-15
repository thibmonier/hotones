<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Order\Exception;

use App\Domain\Order\Exception\OrderNotFoundException;
use App\Domain\Order\ValueObject\OrderId;
use App\Domain\Shared\Exception\DomainException;
use PHPUnit\Framework\TestCase;

final class OrderNotFoundExceptionTest extends TestCase
{
    public function testWithIdBuildsException(): void
    {
        $id = OrderId::generate();

        $exception = OrderNotFoundException::withId($id);

        static::assertInstanceOf(OrderNotFoundException::class, $exception);
        static::assertInstanceOf(DomainException::class, $exception);
    }

    public function testWithIdMessageContainsId(): void
    {
        $id = OrderId::generate();

        $exception = OrderNotFoundException::withId($id);

        static::assertStringContainsString($id->getValue(), $exception->getMessage());
        static::assertStringContainsString('not found', $exception->getMessage());
    }
}
