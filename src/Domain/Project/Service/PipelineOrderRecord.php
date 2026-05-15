<?php

declare(strict_types=1);

namespace App\Domain\Project\Service;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Read-model DTO for revenue forecast calculation (US-114).
 *
 * Domain pure — pas de référence Doctrine. Le Repository hydrate cette
 * structure depuis l'aggregate Order.
 *
 * Statuts Order (cf App\Entity\Order::STATUS_OPTIONS) :
 * - `a_signer`        → devis non signé (pondéré par coefficient probabilité)
 * - `signe` / `gagne` → commande confirmée (comptée à 100 %)
 * - `perdu` / `abandonne` / `standby` / `termine` → exclus du forecast
 */
final readonly class PipelineOrderRecord
{
    public const STATUS_QUOTE = 'a_signer';
    public const STATUS_SIGNED = 'signe';
    public const STATUS_WON = 'gagne';

    public function __construct(
        public string $status,
        public int $amountCents,
        public ?DateTimeImmutable $validUntil,
    ) {
        if ($amountCents < 0) {
            throw new InvalidArgumentException('Order amount cannot be negative');
        }
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_SIGNED
            || $this->status === self::STATUS_WON;
    }

    public function isQuote(): bool
    {
        return $this->status === self::STATUS_QUOTE;
    }

    /**
     * True si la commande contribue au forecast (devis ou confirmée).
     * Les statuts perdu / abandonne / standby / termine sont exclus.
     */
    public function contributesToForecast(): bool
    {
        return $this->isConfirmed() || $this->isQuote();
    }

    /**
     * True si l'échéance tombe dans l'horizon [now, now + horizonDays].
     * Les commandes sans échéance ou déjà échues sont exclues.
     */
    public function isWithinHorizon(int $horizonDays, DateTimeImmutable $now): bool
    {
        if ($this->validUntil === null) {
            return false;
        }

        $horizonEnd = $now->modify(sprintf('+%d days', $horizonDays));

        return $this->validUntil >= $now && $this->validUntil <= $horizonEnd;
    }
}
