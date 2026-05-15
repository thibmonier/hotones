<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Contributor\ValueObject;

use App\Domain\Contributor\ValueObject\ContributorId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ContributorIdTest extends TestCase
{
    public function testGenerate(): void
    {
        static::assertTrue(Uuid::isValid(ContributorId::generate()->getValue()));
    }

    public function testFromString(): void
    {
        $uuid = Uuid::v4()->toRfc4122();
        static::assertSame($uuid, ContributorId::fromString($uuid)->getValue());
    }

    public function testFromStringRejectsInvalidUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ContributorId::fromString('bad');
    }

    public function testFromLegacyInt(): void
    {
        $id = ContributorId::fromLegacyInt(42);
        static::assertTrue($id->isLegacy());
        static::assertSame(42, $id->toLegacyInt());
        static::assertSame('legacy:42', $id->getValue());
    }

    public function testFromLegacyIntRejectsZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ContributorId::fromLegacyInt(0);
    }

    public function testToLegacyIntFailsForUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ContributorId::generate()->toLegacyInt();
    }

    public function testIsLegacyFalseForUuid(): void
    {
        static::assertFalse(ContributorId::generate()->isLegacy());
    }

    public function testEquals(): void
    {
        static::assertTrue(ContributorId::fromLegacyInt(7)->equals(ContributorId::fromLegacyInt(7)));
        static::assertFalse(ContributorId::fromLegacyInt(7)->equals(ContributorId::fromLegacyInt(8)));
    }

    public function testToString(): void
    {
        static::assertSame('legacy:5', (string) ContributorId::fromLegacyInt(5));
    }
}
