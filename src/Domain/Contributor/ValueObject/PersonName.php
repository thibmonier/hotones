<?php

declare(strict_types=1);

namespace App\Domain\Contributor\ValueObject;

use InvalidArgumentException;
use Stringable;

final readonly class PersonName implements Stringable
{
    private function __construct(
        private string $firstName,
        private string $lastName,
    ) {
        if (trim($firstName) === '') {
            throw new InvalidArgumentException('First name cannot be empty');
        }
        if (trim($lastName) === '') {
            throw new InvalidArgumentException('Last name cannot be empty');
        }
    }

    public static function fromParts(string $firstName, string $lastName): self
    {
        return new self(trim($firstName), trim($lastName));
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getFullName(): string
    {
        return $this->firstName.' '.$this->lastName;
    }

    public function equals(self $other): bool
    {
        return $this->firstName === $other->firstName
            && $this->lastName === $other->lastName;
    }

    public function __toString(): string
    {
        return $this->getFullName();
    }
}
