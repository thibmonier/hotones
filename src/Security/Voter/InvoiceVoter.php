<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Invoice;
use App\Entity\User;

/**
 * Voter for Invoice entity.
 *
 * Attributes:
 *   - INVOICE_VIEW   — any user of the same tenant
 *   - INVOICE_EDIT   — ROLE_ADMIN | ROLE_MANAGER | ROLE_COMPTA, blocked once SENT/PAID (locked)
 *   - INVOICE_CANCEL — ROLE_ADMIN | ROLE_COMPTA, only on SENT or OVERDUE
 *   - INVOICE_DELETE — ROLE_ADMIN, only on DRAFT
 */
final class InvoiceVoter extends AbstractTenantAwareVoter
{
    public const string VIEW = 'INVOICE_VIEW';
    public const string EDIT = 'INVOICE_EDIT';
    public const string CANCEL = 'INVOICE_CANCEL';
    public const string DELETE = 'INVOICE_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::CANCEL, self::DELETE], true)
        && $subject instanceof Invoice;
    }

    protected function voteOnRoleAndOwnership(string $attribute, mixed $subject, User $user): bool
    {
        if (!$subject instanceof Invoice) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => true,
            self::EDIT => $this->canEdit($subject, $user),
            self::CANCEL => $this->canCancel($subject, $user),
            self::DELETE => $this->canDelete($subject, $user),
            default => false,
        };
    }

    private function canEdit(Invoice $invoice, User $user): bool
    {
        // Lock once invoice is sent (legal/accounting integrity).
        $locked = in_array(
            $invoice->getStatus(),
            [Invoice::STATUS_SENT, Invoice::STATUS_PAID, Invoice::STATUS_OVERDUE, Invoice::STATUS_CANCELLED],
            true,
        );
        if ($locked) {
            return $this->userHasAnyRole($user, 'ROLE_SUPERADMIN');
        }

        return $this->userHasAnyRole($user, 'ROLE_ADMIN', 'ROLE_MANAGER', 'ROLE_COMPTA', 'ROLE_SUPERADMIN');
    }

    private function canCancel(Invoice $invoice, User $user): bool
    {
        if (!$this->userHasAnyRole($user, 'ROLE_ADMIN', 'ROLE_COMPTA', 'ROLE_SUPERADMIN')) {
            return false;
        }

        return in_array($invoice->getStatus(), [Invoice::STATUS_SENT, Invoice::STATUS_OVERDUE], true);
    }

    private function canDelete(Invoice $invoice, User $user): bool
    {
        if (!$this->userHasAnyRole($user, 'ROLE_ADMIN', 'ROLE_SUPERADMIN')) {
            return false;
        }

        // Only delete drafts; sent invoices must be cancelled (audit trail).
        return $invoice->getStatus() === Invoice::STATUS_DRAFT;
    }
}
