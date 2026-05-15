<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\WorkItem\ValueObject;

use App\Domain\WorkItem\ValueObject\WorkItemId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class WorkItemIdTest extends TestCase
{
    public function testGenerateProducesValidUuid(): void
    {
        $id = WorkItemId::generate();
        static::assertFalse($id->isLegacy());
    }

    public function testFromLegacyIntStoresPrefix(): void
    {
        $id = WorkItemId::fromLegacyInt(42);
        static::assertTrue($id->isLegacy());
        static::assertSame(42, $id->toLegacyInt());
        static::assertSame('legacy:42', $id->getValue());
    }

    public function testFromLegacyIntZeroThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        WorkItemId::fromLegacyInt(0);
    }

    public function testFromLegacyIntNegativeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        WorkItemId::fromLegacyInt(-7);
    }

    public function testFromStringInvalidUuidThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        WorkItemId::fromString('not-a-uuid');
    }

    public function testFromStringValidLegacyAcceptsPrefix(): void
    {
        $id = WorkItemId::fromString('legacy:99');
        static::assertTrue($id->isLegacy());
        static::assertSame(99, $id->toLegacyInt());
    }

    public function testToLegacyIntOnNonLegacyThrows(): void
    {
        $id = WorkItemId::generate();

        $this->expectException(InvalidArgumentException::class);
        $id->toLegacyInt();
    }

    public function testEqualsByValue(): void
    {
        $a = WorkItemId::fromLegacyInt(7);
        $b = WorkItemId::fromLegacyInt(7);
        $c = WorkItemId::fromLegacyInt(8);

        static::assertTrue($a->equals($b));
        static::assertFalse($a->equals($c));
    }

    public function testToStringReturnsValue(): void
    {
        $id = WorkItemId::fromLegacyInt(42);
        static::assertSame('legacy:42', (string) $id);
    }
}
