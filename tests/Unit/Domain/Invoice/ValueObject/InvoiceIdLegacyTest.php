<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Invoice\ValueObject;

use App\Domain\Invoice\ValueObject\InvoiceId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class InvoiceIdLegacyTest extends TestCase
{
    public function testFromLegacyIntWrapsValue(): void
    {
        $id = InvoiceId::fromLegacyInt(42);
        static::assertSame('legacy:42', $id->getValue());
        static::assertTrue($id->isLegacy());
        static::assertSame(42, $id->toLegacyInt());
    }

    public function testFromLegacyIntRejectsZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        InvoiceId::fromLegacyInt(0);
    }

    public function testIsLegacyFalseForUuid(): void
    {
        static::assertFalse(InvoiceId::generate()->isLegacy());
    }

    public function testToLegacyIntRejectsUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        InvoiceId::generate()->toLegacyInt();
    }
}
