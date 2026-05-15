<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Invoice\Exception;

use App\Domain\Invoice\Exception\InvoiceNotFoundException;
use App\Domain\Invoice\ValueObject\InvoiceId;
use App\Domain\Invoice\ValueObject\InvoiceNumber;
use App\Domain\Shared\Exception\DomainException;
use PHPUnit\Framework\TestCase;

final class InvoiceNotFoundExceptionTest extends TestCase
{
    public function testWithIdFactory(): void
    {
        $invoiceId = InvoiceId::generate();

        $exception = InvoiceNotFoundException::withId($invoiceId);

        static::assertInstanceOf(InvoiceNotFoundException::class, $exception);
        static::assertInstanceOf(DomainException::class, $exception);
        static::assertStringContainsString($invoiceId->getValue(), $exception->getMessage());
        static::assertStringContainsString('not found', $exception->getMessage());
    }

    public function testWithNumberFactory(): void
    {
        $invoiceNumber = InvoiceNumber::generate(2026, 5, 1);

        $exception = InvoiceNotFoundException::withNumber($invoiceNumber);

        static::assertInstanceOf(InvoiceNotFoundException::class, $exception);
        static::assertStringContainsString($invoiceNumber->getValue(), $exception->getMessage());
        static::assertStringContainsString('not found', $exception->getMessage());
    }
}
