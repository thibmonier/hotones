<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\ExpenseReport;
use App\Entity\User;

/**
 * Voter for ExpenseReport entity.
 *
 * Attributes:
 *   - EXPENSE_VIEW    — owner | manager | ROLE_COMPTA | ROLE_ADMIN
 *   - EXPENSE_EDIT    — owner only, blocked once SUBMITTED
 *   - EXPENSE_SUBMIT  — owner only, only on DRAFT
 *   - EXPENSE_APPROVE — manager | ROLE_COMPTA | ROLE_ADMIN, NOT the owner
 *   - EXPENSE_REJECT  — same as APPROVE
 *   - EXPENSE_DELETE  — owner only on DRAFT, otherwise ROLE_ADMIN
 */
final class ExpenseReportVoter extends AbstractTenantAwareVoter
{
    public const string VIEW = 'EXPENSE_VIEW';
    public const string EDIT = 'EXPENSE_EDIT';
    public const string SUBMIT = 'EXPENSE_SUBMIT';
    public const string APPROVE = 'EXPENSE_APPROVE';
    public const string REJECT = 'EXPENSE_REJECT';
    public const string DELETE = 'EXPENSE_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array(
            $attribute,
            [self::VIEW, self::EDIT, self::SUBMIT, self::APPROVE, self::REJECT, self::DELETE],
            true,
        )
        && $subject instanceof ExpenseReport;
    }

    protected function voteOnRoleAndOwnership(string $attribute, mixed $subject, User $user): bool
    {
        if (!$subject instanceof ExpenseReport) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => $this->canView($subject, $user),
            self::EDIT => $this->canEdit($subject, $user),
            self::SUBMIT => $this->canSubmit($subject, $user),
            self::APPROVE, self::REJECT => $this->canApproveOrReject($subject, $user),
            self::DELETE => $this->canDelete($subject, $user),
            default => false,
        };
    }

    private function isOwner(ExpenseReport $report, User $user): bool
    {
        $contributorUser = $report->getContributor()->getUser();

        return $contributorUser !== null && $contributorUser->getId() === $user->getId();
    }

    private function canView(ExpenseReport $report, User $user): bool
    {
        if ($this->isOwner($report, $user)) {
            return true;
        }

        return $this->userHasAnyRole($user, 'ROLE_MANAGER', 'ROLE_COMPTA', 'ROLE_ADMIN', 'ROLE_SUPERADMIN');
    }

    private function canEdit(ExpenseReport $report, User $user): bool
    {
        if (!$this->isOwner($report, $user)) {
            return false;
        }

        return $report->getStatus() === ExpenseReport::STATUS_DRAFT;
    }

    private function canSubmit(ExpenseReport $report, User $user): bool
    {
        if (!$this->isOwner($report, $user)) {
            return false;
        }

        return $report->getStatus() === ExpenseReport::STATUS_DRAFT;
    }

    private function canApproveOrReject(ExpenseReport $report, User $user): bool
    {
        if ($this->isOwner($report, $user)) {
            return false; // Separation of duties.
        }

        if (!$this->userHasAnyRole($user, 'ROLE_MANAGER', 'ROLE_COMPTA', 'ROLE_ADMIN', 'ROLE_SUPERADMIN')) {
            return false;
        }

        return $report->getStatus() === ExpenseReport::STATUS_PENDING;
    }

    private function canDelete(ExpenseReport $report, User $user): bool
    {
        // Owner can delete only DRAFT.
        if ($this->isOwner($report, $user)) {
            return $report->getStatus() === ExpenseReport::STATUS_DRAFT;
        }

        return $this->userHasAnyRole($user, 'ROLE_ADMIN', 'ROLE_SUPERADMIN');
    }
}
