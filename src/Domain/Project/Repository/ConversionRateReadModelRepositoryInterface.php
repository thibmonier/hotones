<?php

declare(strict_types=1);

namespace App\Domain\Project\Repository;

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
}
