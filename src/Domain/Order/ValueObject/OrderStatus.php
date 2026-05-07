<?php

declare(strict_types=1);

namespace App\Domain\Order\ValueObject;

enum OrderStatus: string
{
    case DRAFT = 'draft';
    case TO_SIGN = 'a_signer';
    case WON = 'gagne';
    case SIGNED = 'signe';
    case LOST = 'perdu';
    case COMPLETED = 'termine';
    case STANDBY = 'standby';
    case ABANDONED = 'abandonne';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::TO_SIGN => 'À signer',
            self::WON => 'Gagné',
            self::SIGNED => 'Signé',
            self::LOST => 'Perdu',
            self::COMPLETED => 'Terminé',
            self::STANDBY => 'En attente',
            self::ABANDONED => 'Abandonné',
        };
    }

    public function isActive(): bool
    {
        return in_array(
            $this,
            [
                self::DRAFT,
                self::TO_SIGN,
                self::WON,
                self::SIGNED,
                self::STANDBY,
            ],
            true,
        );
    }

    public function isClosed(): bool
    {
        return in_array(
            $this,
            [
                self::LOST,
                self::COMPLETED,
                self::ABANDONED,
            ],
            true,
        );
    }

    public function canTransitionTo(self $newStatus): bool
    {
        $allowedTransitions = match ($this) {
            self::DRAFT => [self::TO_SIGN, self::ABANDONED],
            self::TO_SIGN => [self::WON, self::LOST, self::STANDBY, self::ABANDONED],
            self::WON => [self::SIGNED, self::LOST, self::STANDBY],
            self::SIGNED => [self::COMPLETED, self::STANDBY, self::ABANDONED],
            self::STANDBY => [self::TO_SIGN, self::WON, self::SIGNED, self::ABANDONED],
            self::LOST, self::COMPLETED, self::ABANDONED => [],
        };

        return in_array($newStatus, $allowedTransitions, true);
    }
}
