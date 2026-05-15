<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Invoice\Event;

use App\Domain\Invoice\Event\InvoiceIssuedEvent;
use App\Domain\Invoice\ValueObject\InvoiceId;
use App\Domain\Shared\Interface\DomainEventInterface;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class InvoiceIssuedEventTest extends TestCase
{
    public function testCreateBuildsEventWithFields(): void
    {
        $invoiceId = InvoiceId::generate();
        $issuedAt = new DateTimeImmutable('2026-05-12');
        $dueDate = new DateTimeImmutable('2026-06-12');

        $event = InvoiceIssuedEvent::create($invoiceId, $issuedAt, $dueDate);

        static::assertInstanceOf(DomainEventInterface::class, $event);
        static::assertSame($invoiceId, $event->getInvoiceId());
        static::assertSame($issuedAt, $event->getIssuedAt());
        static::assertSame($dueDate, $event->getDueDate());
    }

    public function testGetOccurredOnSetAtConstruction(): void
    {
        $event = InvoiceIssuedEvent::create(
            InvoiceId::generate(),
            new DateTimeImmutable('2026-05-12'),
            new DateTimeImmutable('2026-06-12'),
        );

        static::assertInstanceOf(DateTimeImmutable::class, $event->getOccurredOn());
    }
}
