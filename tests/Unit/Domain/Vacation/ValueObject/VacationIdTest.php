<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Vacation\ValueObject;

use App\Domain\Vacation\ValueObject\VacationId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * TEST-COVERAGE-010 (sprint-020) — coverage Vacation\VacationId.
 */
final class VacationIdTest extends TestCase
{
    public function testGenerateProducesValidUuid(): void
    {
        $id = VacationId::generate();
        self::assertNotEmpty($id->getValue());
        // RFC 4122 format : xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
        self::assertMatchesRegularExpression(
            '/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/',
            $id->getValue(),
        );
    }

    public function testFromStringValidUuid(): void
    {
        $uuid = '12345678-1234-4321-8123-123456789abc';
        $id = VacationId::fromString($uuid);
        self::assertSame($uuid, $id->getValue());
    }

    public function testFromStringInvalidThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Invalid UUID format/');
        VacationId::fromString('not-a-uuid');
    }

    public function testFromStringEmptyThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        VacationId::fromString('');
    }

    public function testEqualsByValue(): void
    {
        $uuid = '12345678-1234-4321-8123-123456789abc';
        $a = VacationId::fromString($uuid);
        $b = VacationId::fromString($uuid);

        self::assertTrue($a->equals($b));
    }

    public function testNotEqualsForDifferentUuids(): void
    {
        $a = VacationId::fromString('12345678-1234-4321-8123-123456789abc');
        $b = VacationId::fromString('aaaaaaaa-bbbb-4ccc-9ddd-eeeeeeeeeeee');

        self::assertFalse($a->equals($b));
    }

    public function testGenerateProducesUniqueIds(): void
    {
        $a = VacationId::generate();
        $b = VacationId::generate();

        self::assertFalse($a->equals($b));
    }

    public function testToStringReturnsValue(): void
    {
        $uuid = '12345678-1234-4321-8123-123456789abc';
        $id = VacationId::fromString($uuid);
        self::assertSame($uuid, (string) $id);
    }
}
