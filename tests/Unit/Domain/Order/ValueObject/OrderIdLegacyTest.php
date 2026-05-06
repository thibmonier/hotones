<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Order\ValueObject;

use App\Domain\Order\ValueObject\OrderId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class OrderIdLegacyTest extends TestCase
{
    public function testFromLegacyIntWrapsValue(): void
    {
        $id = OrderId::fromLegacyInt(42);
        $this->assertSame('legacy:42', $id->getValue());
        $this->assertTrue($id->isLegacy());
        $this->assertSame(42, $id->toLegacyInt());
    }

    public function testFromLegacyIntRejectsZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        OrderId::fromLegacyInt(0);
    }

    public function testIsLegacyFalseForUuid(): void
    {
        $this->assertFalse(OrderId::generate()->isLegacy());
    }

    public function testToLegacyIntRejectsUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        OrderId::generate()->toLegacyInt();
    }

    public function testFromStringAcceptsLegacy(): void
    {
        $id = OrderId::fromString('legacy:99');
        $this->assertTrue($id->isLegacy());
        $this->assertSame(99, $id->toLegacyInt());
    }

    public function testFromStringRejectsArbitrary(): void
    {
        $this->expectException(InvalidArgumentException::class);
        OrderId::fromString('not-a-uuid');
    }
}
