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
        self::assertFalse($id->isLegacy());
    }

    public function testFromLegacyIntStoresPrefix(): void
    {
        $id = WorkItemId::fromLegacyInt(42);
        self::assertTrue($id->isLegacy());
        self::assertSame(42, $id->toLegacyInt());
        self::assertSame('legacy:42', $id->getValue());
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
        self::assertTrue($id->isLegacy());
        self::assertSame(99, $id->toLegacyInt());
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

        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals($c));
    }

    public function testToStringReturnsValue(): void
    {
        $id = WorkItemId::fromLegacyInt(42);
        self::assertSame('legacy:42', (string) $id);
    }
}
