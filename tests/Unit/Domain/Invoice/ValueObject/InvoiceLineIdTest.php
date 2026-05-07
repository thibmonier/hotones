<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Invoice\ValueObject;

use App\Domain\Invoice\ValueObject\InvoiceLineId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class InvoiceLineIdTest extends TestCase
{
    public function testGenerateProducesValidUuid(): void
    {
        $id = InvoiceLineId::generate();
        $this->assertTrue(Uuid::isValid($id->getValue()));
    }

    public function testFromStringAcceptsValidUuid(): void
    {
        $uuid = Uuid::v4()->toRfc4122();
        $id = InvoiceLineId::fromString($uuid);
        $this->assertSame($uuid, $id->getValue());
    }

    public function testFromStringRejectsInvalidUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        InvoiceLineId::fromString('invalid');
    }

    public function testEqualsTrueForSameValue(): void
    {
        $uuid = Uuid::v4()->toRfc4122();
        $this->assertTrue(InvoiceLineId::fromString($uuid)->equals(InvoiceLineId::fromString($uuid)));
    }

    public function testEqualsFalseForDifferentValues(): void
    {
        $this->assertFalse(InvoiceLineId::generate()->equals(InvoiceLineId::generate()));
    }

    public function testToStringReturnsValue(): void
    {
        $uuid = Uuid::v4()->toRfc4122();
        $this->assertSame($uuid, (string) InvoiceLineId::fromString($uuid));
    }
}
