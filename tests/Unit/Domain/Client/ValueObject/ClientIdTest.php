<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Client\ValueObject;

use App\Domain\Client\ValueObject\ClientId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ClientIdTest extends TestCase
{
    public function testGenerateProducesValidUuid(): void
    {
        $id = ClientId::generate();
        static::assertNotEmpty($id->getValue());
        static::assertMatchesRegularExpression('/^[0-9a-f-]{36}$/i', $id->getValue());
    }

    public function testFromStringValid(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $id = ClientId::fromString($uuid);
        static::assertSame($uuid, $id->getValue());
    }

    public function testFromStringInvalidThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid ClientId format');
        ClientId::fromString('not-a-uuid');
    }

    public function testEquality(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $a = ClientId::fromString($uuid);
        $b = ClientId::fromString($uuid);
        $c = ClientId::generate();

        static::assertTrue($a->equals($b));
        static::assertFalse($a->equals($c));
    }

    public function testToString(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        static::assertSame($uuid, (string) ClientId::fromString($uuid));
    }
}
