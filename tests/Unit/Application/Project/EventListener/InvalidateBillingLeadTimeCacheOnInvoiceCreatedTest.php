<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Project\EventListener;

use App\Application\Project\EventListener\InvalidateBillingLeadTimeCacheOnInvoiceCreated;
use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Company\ValueObject\CompanyId;
use App\Domain\Invoice\Event\InvoiceCreatedEvent;
use App\Domain\Invoice\ValueObject\InvoiceId;
use App\Domain\Invoice\ValueObject\InvoiceNumber;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;

final class InvalidateBillingLeadTimeCacheOnInvoiceCreatedTest extends TestCase
{
    public function testClearsKpiCachePoolOnInvoiceCreatedEvent(): void
    {
        $pool = $this->createMock(CacheItemPoolInterface::class);
        $pool->expects(self::once())
            ->method('clear')
            ->willReturn(true);

        $handler = new InvalidateBillingLeadTimeCacheOnInvoiceCreated(kpiCache: $pool);

        $event = new InvoiceCreatedEvent(
            invoiceId: InvoiceId::fromLegacyInt(1),
            invoiceNumber: InvoiceNumber::fromString('F202605001'),
            companyId: CompanyId::fromLegacyInt(1),
            clientId: ClientId::fromLegacyInt(1),
        );

        $handler($event);
    }
}
