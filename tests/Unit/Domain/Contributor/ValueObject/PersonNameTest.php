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
        $this->assertSame('Jean', $name->getFirstName());
        $this->assertSame('Dupont', $name->getLastName());
        $this->assertSame('Jean Dupont', $name->getFullName());
    }

    public function testTrimsWhitespace(): void
    {
        $name = PersonName::fromParts('  Jean  ', '  Dupont  ');
        $this->assertSame('Jean', $name->getFirstName());
        $this->assertSame('Dupont', $name->getLastName());
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

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    public function testToString(): void
    {
        $name = PersonName::fromParts('Jean', 'Dupont');
        $this->assertSame('Jean Dupont', (string) $name);
    }
}
