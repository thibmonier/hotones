<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Project\EventListener;

use App\Application\Project\EventListener\InvalidateDsoCacheOnInvoicePaid;
use App\Domain\Invoice\Event\InvoicePaidEvent;
use App\Domain\Invoice\ValueObject\InvoiceId;
use App\Domain\Shared\ValueObject\Money;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;

final class InvalidateDsoCacheOnInvoicePaidTest extends TestCase
{
    public function testClearsKpiCachePoolOnInvoicePaidEvent(): void
    {
        $pool = $this->createMock(CacheItemPoolInterface::class);
        $pool->expects(self::once())
            ->method('clear')
            ->willReturn(true);

        $handler = new InvalidateDsoCacheOnInvoicePaid(kpiCache: $pool);

        $event = new InvoicePaidEvent(
            invoiceId: InvoiceId::fromLegacyInt(1),
            amountPaid: Money::fromCents(50_000),
            paidAt: new DateTimeImmutable('2026-05-12'),
        );

        $handler($event);
    }
}
