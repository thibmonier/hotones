<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Invoice\ValueObject;

use App\Domain\Invoice\ValueObject\InvoiceNumber;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class InvoiceNumberTest extends TestCase
{
    public function testFromStringValidFormat(): void
    {
        $number = InvoiceNumber::fromString('F202501001');
        $this->assertSame('F202501001', $number->getValue());
    }

    public function testFromStringInvalidPrefixRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        InvoiceNumber::fromString('X202501001');
    }

    public function testFromStringInvalidFormatRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        InvoiceNumber::fromString('F2025-01-001');
    }

    public function testFromStringMissingDigitsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        InvoiceNumber::fromString('F1');
    }
}
