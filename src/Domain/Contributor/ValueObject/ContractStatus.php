<?php

declare(strict_types=1);

namespace App\Domain\Contributor\ValueObject;

enum ContractStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';

    public function getLabel(): string
    {
        return match ($this) {
            self::ACTIVE => 'Actif',
            self::INACTIVE => 'Inactif',
        };
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }
}
