<?php

declare(strict_types=1);

namespace App\Domain\Project\Service;

use App\Domain\Project\ValueObject\PortfolioMargin;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Portfolio margin calculator — domaine pure, sans dépendance Doctrine (US-117).
 *
 * Formule pondérée :
 *   marge_portefeuille = Σ(marge_proj × factureTotal_proj) / Σ(factureTotal_proj)
 *   sur projets actifs avec snapshot non null (`hasSnapshot()`).
 *
 * Projets sans snapshot ou inactifs (filtrés au niveau Repository) sont
 * comptés séparément pour visibilité PO.
 *
 * Pattern KpiCalculator sprint-024/025 — 7ᵉ application consécutive.
 */
final readonly class PortfolioMarginCalculator
{
    /**
     * @param iterable<PortfolioMarginRecord> $records
     * @param float                           $targetMarginPercent Seuil cible pour breakdown above/below (défaut 20 %)
     */
    public function calculate(
        iterable $records,
        DateTimeImmutable $now,
        float $targetMarginPercent = 20.0,
    ): PortfolioMargin {
        if ($targetMarginPercent < -100.0 || $targetMarginPercent > 100.0) {
            throw new InvalidArgumentException('Target margin percent must be in [-100, 100]');
        }

        $weightedSum = 0.0;
        $totalWeight = 0;
        $withSnapshot = 0;
        $withoutSnapshot = 0;
        $aboveTarget = 0;
        $belowTarget = 0;

        foreach ($records as $record) {
            if (!$record->hasSnapshot()) {
                ++$withoutSnapshot;

                continue;
            }

            ++$withSnapshot;

            $marginPercent = $record->marginPercent();
            $weight = $record->weightCents();

            $weightedSum += $marginPercent * $weight;
            $totalWeight += $weight;

            if ($marginPercent >= $targetMarginPercent) {
                ++$aboveTarget;
            } else {
                ++$belowTarget;
            }
        }

        $averagePercent = $totalWeight > 0 ? $weightedSum / $totalWeight : 0.0;

        return PortfolioMargin::create(
            averagePercent: $averagePercent,
            projectsWithSnapshot: $withSnapshot,
            projectsWithoutSnapshot: $withoutSnapshot,
            projectsAboveTarget: $aboveTarget,
            projectsBelowTarget: $belowTarget,
        );
    }
}
