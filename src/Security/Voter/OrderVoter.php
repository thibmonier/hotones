<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Order;
use App\Entity\User;
use App\Enum\OrderStatus;

/**
 * Voter for Order (devis) entity.
 *
 * Attributes:
 *   - ORDER_VIEW   — any user of the same tenant
 *   - ORDER_EDIT   — ROLE_ADMIN | ROLE_MANAGER | ROLE_COMMERCIAL | ROLE_CHEF_PROJET, blocked when status is COMPLETED (frozen)
 *   - ORDER_SIGN   — ROLE_ADMIN | ROLE_MANAGER (only PENDING/WON can transition to SIGNED)
 *   - ORDER_DELETE — ROLE_ADMIN
 */
final class OrderVoter extends AbstractTenantAwareVoter
{
    public const string VIEW = 'ORDER_VIEW';
    public const string EDIT = 'ORDER_EDIT';
    public const string SIGN = 'ORDER_SIGN';
    public const string DELETE = 'ORDER_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::SIGN, self::DELETE], true)
        && $subject instanceof Order;
    }

    protected function voteOnRoleAndOwnership(string $attribute, mixed $subject, User $user): bool
    {
        if (!$subject instanceof Order) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => true,
            self::EDIT => $this->canEdit($subject, $user),
            self::SIGN => $this->canSign($subject, $user),
            self::DELETE => $this->userHasAnyRole($user, 'ROLE_ADMIN', 'ROLE_SUPERADMIN'),
            default => false,
        };
    }

    private function canEdit(Order $order, User $user): bool
    {
        // Frozen once completed — only superadmin can edit.
        if ($order->getStatus() === OrderStatus::COMPLETED->value) {
            return $this->userHasAnyRole($user, 'ROLE_SUPERADMIN');
        }

        return $this->userHasAnyRole(
            $user,
            'ROLE_ADMIN',
            'ROLE_MANAGER',
            'ROLE_COMMERCIAL',
            'ROLE_CHEF_PROJET',
            'ROLE_SUPERADMIN',
        );
    }

    private function canSign(Order $order, User $user): bool
    {
        if (!$this->userHasAnyRole($user, 'ROLE_ADMIN', 'ROLE_MANAGER', 'ROLE_SUPERADMIN')) {
            return false;
        }

        // Only PENDING (à signer) or WON can transition to SIGNED.
        return in_array($order->getStatus(), [OrderStatus::PENDING->value, OrderStatus::WON->value], true);
    }
}
