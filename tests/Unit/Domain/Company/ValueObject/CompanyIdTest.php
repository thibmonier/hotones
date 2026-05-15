<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Company\ValueObject;

use App\Domain\Company\ValueObject\CompanyId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * TEST-COVERAGE-010 (sprint-020) — coverage Company\CompanyId.
 */
final class CompanyIdTest extends TestCase
{
    public function testGenerateProducesValidUuid(): void
    {
        $id = CompanyId::generate();
        static::assertFalse($id->isLegacy());
        static::assertMatchesRegularExpression(
            '/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/',
            $id->getValue(),
        );
    }

    public function testFromStringValidUuid(): void
    {
        $uuid = '12345678-1234-4321-8123-123456789abc';
        $id = CompanyId::fromString($uuid);
        static::assertFalse($id->isLegacy());
        static::assertSame($uuid, $id->getValue());
    }

    public function testFromStringLegacyPrefixAccepted(): void
    {
        $id = CompanyId::fromString('legacy:42');
        static::assertTrue($id->isLegacy());
        static::assertSame(42, $id->toLegacyInt());
    }

    public function testFromStringInvalidThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Invalid Company ID/');
        CompanyId::fromString('not-a-uuid');
    }

    public function testFromLegacyIntStoresPrefix(): void
    {
        $id = CompanyId::fromLegacyInt(7);
        static::assertTrue($id->isLegacy());
        static::assertSame(7, $id->toLegacyInt());
        static::assertSame('legacy:7', $id->getValue());
    }

    public function testFromLegacyIntZeroThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CompanyId::fromLegacyInt(0);
    }

    public function testFromLegacyIntNegativeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CompanyId::fromLegacyInt(-1);
    }

    public function testToLegacyIntOnNonLegacyThrows(): void
    {
        $id = CompanyId::generate();
        $this->expectException(InvalidArgumentException::class);
        $id->toLegacyInt();
    }

    public function testEqualsByValue(): void
    {
        $a = CompanyId::fromLegacyInt(42);
        $b = CompanyId::fromLegacyInt(42);
        $c = CompanyId::fromLegacyInt(43);

        static::assertTrue($a->equals($b));
        static::assertFalse($a->equals($c));
    }

    public function testToStringReturnsValue(): void
    {
        $id = CompanyId::fromLegacyInt(42);
        static::assertSame('legacy:42', (string) $id);
    }
}
