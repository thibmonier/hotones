<?php

declare(strict_types=1);

namespace App\Domain\User\ValueObject;

/**
 * User role enum with hierarchy support.
 *
 * Role hierarchy (lowest to highest):
 * INTERVENANT → CHEF_PROJET → MANAGER → SUPERADMIN
 */
enum UserRole: string
{
    case INTERVENANT = 'ROLE_INTERVENANT';
    case CHEF_PROJET = 'ROLE_CHEF_PROJET';
    case MANAGER     = 'ROLE_MANAGER';
    case SUPERADMIN  = 'ROLE_SUPERADMIN';

    /**
     * Get the numeric level of this role (higher = more permissions).
     */
    public function getLevel(): int
    {
        return match ($this) {
            self::INTERVENANT => 1,
            self::CHEF_PROJET => 2,
            self::MANAGER     => 3,
            self::SUPERADMIN  => 4,
        };
    }

    /**
     * Check if this role has at least the same level as another role.
     */
    public function hasAtLeast(self $role): bool
    {
        return $this->getLevel() >= $role->getLevel();
    }

    /**
     * Check if this role is higher than another role.
     */
    public function isHigherThan(self $role): bool
    {
        return $this->getLevel() > $role->getLevel();
    }

    /**
     * Get all inherited roles (including self).
     *
     * @return array<string>
     */
    public function getInheritedRoles(): array
    {
        $roles = ['ROLE_USER'];

        foreach (self::cases() as $case) {
            if ($this->hasAtLeast($case)) {
                $roles[] = $case->value;
            }
        }

        return array_unique($roles);
    }

    /**
     * Check if role can be promoted to another role.
     */
    public function canPromoteTo(self $newRole): bool
    {
        return $newRole->isHigherThan($this);
    }

    /**
     * Check if role can be demoted to another role.
     */
    public function canDemoteTo(self $newRole): bool
    {
        return $this->isHigherThan($newRole);
    }

    /**
     * Get the human-readable label.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::INTERVENANT => 'Intervenant',
            self::CHEF_PROJET => 'Chef de Projet',
            self::MANAGER     => 'Manager',
            self::SUPERADMIN  => 'Super Administrateur',
        };
    }

    /**
     * Get the default role for new users.
     */
    public static function default(): self
    {
        return self::INTERVENANT;
    }
}
