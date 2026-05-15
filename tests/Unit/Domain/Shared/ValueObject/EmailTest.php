<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Shared\ValueObject;

use App\Domain\Shared\ValueObject\Email;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    public function testFromStringValid(): void
    {
        $email = Email::fromString('user@example.com');
        static::assertSame('user@example.com', $email->getValue());
    }

    public function testNormalizesLowercaseAndTrim(): void
    {
        $email = Email::fromString('  USER@Example.COM  ');
        static::assertSame('user@example.com', $email->getValue());
    }

    public function testRejectsInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email address: not-an-email');
        Email::fromString('not-an-email');
    }

    public function testEquality(): void
    {
        $a = Email::fromString('a@b.com');
        $b = Email::fromString('A@B.com');
        $c = Email::fromString('x@y.com');
        static::assertTrue($a->equals($b));
        static::assertFalse($a->equals($c));
    }

    public function testGetDomain(): void
    {
        static::assertSame('example.com', Email::fromString('foo@example.com')->getDomain());
    }

    public function testToString(): void
    {
        static::assertSame('foo@bar.io', (string) Email::fromString('foo@bar.io'));
    }
}
