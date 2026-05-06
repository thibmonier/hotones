<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Contributor;
use App\Entity\User;

/**
 * Voter for Contributor (HR) entity.
 *
 * Attributes:
 *   - CONTRIBUTOR_VIEW   — self | manager hierarchy | ROLE_RH | ROLE_ADMIN
 *   - CONTRIBUTOR_EDIT   — self (limited fields, enforced at form level) | ROLE_MANAGER | ROLE_ADMIN
 *   - CONTRIBUTOR_DEACTIVATE — ROLE_MANAGER | ROLE_ADMIN, NOT self
 *   - CONTRIBUTOR_DELETE — ROLE_ADMIN only (rare; soft delete preferred)
 */
final class ContributorVoter extends AbstractTenantAwareVoter
{
    public const string VIEW = 'CONTRIBUTOR_VIEW';
    public const string EDIT = 'CONTRIBUTOR_EDIT';
    public const string DEACTIVATE = 'CONTRIBUTOR_DEACTIVATE';
    public const string DELETE = 'CONTRIBUTOR_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DEACTIVATE, self::DELETE], true)
            && $subject instanceof Contributor;
    }

    protected function voteOnRoleAndOwnership(string $attribute, mixed $subject, User $user): bool
    {
        if (!$subject instanceof Contributor) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => $this->canView($subject, $user),
            self::EDIT => $this->canEdit($subject, $user),
            self::DEACTIVATE => $this->canDeactivate($subject, $user),
            self::DELETE => $this->userHasAnyRole($user, 'ROLE_ADMIN', 'ROLE_SUPERADMIN'),
            default => false,
        };
    }

    private function isSelf(Contributor $contributor, User $user): bool
    {
        $contributorUser = $contributor->getUser();

        return $contributorUser !== null && $contributorUser->getId() === $user->getId();
    }

    private function canView(Contributor $contributor, User $user): bool
    {
        if ($this->isSelf($contributor, $user)) {
            return true;
        }

        return $this->userHasAnyRole(
            $user,
            'ROLE_CHEF_PROJET',
            'ROLE_MANAGER',
            'ROLE_ADMIN',
            'ROLE_COMPTA',
            'ROLE_SUPERADMIN',
        );
    }

    private function canEdit(Contributor $contributor, User $user): bool
    {
        if ($this->isSelf($contributor, $user)) {
            return true; // Self-edit allowed; field-level restrictions enforced via Form.
        }

        return $this->userHasAnyRole($user, 'ROLE_MANAGER', 'ROLE_ADMIN', 'ROLE_SUPERADMIN');
    }

    private function canDeactivate(Contributor $contributor, User $user): bool
    {
        if ($this->isSelf($contributor, $user)) {
            return false; // Cannot self-deactivate.
        }

        return $this->userHasAnyRole($user, 'ROLE_MANAGER', 'ROLE_ADMIN', 'ROLE_SUPERADMIN');
    }
}
