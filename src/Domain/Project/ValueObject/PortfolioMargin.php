<?php

declare(strict_types=1);

namespace App\Domain\Project\ValueObject;

use InvalidArgumentException;

/**
 * Portfolio margin value object (US-117).
 *
 * Marge moyenne pondérée par montant facture total :
 *   marge_portefeuille = Σ(marge_proj × factureTotal_proj) / Σ(factureTotal_proj)
 *
 * `averagePercent` peut être négatif (perte). Cap à [-100, 100] pour
 * éviter outliers (taux > 100 % théorique invalide).
 */
final readonly class PortfolioMargin
{
    private function __construct(
        private float $averagePercent,
        private int $projectsWithSnapshot,
        private int $projectsWithoutSnapshot,
        private int $projectsAboveTarget,
        private int $projectsBelowTarget,
    ) {
        if ($projectsWithSnapshot < 0 || $projectsWithoutSnapshot < 0
            || $projectsAboveTarget < 0 || $projectsBelowTarget < 0) {
            throw new InvalidArgumentException('Project counts cannot be negative');
        }
    }

    public static function create(
        float $averagePercent,
        int $projectsWithSnapshot,
        int $projectsWithoutSnapshot,
        int $projectsAboveTarget,
        int $projectsBelowTarget,
    ): self {
        return new self(
            round(max(-100.0, min(100.0, $averagePercent)), 1),
            $projectsWithSnapshot,
            $projectsWithoutSnapshot,
            $projectsAboveTarget,
            $projectsBelowTarget,
        );
    }

    public static function zero(): self
    {
        return new self(0.0, 0, 0, 0, 0);
    }

    public function getAveragePercent(): float
    {
        return $this->averagePercent;
    }

    public function getProjectsWithSnapshot(): int
    {
        return $this->projectsWithSnapshot;
    }

    public function getProjectsWithoutSnapshot(): int
    {
        return $this->projectsWithoutSnapshot;
    }

    public function getProjectsAboveTarget(): int
    {
        return $this->projectsAboveTarget;
    }

    public function getProjectsBelowTarget(): int
    {
        return $this->projectsBelowTarget;
    }

    public function totalActiveProjects(): int
    {
        return $this->projectsWithSnapshot + $this->projectsWithoutSnapshot;
    }
}
