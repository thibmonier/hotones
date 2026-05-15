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
        static::assertSame('F202501001', $number->getValue());
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

    public function testGenerateProducesExpectedValue(): void
    {
        $number = InvoiceNumber::generate(2025, 3, 42);
        static::assertSame('F202503042', $number->getValue());
    }

    public function testGenerateExtractsYearMonthSequence(): void
    {
        $number = InvoiceNumber::generate(2025, 3, 42);
        static::assertSame(2025, $number->getYear());
        static::assertSame(3, $number->getMonth());
        static::assertSame(42, $number->getSequence());
    }

    public function testGenerateRejectsYearTooSmall(): void
    {
        $this->expectException(InvalidArgumentException::class);
        InvoiceNumber::generate(1999, 1, 1);
    }

    public function testGenerateRejectsYearTooLarge(): void
    {
        $this->expectException(InvalidArgumentException::class);
        InvoiceNumber::generate(2101, 1, 1);
    }

    public function testGenerateRejectsMonthBelowOne(): void
    {
        $this->expectException(InvalidArgumentException::class);
        InvoiceNumber::generate(2025, 0, 1);
    }

    public function testGenerateRejectsMonthAbove12(): void
    {
        $this->expectException(InvalidArgumentException::class);
        InvoiceNumber::generate(2025, 13, 1);
    }

    public function testGenerateRejectsZeroSequence(): void
    {
        $this->expectException(InvalidArgumentException::class);
        InvoiceNumber::generate(2025, 1, 0);
    }

    public function testEqualsTrueForSameValue(): void
    {
        $a = InvoiceNumber::fromString('F202501001');
        $b = InvoiceNumber::fromString('F202501001');
        static::assertTrue($a->equals($b));
    }

    public function testEqualsFalseForDifferentValues(): void
    {
        $a = InvoiceNumber::fromString('F202501001');
        $b = InvoiceNumber::fromString('F202501002');
        static::assertFalse($a->equals($b));
    }

    public function testToStringReturnsValue(): void
    {
        static::assertSame('F202501001', (string) InvoiceNumber::fromString('F202501001'));
    }
}
