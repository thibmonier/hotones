<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Project\Service;

use App\Domain\Project\Service\DsoCalculator;
use App\Domain\Project\Service\InvoicePaymentRecord;
use App\Domain\Project\ValueObject\DsoDays;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class DsoCalculatorTest extends TestCase
{
    private DsoCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new DsoCalculator();
    }

    public function testEmptyCollectionReturnsZero(): void
    {
        $now = new DateTimeImmutable('2026-05-11');

        $result = $this->calculator->calculateRolling([], 30, $now);

        static::assertSame(0.0, $result->getDays());
    }

    public function testSingleInvoicePaidIn10Days(): void
    {
        $now = new DateTimeImmutable('2026-05-11');
        $invoices = [
            new InvoicePaymentRecord(
                issuedAt: new DateTimeImmutable('2026-05-01'),
                paidAt: new DateTimeImmutable('2026-05-11'),
                amountPaidCents: 10_000,
            ),
        ];

        $result = $this->calculator->calculateRolling($invoices, 30, $now);

        static::assertSame(10.0, $result->getDays());
    }

    public function testMultipleInvoicesWeightedByAmount(): void
    {
        $now = new DateTimeImmutable('2026-05-11');
        $invoices = [
            // 10 days × 100€
            new InvoicePaymentRecord(
                issuedAt: new DateTimeImmutable('2026-05-01'),
                paidAt: new DateTimeImmutable('2026-05-11'),
                amountPaidCents: 10_000,
            ),
            // 20 days × 200€ (double weight)
            new InvoicePaymentRecord(
                issuedAt: new DateTimeImmutable('2026-04-21'),
                paidAt: new DateTimeImmutable('2026-05-11'),
                amountPaidCents: 20_000,
            ),
        ];

        // Weighted: (10*10000 + 20*20000) / (10000+20000) = 500000/30000 = 16.666...
        $result = $this->calculator->calculateRolling($invoices, 30, $now);

        static::assertSame(16.7, $result->getDays());
    }

    public function testUnpaidInvoicesExcluded(): void
    {
        $now = new DateTimeImmutable('2026-05-11');
        $invoices = [
            new InvoicePaymentRecord(
                issuedAt: new DateTimeImmutable('2026-05-01'),
                paidAt: new DateTimeImmutable('2026-05-11'),
                amountPaidCents: 10_000,
            ),
            // Unpaid (paidAt null) — must be excluded
            new InvoicePaymentRecord(
                issuedAt: new DateTimeImmutable('2026-04-01'),
                paidAt: null,
                amountPaidCents: 50_000,
            ),
        ];

        $result = $this->calculator->calculateRolling($invoices, 30, $now);

        // Only first invoice counts: 10 days
        static::assertSame(10.0, $result->getDays());
    }

    public function testInvoicesOutsideWindowExcluded(): void
    {
        $now = new DateTimeImmutable('2026-05-11');
        $invoices = [
            // In window (paid 5 days ago)
            new InvoicePaymentRecord(
                issuedAt: new DateTimeImmutable('2026-04-26'),
                paidAt: new DateTimeImmutable('2026-05-06'),
                amountPaidCents: 10_000,
            ),
            // Outside 30-day window (paid 40 days ago)
            new InvoicePaymentRecord(
                issuedAt: new DateTimeImmutable('2026-03-22'),
                paidAt: new DateTimeImmutable('2026-04-01'),
                amountPaidCents: 50_000,
            ),
        ];

        $result = $this->calculator->calculateRolling($invoices, 30, $now);

        // Only first invoice (10 days)
        static::assertSame(10.0, $result->getDays());
    }

    public function testZeroAmountInvoicesExcluded(): void
    {
        $now = new DateTimeImmutable('2026-05-11');
        $invoices = [
            new InvoicePaymentRecord(
                issuedAt: new DateTimeImmutable('2026-05-01'),
                paidAt: new DateTimeImmutable('2026-05-11'),
                amountPaidCents: 10_000,
            ),
            // Zero amount — must be excluded (no contribution to weighted average)
            new InvoicePaymentRecord(
                issuedAt: new DateTimeImmutable('2026-05-01'),
                paidAt: new DateTimeImmutable('2026-05-06'),
                amountPaidCents: 0,
            ),
        ];

        $result = $this->calculator->calculateRolling($invoices, 30, $now);

        static::assertSame(10.0, $result->getDays());
    }

    public function testReturnsDsoDaysValueObject(): void
    {
        $now = new DateTimeImmutable('2026-05-11');
        $invoices = [
            new InvoicePaymentRecord(
                issuedAt: new DateTimeImmutable('2026-05-01'),
                paidAt: new DateTimeImmutable('2026-05-11'),
                amountPaidCents: 10_000,
            ),
        ];

        $result = $this->calculator->calculateRolling($invoices, 30, $now);

        static::assertInstanceOf(DsoDays::class, $result);
    }
}
