<?php

namespace App\Enum;

enum OrderStatus: string
{
    case PENDING   = 'a_signer';  // À signer
    case WON       = 'gagne';         // Gagné
    case SIGNED    = 'signe';      // Signé
    case LOST      = 'perdu';        // Perdu
    case COMPLETED = 'termine'; // Terminé
    case STANDBY   = 'standby';   // Standby
    case ABANDONED = 'abandonne'; // Abandonné

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING   => 'À signer',
            self::WON       => 'Gagné',
            self::SIGNED    => 'Signé',
            self::LOST      => 'Perdu',
            self::COMPLETED => 'Terminé',
            self::STANDBY   => 'Standby',
            self::ABANDONED => 'Abandonné',
        };
    }

    public static function fromString(string $status): ?self
    {
        return match ($status) {
            'a_signer'  => self::PENDING,
            'gagne'     => self::WON,
            'signe'     => self::SIGNED,
            'perdu'     => self::LOST,
            'termine'   => self::COMPLETED,
            'standby'   => self::STANDBY,
            'abandonne' => self::ABANDONED,
            default     => null,
        };
    }
}
