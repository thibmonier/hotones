<?php

declare(strict_types=1);

namespace App\Domain\Invoice\ValueObject;

/**
 * Invoice status enumeration.
 *
 * Workflow: DRAFT -> SENT -> PAID
 *                 -> OVERDUE -> PAID
 *                 -> CANCELLED
 */
enum InvoiceStatus: string
{
    case DRAFT     = 'brouillon';
    case SENT      = 'envoyee';
    case PAID      = 'payee';
    case OVERDUE   = 'en_retard';
    case CANCELLED = 'annulee';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT     => 'Brouillon',
            self::SENT      => 'Envoyée',
            self::PAID      => 'Payée',
            self::OVERDUE   => 'En retard',
            self::CANCELLED => 'Annulée',
        };
    }

    /**
     * Check if invoice can still be modified.
     */
    public function isEditable(): bool
    {
        return $this === self::DRAFT;
    }

    /**
     * Check if invoice is finalized (sent or later).
     */
    public function isFinalized(): bool
    {
        return in_array($this, [self::SENT, self::PAID, self::OVERDUE], true);
    }

    /**
     * Check if invoice is closed (paid or cancelled).
     */
    public function isClosed(): bool
    {
        return in_array($this, [self::PAID, self::CANCELLED], true);
    }

    /**
     * Check if invoice is awaiting payment.
     */
    public function isAwaitingPayment(): bool
    {
        return in_array($this, [self::SENT, self::OVERDUE], true);
    }

    /**
     * Check if transition to new status is allowed.
     */
    public function canTransitionTo(self $newStatus): bool
    {
        return match ($this) {
            self::DRAFT   => in_array($newStatus, [self::SENT, self::CANCELLED], true),
            self::SENT    => in_array($newStatus, [self::PAID, self::OVERDUE, self::CANCELLED], true),
            self::OVERDUE => in_array($newStatus, [self::PAID, self::CANCELLED], true),
            self::PAID, self::CANCELLED => false, // Terminal states
        };
    }
}
