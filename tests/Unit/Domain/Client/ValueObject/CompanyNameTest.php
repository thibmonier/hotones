<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Client\ValueObject;

use App\Domain\Client\ValueObject\CompanyName;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CompanyNameTest extends TestCase
{
    public function testFromStringValid(): void
    {
        $name = CompanyName::fromString('Acme Corp');
        $this->assertSame('Acme Corp', $name->getValue());
    }

    public function testFromStringTrimsWhitespace(): void
    {
        $name = CompanyName::fromString('  Acme  ');
        $this->assertSame('Acme', $name->getValue());
    }

    public function testTooShortRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('at least 2 characters');
        CompanyName::fromString('A');
    }

    public function testTooLongRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot exceed 255 characters');
        CompanyName::fromString(str_repeat('A', 256));
    }

    public function testEquality(): void
    {
        $a = CompanyName::fromString('Foo');
        $b = CompanyName::fromString('Foo');
        $c = CompanyName::fromString('Bar');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    public function testToString(): void
    {
        $this->assertSame('Acme', (string) CompanyName::fromString('Acme'));
    }
}
