<?php

declare(strict_types=1);

namespace App\Domain\Project\Service;

use DateTimeImmutable;

/**
 * Read-model DTO pour PortfolioMarginCalculator (US-117).
 *
 * Snapshot persistant US-107 : `coutTotalCents` + `factureTotalCents` +
 * `margeCalculatedAt` sur table `projects`. Marge % calculée à la volée :
 *   (factureTotalCents - coutTotalCents) / factureTotalCents × 100
 *
 * Domain pure — pas de référence Doctrine.
 */
final readonly class PortfolioMarginRecord
{
    public function __construct(
        public int $projectId,
        public string $projectName,
        public ?int $coutTotalCents,
        public ?int $factureTotalCents,
        public ?DateTimeImmutable $margeCalculatedAt,
    ) {
    }

    /**
     * True si le projet a un snapshot exploitable pour le portefeuille.
     */
    public function hasSnapshot(): bool
    {
        return $this->margeCalculatedAt !== null
            && $this->factureTotalCents !== null
            && $this->factureTotalCents > 0
            && $this->coutTotalCents !== null;
    }

    /**
     * Marge en % (signed). Returns 0.0 if snapshot incomplete.
     */
    public function marginPercent(): float
    {
        if (!$this->hasSnapshot()) {
            return 0.0;
        }

        // hasSnapshot garantit factureTotalCents > 0 et coutTotalCents !== null
        $margeCents = $this->factureTotalCents - $this->coutTotalCents;

        return ($margeCents / $this->factureTotalCents) * 100.0;
    }

    /**
     * Poids du projet dans le portefeuille (montant facture total).
     */
    public function weightCents(): int
    {
        return $this->factureTotalCents ?? 0;
    }
}
