<?php

declare(strict_types=1);

namespace App\Domain\Project\ValueObject;

/**
 * Project type enum (fixed price vs time & materials).
 */
enum ProjectType: string
{
    case FORFAIT = 'forfait';
    case REGIE = 'regie';

    public function isFixedPrice(): bool
    {
        return $this === self::FORFAIT;
    }

    public function isTimeAndMaterials(): bool
    {
        return $this === self::REGIE;
    }

    public function label(): string
    {
        return match ($this) {
            self::FORFAIT => 'Forfait',
            self::REGIE => 'Régie',
        };
    }
}
