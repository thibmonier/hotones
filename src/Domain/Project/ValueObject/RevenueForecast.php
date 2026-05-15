<?php

declare(strict_types=1);

namespace App\Domain\Project\ValueObject;

use InvalidArgumentException;

/**
 * Revenue forecast value object (US-114).
 *
 * Forecast pondéré sur 2 horizons glissants (30 / 90 jours) :
 *   forecast = Σ(commandes confirmées) + Σ(devis × coefficient probabilité)
 *
 * `confirmedCents` + `weightedQuotesCents` décomposent le forecast 90 j
 * (horizon le plus large) pour l'affichage widget.
 */
final readonly class RevenueForecast
{
    private function __construct(
        private int $forecast30Cents,
        private int $forecast90Cents,
        private int $confirmedCents,
        private int $weightedQuotesCents,
    ) {
        if ($forecast30Cents < 0 || $forecast90Cents < 0
            || $confirmedCents < 0 || $weightedQuotesCents < 0) {
            throw new InvalidArgumentException('Forecast amounts cannot be negative');
        }
    }

    public static function create(
        int $forecast30Cents,
        int $forecast90Cents,
        int $confirmedCents,
        int $weightedQuotesCents,
    ): self {
        return new self($forecast30Cents, $forecast90Cents, $confirmedCents, $weightedQuotesCents);
    }

    public static function zero(): self
    {
        return new self(0, 0, 0, 0);
    }

    public function getForecast30Cents(): int
    {
        return $this->forecast30Cents;
    }

    public function getForecast90Cents(): int
    {
        return $this->forecast90Cents;
    }

    public function getForecast30Euros(): float
    {
        return $this->forecast30Cents / 100;
    }

    public function getForecast90Euros(): float
    {
        return $this->forecast90Cents / 100;
    }

    public function getConfirmedCents(): int
    {
        return $this->confirmedCents;
    }

    public function getWeightedQuotesCents(): int
    {
        return $this->weightedQuotesCents;
    }

    public function equals(self $other): bool
    {
        return $this->forecast30Cents === $other->forecast30Cents
            && $this->forecast90Cents === $other->forecast90Cents
            && $this->confirmedCents === $other->confirmedCents
            && $this->weightedQuotesCents === $other->weightedQuotesCents;
    }
}
