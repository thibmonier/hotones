<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Shared\ValueObject;

use App\Domain\Shared\ValueObject\TenantId;
use Error;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TenantIdTest extends TestCase
{
    public function testFromIntCreatesTenantId(): void
    {
        $tenantId = TenantId::fromInt(42);

        static::assertSame(42, $tenantId->value);
    }

    public function testFromStringParsesNumericString(): void
    {
        $tenantId = TenantId::fromString('17');

        static::assertSame(17, $tenantId->value);
    }

    public function testFromIntRejectsZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/positive integer/');

        TenantId::fromInt(0);
    }

    public function testFromIntRejectsNegative(): void
    {
        $this->expectException(InvalidArgumentException::class);

        TenantId::fromInt(-1);
    }

    public function testFromStringRejectsNonNumeric(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/positive integer literal/');

        TenantId::fromString('abc');
    }

    public function testFromStringRejectsEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        TenantId::fromString('');
    }

    public function testFromStringRejectsNegativeLiteral(): void
    {
        $this->expectException(InvalidArgumentException::class);

        TenantId::fromString('-5');
    }

    public function testEqualsReturnsTrueForSameValue(): void
    {
        $a = TenantId::fromInt(7);
        $b = TenantId::fromInt(7);

        static::assertTrue($a->equals($b));
    }

    public function testEqualsReturnsFalseForDifferentValues(): void
    {
        $a = TenantId::fromInt(1);
        $b = TenantId::fromInt(2);

        static::assertFalse($a->equals($b));
    }

    public function testToStringReturnsValueAsString(): void
    {
        $tenantId = TenantId::fromInt(123);

        static::assertSame('123', (string) $tenantId);
    }

    public function testValueIsReadonly(): void
    {
        $tenantId = TenantId::fromInt(1);

        // Domain rules require final readonly. Reading public prop OK.
        static::assertSame(1, $tenantId->value);

        // Writing should fail at PHP level (readonly).
        $this->expectException(Error::class);
        // Suppress static analysis: this is precisely what we want to verify.
        /* @phpstan-ignore property.readOnlyAssignNotInConstructor */
        $tenantId->value = 99;
    }
}
