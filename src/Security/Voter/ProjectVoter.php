<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Project;
use App\Entity\User;

/**
 * Voter for Project entity. Tenant + role + ownership triplet.
 *
 * Attributes:
 *   - PROJECT_VIEW   — any user of the same tenant
 *   - PROJECT_EDIT   — ROLE_ADMIN | ROLE_MANAGER | ROLE_CHEF_PROJET assigned as PM
 *   - PROJECT_DELETE — ROLE_ADMIN only
 */
final class ProjectVoter extends AbstractTenantAwareVoter
{
    public const string VIEW = 'PROJECT_VIEW';
    public const string EDIT = 'PROJECT_EDIT';
    public const string DELETE = 'PROJECT_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE], true) && $subject instanceof Project;
    }

    protected function voteOnRoleAndOwnership(string $attribute, mixed $subject, User $user): bool
    {
        if (!$subject instanceof Project) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => true, // Any tenant member sees the project list.
            self::EDIT => $this->canEdit($subject, $user),
            self::DELETE => $this->userHasAnyRole($user, 'ROLE_ADMIN', 'ROLE_SUPERADMIN'),
            default => false,
        };
    }

    private function canEdit(Project $project, User $user): bool
    {
        if ($this->userHasAnyRole($user, 'ROLE_ADMIN', 'ROLE_MANAGER', 'ROLE_SUPERADMIN')) {
            return true;
        }

        if (!$this->userHasAnyRole($user, 'ROLE_CHEF_PROJET')) {
            return false;
        }

        // Chef de projet can edit only if assigned as the project's PM.
        $manager = $project->getProjectManager();

        return $manager !== null && $manager->getId() === $user->getId();
    }
}
