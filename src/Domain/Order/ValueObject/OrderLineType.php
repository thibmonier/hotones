<?php

declare(strict_types=1);

namespace App\Domain\Order\ValueObject;

/**
 * Enum representing the type of an order line.
 */
enum OrderLineType: string
{
    case SERVICE = 'service';
    case PURCHASE = 'purchase';
    case FIXED_AMOUNT = 'fixed_amount';

    public function getLabel(): string
    {
        return match ($this) {
            self::SERVICE => 'Service',
            self::PURCHASE => 'Achat',
            self::FIXED_AMOUNT => 'Montant fixe',
        };
    }

    public function isService(): bool
    {
        return $this === self::SERVICE;
    }

    public function isPurchase(): bool
    {
        return $this === self::PURCHASE;
    }

    public function isFixedAmount(): bool
    {
        return $this === self::FIXED_AMOUNT;
    }
}
