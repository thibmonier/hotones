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
        $amount = Money::fromCents(10000);
        $paidAt = new DateTimeImmutable('2026-05-07');

        $event = InvoicePaidEvent::create($invoiceId, $amount, $paidAt);

        $this->assertEquals($invoiceId, $event->getInvoiceId());
        $this->assertSame(10000, $event->getAmountPaid()->getAmountCents());
        $this->assertEquals($paidAt, $event->getPaidAt());
        $this->assertNotNull($event->getOccurredOn());
    }
}
