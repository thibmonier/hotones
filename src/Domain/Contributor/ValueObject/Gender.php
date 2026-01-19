<?php

declare(strict_types=1);

namespace App\Domain\Contributor\ValueObject;

/**
 * Enum representing a contributor's gender.
 */
enum Gender: string
{
    case MALE          = 'male';
    case FEMALE        = 'female';
    case OTHER         = 'other';
    case NOT_SPECIFIED = 'not_specified';

    public function getLabel(): string
    {
        return match ($this) {
            self::MALE          => 'Homme',
            self::FEMALE        => 'Femme',
            self::OTHER         => 'Autre',
            self::NOT_SPECIFIED => 'Non spécifié',
        };
    }
}
