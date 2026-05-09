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
        self::assertFalse($id->isLegacy());
        self::assertMatchesRegularExpression(
            '/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/',
            $id->getValue(),
        );
    }

    public function testFromStringValidUuid(): void
    {
        $uuid = '12345678-1234-4321-8123-123456789abc';
        $id = CompanyId::fromString($uuid);
        self::assertFalse($id->isLegacy());
        self::assertSame($uuid, $id->getValue());
    }

    public function testFromStringLegacyPrefixAccepted(): void
    {
        $id = CompanyId::fromString('legacy:42');
        self::assertTrue($id->isLegacy());
        self::assertSame(42, $id->toLegacyInt());
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
        self::assertTrue($id->isLegacy());
        self::assertSame(7, $id->toLegacyInt());
        self::assertSame('legacy:7', $id->getValue());
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

        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals($c));
    }

    public function testToStringReturnsValue(): void
    {
        $id = CompanyId::fromLegacyInt(42);
        self::assertSame('legacy:42', (string) $id);
    }
}
