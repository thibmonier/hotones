<?php

declare(strict_types=1);

namespace App\Domain\Project\Service;

use App\Domain\Project\ValueObject\ConversionRate;
use App\Domain\Project\ValueObject\ConversionTrend;
use DateTimeImmutable;

/**
 * Conversion rate calculator — domaine pure, sans dépendance Doctrine (US-115).
 *
 * Formule : taux = count(Orders signe + gagne) / count(Orders émis hors standby)
 * sur la fenêtre rolling [now - N jours, now[.
 *
 * Tendance 30j : delta vs fenêtre précédente [now - 60j, now - 30j[.
 *
 * Pattern KpiCalculator sprint-024 (DSO + lead time + margin adoption +
 * revenue forecast).
 */
final readonly class ConversionRateCalculator
{
    private const float TREND_STABILITY_DELTA_PERCENT = 1.0;

    /**
     * @param iterable<OrderConversionRecord> $records
     */
    public function calculate(
        iterable $records,
        DateTimeImmutable $now,
    ): ConversionRate {
        // Matérialise une fois — $records peut être un Generator non rembobinable.
        $pipeline = [];
        foreach ($records as $record) {
            $pipeline[] = $record;
        }

        $stats30 = $this->statsForWindow($pipeline, 30, $now);
        $stats90 = $this->statsForWindow($pipeline, 90, $now);
        $stats365 = $this->statsForWindow($pipeline, 365, $now);

        $previousWindowEnd = $now->modify('-30 days');
        $statsPrevious30 = $this->statsForWindow($pipeline, 30, $previousWindowEnd);

        return ConversionRate::create(
            rate30Percent: $stats30['rate'],
            rate90Percent: $stats90['rate'],
            rate365Percent: $stats365['rate'],
            emitted30Count: $stats30['emitted'],
            converted30Count: $stats30['converted'],
            trend30: $this->computeTrend($stats30['rate'], $statsPrevious30['rate']),
        );
    }

    /**
     * @param list<OrderConversionRecord> $pipeline
     *
     * @return array{rate: float, emitted: int, converted: int}
     */
    private function statsForWindow(
        array $pipeline,
        int $windowDays,
        DateTimeImmutable $windowEnd,
    ): array {
        $windowStart = $windowEnd->modify(sprintf('-%d days', $windowDays));

        $emitted = 0;
        $converted = 0;

        foreach ($pipeline as $record) {
            if (!$record->isInWindow($windowStart, $windowEnd)) {
                continue;
            }

            if (!$record->contributesToConversion()) {
                continue;
            }

            ++$emitted;
            if ($record->isConverted()) {
                ++$converted;
            }
        }

        $rate = $emitted > 0 ? ($converted / $emitted) * 100.0 : 0.0;

        return [
            'rate' => $rate,
            'emitted' => $emitted,
            'converted' => $converted,
        ];
    }

    private function computeTrend(float $current, float $previous): ConversionTrend
    {
        $delta = $current - $previous;

        if (abs($delta) < self::TREND_STABILITY_DELTA_PERCENT) {
            return ConversionTrend::Stable;
        }

        return $delta > 0 ? ConversionTrend::Up : ConversionTrend::Down;
    }
}
