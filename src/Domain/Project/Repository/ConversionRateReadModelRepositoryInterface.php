<?php

declare(strict_types=1);

namespace App\Domain\Project\Repository;

use App\Domain\Project\Service\ClientConversionAggregate;
use App\Domain\Project\Service\OrderConversionRecord;
use DateTimeImmutable;

/**
 * Read-model port pour le calcul du taux de conversion (US-115).
 *
 * Retourne les Orders émis dans les 365 derniers jours, statuts contribuant
 * à la conversion uniquement (signe / gagne / perdu / abandonne).
 * a_signer / termine / standby exclus au niveau SQL.
 */
interface ConversionRateReadModelRepositoryInterface
{
    /**
     * @return list<OrderConversionRecord>
     */
    public function findConversionRecords(DateTimeImmutable $now): array;

    /**
     * Drill-down par client (US-119 T-119-01) — taux de conversion agrégé
     * par client sur la fenêtre de N jours. Tri par taux décroissant.
     *
     * @return list<ClientConversionAggregate>
     */
    public function findAllClientsAggregated(int $windowDays, DateTimeImmutable $now): array;
}
