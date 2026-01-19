<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObject;

use InvalidArgumentException;

final readonly class Money
{
    private const string DEFAULT_CURRENCY = 'EUR';

    private function __construct(
        private int $amountCents,
        private string $currency = self::DEFAULT_CURRENCY,
    ) {
        if ($amountCents < 0) {
            throw new InvalidArgumentException('Amount cannot be negative');
        }

        if (empty($currency)) {
            throw new InvalidArgumentException('Currency cannot be empty');
        }
    }

    public static function fromCents(int $cents, string $currency = self::DEFAULT_CURRENCY): self
    {
        return new self($cents, $currency);
    }

    public static function fromAmount(float $amount, string $currency = self::DEFAULT_CURRENCY): self
    {
        if ($amount < 0) {
            throw new InvalidArgumentException('Amount cannot be negative');
        }

        return new self((int) round($amount * 100), $currency);
    }

    public static function zero(string $currency = self::DEFAULT_CURRENCY): self
    {
        return new self(0, $currency);
    }

    public function add(self $other): self
    {
        $this->ensureSameCurrency($other);

        return new self($this->amountCents + $other->amountCents, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->ensureSameCurrency($other);

        $result = $this->amountCents - $other->amountCents;

        if ($result < 0) {
            throw new InvalidArgumentException('Subtraction would result in negative amount');
        }

        return new self($result, $this->currency);
    }

    public function multiply(float $multiplier): self
    {
        if ($multiplier < 0) {
            throw new InvalidArgumentException('Multiplier cannot be negative');
        }

        return new self((int) round($this->amountCents * $multiplier), $this->currency);
    }

    public function percentage(float $percent): self
    {
        return $this->multiply($percent / 100);
    }

    public function isZero(): bool
    {
        return $this->amountCents === 0;
    }

    public function isPositive(): bool
    {
        return $this->amountCents > 0;
    }

    public function isGreaterThan(self $other): bool
    {
        $this->ensureSameCurrency($other);

        return $this->amountCents > $other->amountCents;
    }

    public function isGreaterThanOrEqual(self $other): bool
    {
        $this->ensureSameCurrency($other);

        return $this->amountCents >= $other->amountCents;
    }

    public function isLessThan(self $other): bool
    {
        $this->ensureSameCurrency($other);

        return $this->amountCents < $other->amountCents;
    }

    public function equals(self $other): bool
    {
        return $this->amountCents === $other->amountCents
            && $this->currency    === $other->currency;
    }

    public function getAmountCents(): int
    {
        return $this->amountCents;
    }

    public function getAmount(): float
    {
        return $this->amountCents / 100;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function format(): string
    {
        return number_format($this->getAmount(), 2, ',', ' ').' '.$this->currency;
    }

    private function ensureSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(sprintf('Currency mismatch: %s vs %s', $this->currency, $other->currency));
        }
    }

    public function __toString(): string
    {
        return $this->format();
    }
}
