<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Project\ValueObject;

use App\Domain\Project\ValueObject\ProjectId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ProjectIdLegacyTest extends TestCase
{
    public function testFromLegacyIntWrapsValue(): void
    {
        $id = ProjectId::fromLegacyInt(42);
        static::assertSame('legacy:42', $id->value());
        static::assertTrue($id->isLegacy());
        static::assertSame(42, $id->toLegacyInt());
    }

    public function testFromLegacyIntRejectsZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ProjectId::fromLegacyInt(0);
    }

    public function testIsLegacyFalseForUuid(): void
    {
        static::assertFalse(ProjectId::generate()->isLegacy());
    }

    public function testToLegacyIntRejectsUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ProjectId::generate()->toLegacyInt();
    }

    public function testFromStringAcceptsLegacy(): void
    {
        $id = ProjectId::fromString('legacy:99');
        static::assertTrue($id->isLegacy());
        static::assertSame(99, $id->toLegacyInt());
    }
}
