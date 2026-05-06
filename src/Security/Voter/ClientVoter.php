<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Client;
use App\Entity\User;

/**
 * Voter for Client (CRM) entity.
 *
 * Attributes:
 *   - CLIENT_VIEW   — any tenant member
 *   - CLIENT_EDIT   — ROLE_ADMIN | ROLE_MANAGER | ROLE_COMMERCIAL | ROLE_CHEF_PROJET
 *   - CLIENT_DELETE — ROLE_ADMIN
 */
final class ClientVoter extends AbstractTenantAwareVoter
{
    public const string VIEW = 'CLIENT_VIEW';
    public const string EDIT = 'CLIENT_EDIT';
    public const string DELETE = 'CLIENT_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE], true)
            && $subject instanceof Client;
    }

    protected function voteOnRoleAndOwnership(string $attribute, mixed $subject, User $user): bool
    {
        return match ($attribute) {
            self::VIEW => true,
            self::EDIT => $this->userHasAnyRole(
                $user,
                'ROLE_ADMIN',
                'ROLE_MANAGER',
                'ROLE_COMMERCIAL',
                'ROLE_CHEF_PROJET',
                'ROLE_SUPERADMIN',
            ),
            self::DELETE => $this->userHasAnyRole($user, 'ROLE_ADMIN', 'ROLE_SUPERADMIN'),
            default => false,
        };
    }
}
