<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Invoice\Event;

use App\Domain\Invoice\Event\InvoicePaidEvent;
use App\Domain\Invoice\ValueObject\InvoiceId;
use App\Domain\Shared\ValueObject\Money;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class InvoicePaidEventTest extends TestCase
{
    public function testCreate(): void
    {
        $invoiceId = InvoiceId::generate();
        $amount = Money::fromCents(10_000);
        $paidAt = new DateTimeImmutable('2026-05-07');

        $event = InvoicePaidEvent::create($invoiceId, $amount, $paidAt);

        static::assertEquals($invoiceId, $event->getInvoiceId());
        static::assertSame(10_000, $event->getAmountPaid()->getAmountCents());
        static::assertEquals($paidAt, $event->getPaidAt());
        static::assertNotNull($event->getOccurredOn());
    }
}
