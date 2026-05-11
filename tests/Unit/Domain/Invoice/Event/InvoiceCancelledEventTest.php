<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Invoice\Event;

use App\Domain\Invoice\Event\InvoiceCancelledEvent;
use App\Domain\Invoice\ValueObject\InvoiceId;
use App\Domain\Shared\Interface\DomainEventInterface;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class InvoiceCancelledEventTest extends TestCase
{
    public function testCreateBuildsEventWithFields(): void
    {
        $invoiceId = InvoiceId::generate();
        $reason = 'Client request';

        $event = InvoiceCancelledEvent::create($invoiceId, $reason);

        self::assertInstanceOf(DomainEventInterface::class, $event);
        self::assertSame($invoiceId, $event->getInvoiceId());
        self::assertSame($reason, $event->getReason());
    }

    public function testGetOccurredOnSetAtConstruction(): void
    {
        $event = InvoiceCancelledEvent::create(InvoiceId::generate(), 'reason');

        self::assertInstanceOf(DateTimeImmutable::class, $event->getOccurredOn());
    }
}
