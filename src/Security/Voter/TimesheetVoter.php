<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Timesheet;
use App\Entity\User;

/**
 * Voter for Timesheet entity.
 *
 * Attributes:
 *   - TIMESHEET_VIEW     — owner | ROLE_CHEF_PROJET+ (any tenant member)
 *   - TIMESHEET_EDIT     — owner only, blocked when status = approved/locked
 *   - TIMESHEET_DELETE   — owner | ROLE_ADMIN, blocked when approved
 *   - TIMESHEET_VALIDATE — ROLE_CHEF_PROJET | ROLE_MANAGER | ROLE_ADMIN (not the owner)
 */
final class TimesheetVoter extends AbstractTenantAwareVoter
{
    public const string VIEW = 'TIMESHEET_VIEW';
    public const string EDIT = 'TIMESHEET_EDIT';
    public const string DELETE = 'TIMESHEET_DELETE';
    public const string VALIDATE = 'TIMESHEET_VALIDATE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE, self::VALIDATE], true)
            && $subject instanceof Timesheet;
    }

    protected function voteOnRoleAndOwnership(string $attribute, mixed $subject, User $user): bool
    {
        if (!$subject instanceof Timesheet) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => $this->canView($subject, $user),
            self::EDIT => $this->canEdit($subject, $user),
            self::DELETE => $this->canDelete($subject, $user),
            self::VALIDATE => $this->canValidate($subject, $user),
            default => false,
        };
    }

    private function isOwner(Timesheet $timesheet, User $user): bool
    {
        $contributor = $timesheet->getContributor();
        $contributorUser = $contributor->getUser();

        return $contributorUser !== null && $contributorUser->getId() === $user->getId();
    }

    private function canView(Timesheet $timesheet, User $user): bool
    {
        // Owner or tenant management can view.
        if ($this->isOwner($timesheet, $user)) {
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

    private function canEdit(Timesheet $timesheet, User $user): bool
    {
        if (!$this->isOwner($timesheet, $user) && !$this->userHasAnyRole($user, 'ROLE_ADMIN', 'ROLE_SUPERADMIN')) {
            return false;
        }

        // Cannot edit a validated timesheet (data integrity for billing).
        return !$this->isValidated($timesheet);
    }

    private function canDelete(Timesheet $timesheet, User $user): bool
    {
        if (!$this->isOwner($timesheet, $user) && !$this->userHasAnyRole($user, 'ROLE_ADMIN', 'ROLE_SUPERADMIN')) {
            return false;
        }

        return !$this->isValidated($timesheet);
    }

    private function canValidate(Timesheet $timesheet, User $user): bool
    {
        // Cannot self-validate (separation of duties).
        if ($this->isOwner($timesheet, $user)) {
            return false;
        }

        return $this->userHasAnyRole(
            $user,
            'ROLE_CHEF_PROJET',
            'ROLE_MANAGER',
            'ROLE_ADMIN',
            'ROLE_SUPERADMIN',
        );
    }

    private function isValidated(Timesheet $timesheet): bool
    {
        if (!method_exists($timesheet, 'getStatus')) {
            return false;
        }

        $status = $timesheet->getStatus();

        return in_array($status, ['validated', 'approved', 'locked'], true);
    }
}
