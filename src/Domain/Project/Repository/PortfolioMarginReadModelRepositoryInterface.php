<?php

declare(strict_types=1);

namespace App\Domain\Project\Repository;

use App\Domain\Project\Service\PortfolioMarginRecord;
use DateTimeImmutable;

/**
 * Read-model port pour le calcul de la marge moyenne portefeuille (US-117 T-117-02).
 *
 * Retourne les Projects actifs (`status = 'active'`) du tenant courant avec
 * leur snapshot marge persisté (US-107) : `coutTotalCents`, `factureTotalCents`,
 * `margeCalculatedAt`. Les projets sans snapshot sont retournés pour visibilité
 * PO (comptés séparément par {@see PortfolioMarginCalculator}).
 *
 * Projets `completed` / `cancelled` exclus au niveau SQL.
 *
 * `$now` est utilisé par le cache decorator pour la clé journalière ; la query
 * Doctrine est time-agnostic (snapshot état courant).
 */
interface PortfolioMarginReadModelRepositoryInterface
{
    /**
     * @return list<PortfolioMarginRecord>
     */
    public function findActiveProjectsWithSnapshot(DateTimeImmutable $now): array;
}
