<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Domain\Vacation\Entity\Vacation;
use App\Domain\Vacation\ValueObject\VacationStatus;
use App\Entity\User;

/**
 * Voter for the DDD Vacation aggregate.
 *
 * Attributes:
 *   - VACATION_VIEW    — owner | manager | ROLE_ADMIN | ROLE_RH
 *   - VACATION_REQUEST — owner can request (any tenant member)
 *   - VACATION_APPROVE — manager hierarchy, NOT the owner (separation of duties)
 *   - VACATION_REJECT  — manager hierarchy, NOT the owner
 *   - VACATION_CANCEL  — owner (before APPROVED) | manager
 */
final class VacationVoter extends AbstractTenantAwareVoter
{
    public const string VIEW = 'VACATION_VIEW';
    public const string REQUEST = 'VACATION_REQUEST';
    public const string APPROVE = 'VACATION_APPROVE';
    public const string REJECT = 'VACATION_REJECT';
    public const string CANCEL = 'VACATION_CANCEL';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::REQUEST, self::APPROVE, self::REJECT, self::CANCEL], true)
        && $subject instanceof Vacation;
    }

    protected function voteOnRoleAndOwnership(string $attribute, mixed $subject, User $user): bool
    {
        if (!$subject instanceof Vacation) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => $this->canView($subject, $user),
            self::REQUEST => true,
            self::APPROVE, self::REJECT => $this->canApproveOrReject($subject, $user),
            self::CANCEL => $this->canCancel($subject, $user),
            default => false,
        };
    }

    private function isOwner(Vacation $vacation, User $user): bool
    {
        $contributorUser = $vacation->getContributor()->getUser();

        return $contributorUser !== null && $contributorUser->getId() === $user->getId();
    }

    private function canView(Vacation $vacation, User $user): bool
    {
        if ($this->isOwner($vacation, $user)) {
            return true;
        }

        return $this->userHasAnyRole($user, 'ROLE_CHEF_PROJET', 'ROLE_MANAGER', 'ROLE_ADMIN', 'ROLE_SUPERADMIN');
    }

    private function canApproveOrReject(Vacation $vacation, User $user): bool
    {
        if ($this->isOwner($vacation, $user)) {
            return false; // Cannot self-approve.
        }

        return $this->userHasAnyRole($user, 'ROLE_MANAGER', 'ROLE_ADMIN', 'ROLE_SUPERADMIN');
    }

    private function canCancel(Vacation $vacation, User $user): bool
    {
        // Manager can always cancel.
        if ($this->userHasAnyRole($user, 'ROLE_MANAGER', 'ROLE_ADMIN', 'ROLE_SUPERADMIN')) {
            return true;
        }

        // Owner can cancel only before APPROVED status.
        if ($this->isOwner($vacation, $user)) {
            return $vacation->getStatus() !== VacationStatus::APPROVED;
        }

        return false;
    }
}
