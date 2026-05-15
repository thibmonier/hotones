<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Invoice\Exception;

use App\Domain\Invoice\Exception\InvalidInvoiceException;
use App\Domain\Invoice\ValueObject\InvoiceStatus;
use App\Domain\Shared\Exception\DomainException;
use PHPUnit\Framework\TestCase;

final class InvalidInvoiceExceptionTest extends TestCase
{
    public function testEmptyLinesFactory(): void
    {
        $exception = InvalidInvoiceException::emptyLines();

        static::assertInstanceOf(InvalidInvoiceException::class, $exception);
        static::assertInstanceOf(DomainException::class, $exception);
        static::assertStringContainsString('line item', $exception->getMessage());
    }

    public function testInvalidStatusTransitionFactory(): void
    {
        $exception = InvalidInvoiceException::invalidStatusTransition(
            InvoiceStatus::PAID,
            InvoiceStatus::DRAFT,
        );

        static::assertInstanceOf(InvalidInvoiceException::class, $exception);
        static::assertStringContainsString('Cannot transition', $exception->getMessage());
    }

    public function testCannotModifyFinalizedInvoiceFactory(): void
    {
        $exception = InvalidInvoiceException::cannotModifyFinalizedInvoice();

        static::assertStringContainsString('Cannot modify', $exception->getMessage());
        static::assertStringContainsString('issued', $exception->getMessage());
    }

    public function testDueDateBeforeIssueDateFactory(): void
    {
        $exception = InvalidInvoiceException::dueDateBeforeIssueDate();

        static::assertStringContainsString('Due date', $exception->getMessage());
        static::assertStringContainsString('issue date', $exception->getMessage());
    }

    public function testInvalidPaymentAmountFactory(): void
    {
        $exception = InvalidInvoiceException::invalidPaymentAmount();

        static::assertStringContainsString('Payment amount', $exception->getMessage());
        static::assertStringContainsString('positive', $exception->getMessage());
    }

    public function testAlreadyPaidFactory(): void
    {
        $exception = InvalidInvoiceException::alreadyPaid();

        static::assertStringContainsString('already been paid', $exception->getMessage());
    }

    public function testCannotCancelPaidInvoiceFactory(): void
    {
        $exception = InvalidInvoiceException::cannotCancelPaidInvoice();

        static::assertStringContainsString('Cannot cancel', $exception->getMessage());
        static::assertStringContainsString('paid', $exception->getMessage());
    }
}
