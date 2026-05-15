<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Invoice\Event;

use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Company\ValueObject\CompanyId;
use App\Domain\Invoice\Event\InvoiceCreatedEvent;
use App\Domain\Invoice\ValueObject\InvoiceId;
use App\Domain\Invoice\ValueObject\InvoiceNumber;
use PHPUnit\Framework\TestCase;

final class InvoiceCreatedEventTest extends TestCase
{
    public function testCreate(): void
    {
        $invoiceId = InvoiceId::generate();
        $number = InvoiceNumber::fromString('F202601001');
        $companyId = CompanyId::fromLegacyInt(1);
        $clientId = ClientId::fromLegacyInt(42);

        $event = InvoiceCreatedEvent::create($invoiceId, $number, $companyId, $clientId);

        static::assertEquals($invoiceId, $event->getInvoiceId());
        static::assertEquals($number, $event->getInvoiceNumber());
        static::assertEquals($companyId, $event->getCompanyId());
        static::assertEquals($clientId, $event->getClientId());
        static::assertNotNull($event->getOccurredOn());
    }
}
