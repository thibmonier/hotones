<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Order\ValueObject;

use App\Domain\Order\ValueObject\OrderLineId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class OrderLineIdTest extends TestCase
{
    public function testGenerateProducesValidUuid(): void
    {
        $this->assertTrue(Uuid::isValid(OrderLineId::generate()->getValue()));
    }

    public function testFromStringAcceptsValidUuid(): void
    {
        $uuid = Uuid::v4()->toRfc4122();
        $this->assertSame($uuid, OrderLineId::fromString($uuid)->getValue());
    }

    public function testFromStringRejectsInvalidUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        OrderLineId::fromString('bad');
    }

    public function testEquals(): void
    {
        $uuid = Uuid::v4()->toRfc4122();
        $this->assertTrue(OrderLineId::fromString($uuid)->equals(OrderLineId::fromString($uuid)));
        $this->assertFalse(OrderLineId::generate()->equals(OrderLineId::generate()));
    }

    public function testToString(): void
    {
        $uuid = Uuid::v4()->toRfc4122();
        $this->assertSame($uuid, (string) OrderLineId::fromString($uuid));
    }
}
