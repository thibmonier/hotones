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

        self::assertInstanceOf(OrderNotFoundException::class, $exception);
        self::assertInstanceOf(DomainException::class, $exception);
    }

    public function testWithIdMessageContainsId(): void
    {
        $id = OrderId::generate();

        $exception = OrderNotFoundException::withId($id);

        self::assertStringContainsString($id->getValue(), $exception->getMessage());
        self::assertStringContainsString('not found', $exception->getMessage());
    }
}
