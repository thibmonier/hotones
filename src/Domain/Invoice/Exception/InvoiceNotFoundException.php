<?php

declare(strict_types=1);

namespace App\Domain\Invoice\Exception;

use App\Domain\Invoice\ValueObject\InvoiceId;
use App\Domain\Invoice\ValueObject\InvoiceNumber;
use App\Domain\Shared\Exception\DomainException;

/**
 * Exception thrown when an invoice cannot be found.
 */
final class InvoiceNotFoundException extends DomainException
{
    public static function withId(InvoiceId $invoiceId): self
    {
        return new self(
            sprintf('Invoice with ID "%s" was not found.', $invoiceId->getValue()),
        );
    }

    public static function withNumber(InvoiceNumber $invoiceNumber): self
    {
        return new self(
            sprintf('Invoice with number "%s" was not found.', $invoiceNumber->getValue()),
        );
    }
}
