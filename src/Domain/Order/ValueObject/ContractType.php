<?php

declare(strict_types=1);

namespace App\Domain\Order\ValueObject;

enum ContractType: string
{
    case FIXED_PRICE       = 'forfait';
    case TIME_AND_MATERIAL = 'regie';

    public function getLabel(): string
    {
        return match ($this) {
            self::FIXED_PRICE       => 'Forfait',
            self::TIME_AND_MATERIAL => 'Régie',
        };
    }

    public function isFixedPrice(): bool
    {
        return $this === self::FIXED_PRICE;
    }

    public function isTimeAndMaterial(): bool
    {
        return $this === self::TIME_AND_MATERIAL;
    }
}
