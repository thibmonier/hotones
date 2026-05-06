<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Client\ValueObject;

use App\Domain\Client\ValueObject\ClientId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the legacy int wrapper added in sprint-009 DDD-PHASE2-CLIENT-ACL.
 */
final class ClientIdLegacyTest extends TestCase
{
    public function testFromLegacyIntWrapsValue(): void
    {
        $id = ClientId::fromLegacyInt(42);

        $this->assertSame('legacy:42', $id->getValue());
        $this->assertTrue($id->isLegacy());
        $this->assertSame(42, $id->toLegacyInt());
    }

    public function testFromLegacyIntRejectsZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ClientId::fromLegacyInt(0);
    }

    public function testFromLegacyIntRejectsNegative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ClientId::fromLegacyInt(-1);
    }

    public function testIsLegacyFalseForUuid(): void
    {
        $id = ClientId::generate();
        $this->assertFalse($id->isLegacy());
    }

    public function testToLegacyIntRejectsUuid(): void
    {
        $id = ClientId::generate();
        $this->expectException(InvalidArgumentException::class);
        $id->toLegacyInt();
    }

    public function testFromStringAcceptsLegacyFormat(): void
    {
        $id = ClientId::fromString('legacy:99');
        $this->assertTrue($id->isLegacy());
        $this->assertSame(99, $id->toLegacyInt());
    }

    public function testFromStringRejectsArbitraryString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ClientId::fromString('not-uuid-not-legacy');
    }
}
