<?php

declare(strict_types=1);

namespace App\Domain\Project\ValueObject;

use InvalidArgumentException;
use Stringable;

final readonly class DsoDays implements Stringable
{
    private function __construct(
        private float $days,
    ) {
        if ($days < 0.0) {
            throw new InvalidArgumentException('DSO days cannot be negative');
        }
    }

    public static function fromDays(float $days): self
    {
        return new self(round($days, 1));
    }

    public static function zero(): self
    {
        return new self(0.0);
    }

    public function getDays(): float
    {
        return $this->days;
    }

    public function equals(self $other): bool
    {
        return abs($this->days - $other->days) < 0.01;
    }

    public function __toString(): string
    {
        return (string) $this->days;
    }
}
