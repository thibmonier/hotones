<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Project\ValueObject;

use App\Domain\Project\ValueObject\ProjectTaskId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ProjectTaskIdTest extends TestCase
{
    public function testGenerateProducesValidUuid(): void
    {
        $id = ProjectTaskId::generate();
        static::assertFalse($id->isLegacy());
    }

    public function testFromLegacyIntStoresPrefix(): void
    {
        $id = ProjectTaskId::fromLegacyInt(42);
        static::assertTrue($id->isLegacy());
        static::assertSame(42, $id->toLegacyInt());
        static::assertSame('legacy:42', $id->getValue());
    }

    public function testFromLegacyIntZeroThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ProjectTaskId::fromLegacyInt(0);
    }

    public function testFromLegacyIntNegativeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ProjectTaskId::fromLegacyInt(-7);
    }

    public function testFromStringInvalidUuidThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ProjectTaskId::fromString('not-a-uuid');
    }

    public function testFromStringValidLegacyAcceptsPrefix(): void
    {
        $id = ProjectTaskId::fromString('legacy:99');
        static::assertTrue($id->isLegacy());
        static::assertSame(99, $id->toLegacyInt());
    }

    public function testToLegacyIntOnNonLegacyThrows(): void
    {
        $id = ProjectTaskId::generate();

        $this->expectException(InvalidArgumentException::class);
        $id->toLegacyInt();
    }

    public function testEqualsByValue(): void
    {
        $a = ProjectTaskId::fromLegacyInt(7);
        $b = ProjectTaskId::fromLegacyInt(7);
        $c = ProjectTaskId::fromLegacyInt(8);

        static::assertTrue($a->equals($b));
        static::assertFalse($a->equals($c));
    }

    public function testToStringReturnsValue(): void
    {
        $id = ProjectTaskId::fromLegacyInt(42);
        static::assertSame('legacy:42', (string) $id);
    }
}
