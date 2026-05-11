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

        self::assertInstanceOf(InvalidInvoiceException::class, $exception);
        self::assertInstanceOf(DomainException::class, $exception);
        self::assertStringContainsString('line item', $exception->getMessage());
    }

    public function testInvalidStatusTransitionFactory(): void
    {
        $exception = InvalidInvoiceException::invalidStatusTransition(
            InvoiceStatus::PAID,
            InvoiceStatus::DRAFT,
        );

        self::assertInstanceOf(InvalidInvoiceException::class, $exception);
        self::assertStringContainsString('Cannot transition', $exception->getMessage());
    }

    public function testCannotModifyFinalizedInvoiceFactory(): void
    {
        $exception = InvalidInvoiceException::cannotModifyFinalizedInvoice();

        self::assertStringContainsString('Cannot modify', $exception->getMessage());
        self::assertStringContainsString('issued', $exception->getMessage());
    }

    public function testDueDateBeforeIssueDateFactory(): void
    {
        $exception = InvalidInvoiceException::dueDateBeforeIssueDate();

        self::assertStringContainsString('Due date', $exception->getMessage());
        self::assertStringContainsString('issue date', $exception->getMessage());
    }

    public function testInvalidPaymentAmountFactory(): void
    {
        $exception = InvalidInvoiceException::invalidPaymentAmount();

        self::assertStringContainsString('Payment amount', $exception->getMessage());
        self::assertStringContainsString('positive', $exception->getMessage());
    }

    public function testAlreadyPaidFactory(): void
    {
        $exception = InvalidInvoiceException::alreadyPaid();

        self::assertStringContainsString('already been paid', $exception->getMessage());
    }

    public function testCannotCancelPaidInvoiceFactory(): void
    {
        $exception = InvalidInvoiceException::cannotCancelPaidInvoice();

        self::assertStringContainsString('Cannot cancel', $exception->getMessage());
        self::assertStringContainsString('paid', $exception->getMessage());
    }
}
