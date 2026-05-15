<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Order\ValueObject;

use App\Domain\Order\ValueObject\OrderSectionId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class OrderSectionIdTest extends TestCase
{
    public function testGenerateProducesValidUuid(): void
    {
        static::assertTrue(Uuid::isValid(OrderSectionId::generate()->getValue()));
    }

    public function testFromStringAcceptsValidUuid(): void
    {
        $uuid = Uuid::v4()->toRfc4122();
        static::assertSame($uuid, OrderSectionId::fromString($uuid)->getValue());
    }

    public function testFromStringRejectsInvalidUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        OrderSectionId::fromString('bad');
    }

    public function testEquals(): void
    {
        $uuid = Uuid::v4()->toRfc4122();
        static::assertTrue(OrderSectionId::fromString($uuid)->equals(OrderSectionId::fromString($uuid)));
        static::assertFalse(OrderSectionId::generate()->equals(OrderSectionId::generate()));
    }

    public function testToString(): void
    {
        $uuid = Uuid::v4()->toRfc4122();
        static::assertSame($uuid, (string) OrderSectionId::fromString($uuid));
    }
}
