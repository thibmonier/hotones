<?php

declare(strict_types=1);

namespace App\Domain\Project\Service;

use App\Domain\Project\ValueObject\RevenueForecast;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Revenue forecast calculator — domaine pure, sans dépendance Doctrine (US-114).
 *
 * Formule :
 *   forecast = Σ(commandes confirmées, montant 100 %)
 *            + Σ(devis a_signer, montant × coefficient probabilité)
 *
 * - Horizon : seules les commandes dont `validUntil` tombe dans
 *   [now, now + horizonDays] sont comptées (forecast 30 j et 90 j)
 * - Statuts perdu / abandonne / standby / termine exclus
 * - Commandes sans échéance exclues
 *
 * Pattern KpiCalculator sprint-024 (Domain Service pure testable Unit).
 */
final readonly class RevenueForecastCalculator
{
    private const HORIZON_30_DAYS = 30;
    private const HORIZON_90_DAYS = 90;

    /**
     * @param iterable<PipelineOrderRecord> $records
     * @param float                         $probabilityCoefficient Pondération devis a_signer (0.0–1.0)
     */
    public function calculate(
        iterable $records,
        float $probabilityCoefficient,
        DateTimeImmutable $now,
    ): RevenueForecast {
        if ($probabilityCoefficient < 0.0 || $probabilityCoefficient > 1.0) {
            throw new InvalidArgumentException('Probability coefficient must be between 0.0 and 1.0');
        }

        // Matérialise une fois — `$records` peut être un Generator non rembobinable.
        $pipeline = [];
        foreach ($records as $record) {
            if (!$record->contributesToForecast()) {
                continue;
            }

            $pipeline[] = $record;
        }

        $forecast30 = $this->sumHorizon($pipeline, self::HORIZON_30_DAYS, $probabilityCoefficient, $now);
        $forecast90 = $this->sumHorizon($pipeline, self::HORIZON_90_DAYS, $probabilityCoefficient, $now);

        return RevenueForecast::create(
            forecast30Cents: $forecast30['total'],
            forecast90Cents: $forecast90['total'],
            confirmedCents: $forecast90['confirmed'],
            weightedQuotesCents: $forecast90['weighted'],
        );
    }

    /**
     * @param list<PipelineOrderRecord> $pipeline
     *
     * @return array{total: int, confirmed: int, weighted: int}
     */
    private function sumHorizon(
        array $pipeline,
        int $horizonDays,
        float $probabilityCoefficient,
        DateTimeImmutable $now,
    ): array {
        $confirmedCents = 0;
        $weightedCents = 0;

        foreach ($pipeline as $record) {
            if (!$record->isWithinHorizon($horizonDays, $now)) {
                continue;
            }

            if ($record->isConfirmed()) {
                $confirmedCents += $record->amountCents;

                continue;
            }

            // isQuote() — garanti par contributesToForecast()
            $weightedCents += (int) round($record->amountCents * $probabilityCoefficient);
        }

        return [
            'total' => $confirmedCents + $weightedCents,
            'confirmed' => $confirmedCents,
            'weighted' => $weightedCents,
        ];
    }
}
