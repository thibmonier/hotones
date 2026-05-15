<?php

declare(strict_types=1);

namespace App\Domain\Project\Service;

use DateTimeImmutable;

/**
 * Read-model DTO for conversion rate calculation (US-115).
 *
 * Domain pure — pas de référence Doctrine.
 *
 * Statuts Order (cf {@see \App\Entity\Order::STATUS_OPTIONS}) :
 * - converti  : `signe` / `gagne`
 * - échec     : `perdu` / `abandonne`
 * - exclu     : `standby` (en attente, pas un échec)
 * - exclu     : `termine` (post-conversion, déjà compté)
 * - exclu     : `a_signer` (en cours — dénominateur de pipeline, pas de conversion)
 */
final readonly class OrderConversionRecord
{
    public const STATUS_CONVERTED_SIGNED = 'signe';
    public const STATUS_CONVERTED_WON = 'gagne';
    public const STATUS_FAILED_LOST = 'perdu';
    public const STATUS_FAILED_ABANDONED = 'abandonne';
    public const STATUS_EXCLUDED_STANDBY = 'standby';

    public function __construct(
        public string $status,
        public DateTimeImmutable $createdAt,
    ) {
    }

    public function isConverted(): bool
    {
        return $this->status === self::STATUS_CONVERTED_SIGNED
            || $this->status === self::STATUS_CONVERTED_WON;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED_LOST
            || $this->status === self::STATUS_FAILED_ABANDONED;
    }

    /**
     * Contribue au dénominateur du taux de conversion.
     * Exclut `standby` (en attente) et `termine` / `a_signer` (hors décision).
     */
    public function contributesToConversion(): bool
    {
        return $this->isConverted() || $this->isFailed();
    }

    public function isInWindow(DateTimeImmutable $windowStart, DateTimeImmutable $windowEnd): bool
    {
        return $this->createdAt >= $windowStart && $this->createdAt < $windowEnd;
    }
}
