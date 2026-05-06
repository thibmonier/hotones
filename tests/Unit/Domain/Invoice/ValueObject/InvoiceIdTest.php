<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Invoice\ValueObject;

use App\Domain\Invoice\ValueObject\InvoiceId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class InvoiceIdTest extends TestCase
{
    public function testGenerateProducesValidUuid(): void
    {
        $id = InvoiceId::generate();
        $this->assertTrue(Uuid::isValid($id->getValue()));
    }

    public function testFromStringAcceptsValidUuid(): void
    {
        $uuid = Uuid::v4()->toRfc4122();
        $id = InvoiceId::fromString($uuid);
        $this->assertSame($uuid, $id->getValue());
    }

    public function testFromStringRejectsInvalidUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        InvoiceId::fromString('not-a-uuid');
    }

    public function testEqualsTrueForSameValue(): void
    {
        $uuid = Uuid::v4()->toRfc4122();
        $a = InvoiceId::fromString($uuid);
        $b = InvoiceId::fromString($uuid);
        $this->assertTrue($a->equals($b));
    }

    public function testEqualsFalseForDifferentValues(): void
    {
        $this->assertFalse(InvoiceId::generate()->equals(InvoiceId::generate()));
    }

    public function testToStringReturnsValue(): void
    {
        $uuid = Uuid::v4()->toRfc4122();
        $id = InvoiceId::fromString($uuid);
        $this->assertSame($uuid, (string) $id);
    }
}
