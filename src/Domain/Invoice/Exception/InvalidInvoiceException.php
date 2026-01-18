<?php

declare(strict_types=1);

namespace App\Domain\Invoice\Exception;

use App\Domain\Invoice\ValueObject\InvoiceStatus;
use App\Domain\Shared\Exception\DomainException;

/**
 * Exception thrown when invoice validation fails.
 */
final class InvalidInvoiceException extends DomainException
{
    public static function emptyLines(): self
    {
        return new self('Invoice must have at least one line item.');
    }

    public static function invalidStatusTransition(InvoiceStatus $from, InvoiceStatus $to): self
    {
        return new self(
            sprintf(
                'Cannot transition invoice status from "%s" to "%s".',
                $from->getLabel(),
                $to->getLabel()
            )
        );
    }

    public static function cannotModifyFinalizedInvoice(): self
    {
        return new self('Cannot modify an invoice that has already been issued.');
    }

    public static function dueDateBeforeIssueDate(): self
    {
        return new self('Due date cannot be before the issue date.');
    }

    public static function invalidPaymentAmount(): self
    {
        return new self('Payment amount must be positive.');
    }

    public static function alreadyPaid(): self
    {
        return new self('Invoice has already been paid.');
    }

    public static function cannotCancelPaidInvoice(): self
    {
        return new self('Cannot cancel an invoice that has been paid.');
    }
}
