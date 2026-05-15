<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Contributor\ValueObject;

use App\Domain\Contributor\ValueObject\PersonName;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PersonNameTest extends TestCase
{
    public function testFromParts(): void
    {
        $name = PersonName::fromParts('Jean', 'Dupont');
        static::assertSame('Jean', $name->getFirstName());
        static::assertSame('Dupont', $name->getLastName());
        static::assertSame('Jean Dupont', $name->getFullName());
    }

    public function testTrimsWhitespace(): void
    {
        $name = PersonName::fromParts('  Jean  ', '  Dupont  ');
        static::assertSame('Jean', $name->getFirstName());
        static::assertSame('Dupont', $name->getLastName());
    }

    public function testRejectsEmptyFirstName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PersonName::fromParts('  ', 'Dupont');
    }

    public function testRejectsEmptyLastName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PersonName::fromParts('Jean', '');
    }

    public function testEquals(): void
    {
        $a = PersonName::fromParts('Jean', 'Dupont');
        $b = PersonName::fromParts('Jean', 'Dupont');
        $c = PersonName::fromParts('Marie', 'Dupont');

        static::assertTrue($a->equals($b));
        static::assertFalse($a->equals($c));
    }

    public function testToString(): void
    {
        $name = PersonName::fromParts('Jean', 'Dupont');
        static::assertSame('Jean Dupont', (string) $name);
    }
}
