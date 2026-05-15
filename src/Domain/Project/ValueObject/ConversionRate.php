<?php

declare(strict_types=1);

namespace App\Domain\Project\ValueObject;

use InvalidArgumentException;

/**
 * Conversion rate value object (US-115).
 *
 * Taux de conversion devis → commande sur fenêtres glissantes 30 / 90 / 365 j
 * + tendance vs fenêtre précédente.
 *
 * `ratePercent` ∈ [0, 100]. Si dénominateur = 0 → rate = 0.0 (pipeline vide).
 */
final readonly class ConversionRate
{
    private function __construct(
        private float $rate30Percent,
        private float $rate90Percent,
        private float $rate365Percent,
        private int $emitted30Count,
        private int $converted30Count,
        private ConversionTrend $trend30,
    ) {
        foreach ([$rate30Percent, $rate90Percent, $rate365Percent] as $rate) {
            if ($rate < 0.0 || $rate > 100.0) {
                throw new InvalidArgumentException('Conversion rate must be between 0 and 100');
            }
        }

        if ($emitted30Count < 0 || $converted30Count < 0) {
            throw new InvalidArgumentException('Counts cannot be negative');
        }
    }

    public static function create(
        float $rate30Percent,
        float $rate90Percent,
        float $rate365Percent,
        int $emitted30Count,
        int $converted30Count,
        ConversionTrend $trend30,
    ): self {
        return new self(
            round($rate30Percent, 1),
            round($rate90Percent, 1),
            round($rate365Percent, 1),
            $emitted30Count,
            $converted30Count,
            $trend30,
        );
    }

    public static function zero(): self
    {
        return new self(0.0, 0.0, 0.0, 0, 0, ConversionTrend::Stable);
    }

    public function getRate30Percent(): float
    {
        return $this->rate30Percent;
    }

    public function getRate90Percent(): float
    {
        return $this->rate90Percent;
    }

    public function getRate365Percent(): float
    {
        return $this->rate365Percent;
    }

    public function getEmitted30Count(): int
    {
        return $this->emitted30Count;
    }

    public function getConverted30Count(): int
    {
        return $this->converted30Count;
    }

    public function getTrend30(): ConversionTrend
    {
        return $this->trend30;
    }
}
