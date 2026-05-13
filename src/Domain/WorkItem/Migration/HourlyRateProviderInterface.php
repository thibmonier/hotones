<?php

declare(strict_types=1);

namespace App\Domain\WorkItem\Migration;

use DateTimeImmutable;

/**
 * Port DDD : résout le taux horaire d'un contributeur à une date donnée
 * (US-113 T-113-02).
 *
 * Suit la chaîne EmploymentPeriod actif au {@code $workDate}, puis fallback
 * `Contributor.hourlyRate` direct. Renvoie `null` si aucune source disponible
 * (Contributor inactif sans rate) — le migrator flagge alors `legacy_no_rate`.
 *
 * Pattern aligné `App\Domain\Project\Repository\*ReadModelRepositoryInterface`.
 */
interface HourlyRateProviderInterface
{
    /**
     * @return int|null cents/hour (rate × 100). Null si pas de rate disponible.
     */
    public function resolveAt(int $contributorId, DateTimeImmutable $workDate): ?int;
}
