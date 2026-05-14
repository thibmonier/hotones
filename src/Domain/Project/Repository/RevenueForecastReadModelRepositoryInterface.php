<?php

declare(strict_types=1);

namespace App\Domain\Project\Repository;

use App\Domain\Project\Service\PipelineOrderRecord;
use DateTimeImmutable;

/**
 * Read-model port for revenue forecast (US-114).
 *
 * Retourne le pipeline d'Orders contribuant au forecast — statuts
 * `a_signer` / `signe` / `gagne`, échéance dans l'horizon [now, now + 90 j],
 * commandes déjà couvertes par une facture exclues (pas de double comptage).
 */
interface RevenueForecastReadModelRepositoryInterface
{
    /**
     * @return list<PipelineOrderRecord>
     */
    public function findPipelineOrders(DateTimeImmutable $now): array;
}
