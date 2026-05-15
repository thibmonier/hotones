<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Invoice\Entity;

use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Company\ValueObject\CompanyId;
use App\Domain\Invoice\Entity\Invoice;
use App\Domain\Invoice\Event\InvoiceCancelledEvent;
use App\Domain\Invoice\Event\InvoiceCreatedEvent;
use App\Domain\Invoice\Event\InvoiceIssuedEvent;
use App\Domain\Invoice\Event\InvoicePaidEvent;
use App\Domain\Invoice\Exception\InvalidInvoiceException;
use App\Domain\Invoice\ValueObject\InvoiceId;
use App\Domain\Invoice\ValueObject\InvoiceLineId;
use App\Domain\Invoice\ValueObject\InvoiceNumber;
use App\Domain\Invoice\ValueObject\InvoiceStatus;
use App\Domain\Invoice\ValueObject\TaxRate;
use App\Domain\Order\ValueObject\OrderId;
use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\Shared\ValueObject\Money;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * TEST-COVERAGE-007 (sprint-018) — coverage Domain Invoice Aggregate Root.
 *
 * Couvre :
 * - Factory `create` + reconstitute
 * - Lines management (add / update / remove + recalcul totaux)
 * - Status transitions (issue / markAsPaid / markAsOverdue / cancel)
 * - Editable invariants (cannot modify after issue)
 * - Query methods (isOverdue / getDaysUntilDue)
 * - Domain events recordings
 */
final class InvoiceTest extends TestCase
{
    public function testCreateInitializesDraftAndZeroAmounts(): void
    {
        $invoice = $this->newInvoice();

        static::assertSame(InvoiceStatus::DRAFT, $invoice->getStatus());
        static::assertTrue($invoice->getAmountHt()->equals(Money::zero()));
        static::assertTrue($invoice->getAmountTva()->equals(Money::zero()));
        static::assertTrue($invoice->getAmountTtc()->equals(Money::zero()));
        static::assertNull($invoice->getIssuedAt());
        static::assertNull($invoice->getDueDate());
        static::assertNull($invoice->getPaidAt());
    }

    public function testCreateRecordsInvoiceCreatedEvent(): void
    {
        $invoice = $this->newInvoice();
        $events = $invoice->pullDomainEvents();

        static::assertCount(1, $events);
        static::assertInstanceOf(InvoiceCreatedEvent::class, $events[0]);
    }

    public function testReconstituteDoesNotRecordEvents(): void
    {
        $invoice = Invoice::reconstitute(
            InvoiceId::fromLegacyInt(7),
            InvoiceNumber::fromString('F202601001'),
            CompanyId::fromLegacyInt(1),
            ClientId::fromLegacyInt(2),
        );

        static::assertSame([], $invoice->pullDomainEvents());
    }

    public function testAddLineRecalculatesTotals(): void
    {
        $invoice = $this->newInvoice();
        $invoice->pullDomainEvents(); // drain

        $invoice->addLine(
            InvoiceLineId::generate(),
            'Web design',
            quantity: 2.0,
            unitPriceHt: Money::fromAmount(100.0),
            taxRate: TaxRate::standardFrance(), // 20 %
        );

        // 2 * 100 = 200 HT, TVA 20 % = 40, TTC = 240
        static::assertSame(20_000, $invoice->getAmountHt()->getAmountCents());
        static::assertSame(4000, $invoice->getAmountTva()->getAmountCents());
        static::assertSame(24_000, $invoice->getAmountTtc()->getAmountCents());
    }

    public function testAddMultipleLinesAggregateTotals(): void
    {
        $invoice = $this->newInvoice();

        $invoice->addLine(
            InvoiceLineId::generate(),
            'Line A',
            1.0,
            Money::fromAmount(100.0),
            TaxRate::standardFrance(),
        );
        $invoice->addLine(
            InvoiceLineId::generate(),
            'Line B',
            3.0,
            Money::fromAmount(50.0),
            TaxRate::standardFrance(),
        );

        // (100 + 150) HT = 250, TVA 20 % = 50, TTC = 300
        static::assertSame(25_000, $invoice->getAmountHt()->getAmountCents());
        static::assertSame(5000, $invoice->getAmountTva()->getAmountCents());
        static::assertSame(30_000, $invoice->getAmountTtc()->getAmountCents());
    }

    public function testUpdateLineMutatesTotals(): void
    {
        $invoice = $this->newInvoice();
        $lineId = InvoiceLineId::generate();
        $invoice->addLine($lineId, 'Initial', 1.0, Money::fromAmount(100.0), TaxRate::standardFrance());

        $invoice->updateLine($lineId, 'Updated', 2.0, Money::fromAmount(150.0), TaxRate::standardFrance());

        // 2 * 150 = 300 HT, TVA 60, TTC 360
        static::assertSame(30_000, $invoice->getAmountHt()->getAmountCents());
        static::assertSame(36_000, $invoice->getAmountTtc()->getAmountCents());
    }

    public function testRemoveLineRecalculatesTotals(): void
    {
        $invoice = $this->newInvoice();
        $keep = InvoiceLineId::generate();
        $remove = InvoiceLineId::generate();
        $invoice->addLine($keep, 'Keep', 1.0, Money::fromAmount(100.0), TaxRate::standardFrance());
        $invoice->addLine($remove, 'Remove', 1.0, Money::fromAmount(50.0), TaxRate::standardFrance());

        $invoice->removeLine($remove);

        static::assertSame(10_000, $invoice->getAmountHt()->getAmountCents());
    }

    public function testRemoveUnknownLineThrows(): void
    {
        $invoice = $this->newInvoice();

        $this->expectException(InvalidArgumentException::class);
        $invoice->removeLine(InvoiceLineId::generate());
    }

    public function testIssueTransitionsToSentAndRecordsEvent(): void
    {
        $invoice = $this->newInvoice();
        $invoice->addLine(InvoiceLineId::generate(), 'X', 1.0, Money::fromAmount(50.0), TaxRate::standardFrance());
        $invoice->pullDomainEvents();

        $issuedAt = new DateTimeImmutable('2026-01-01');
        $dueDate = new DateTimeImmutable('2026-01-31');
        $invoice->issue($issuedAt, $dueDate);

        static::assertSame(InvoiceStatus::SENT, $invoice->getStatus());
        static::assertEquals($issuedAt, $invoice->getIssuedAt());
        static::assertEquals($dueDate, $invoice->getDueDate());

        $events = $invoice->pullDomainEvents();
        static::assertCount(1, $events);
        static::assertInstanceOf(InvoiceIssuedEvent::class, $events[0]);
    }

    public function testIssueWithoutLinesThrows(): void
    {
        $invoice = $this->newInvoice();

        $this->expectException(InvalidInvoiceException::class);
        $invoice->issue(new DateTimeImmutable(), new DateTimeImmutable('+30 days'));
    }

    public function testIssueWithDueBeforeIssuedThrows(): void
    {
        $invoice = $this->newInvoice();
        $invoice->addLine(InvoiceLineId::generate(), 'X', 1.0, Money::fromAmount(50.0), TaxRate::standardFrance());

        $this->expectException(InvalidInvoiceException::class);
        $invoice->issue(new DateTimeImmutable('2026-01-31'), new DateTimeImmutable('2026-01-01'));
    }

    public function testCannotAddLineAfterIssue(): void
    {
        $invoice = $this->issuedInvoice();

        $this->expectException(InvalidInvoiceException::class);
        $invoice->addLine(InvoiceLineId::generate(), 'Y', 1.0, Money::fromAmount(10.0), TaxRate::standardFrance());
    }

    public function testMarkAsPaidTransitions(): void
    {
        $invoice = $this->issuedInvoice();
        $invoice->pullDomainEvents();

        $paidAt = new DateTimeImmutable('2026-01-15');
        $invoice->markAsPaid($paidAt, Money::fromAmount(60.0));

        static::assertSame(InvoiceStatus::PAID, $invoice->getStatus());
        static::assertEquals($paidAt, $invoice->getPaidAt());

        $events = $invoice->pullDomainEvents();
        static::assertCount(1, $events);
        static::assertInstanceOf(InvoicePaidEvent::class, $events[0]);
    }

    public function testMarkAsPaidWithZeroAmountThrows(): void
    {
        $invoice = $this->issuedInvoice();

        $this->expectException(InvalidInvoiceException::class);
        $invoice->markAsPaid(new DateTimeImmutable(), Money::zero());
    }

    public function testCannotMarkDraftAsPaid(): void
    {
        $invoice = $this->newInvoice();

        $this->expectException(InvalidInvoiceException::class);
        $invoice->markAsPaid(new DateTimeImmutable(), Money::fromAmount(10.0));
    }

    public function testMarkAsOverdueFromSent(): void
    {
        $invoice = $this->issuedInvoice();
        $invoice->markAsOverdue();

        static::assertSame(InvoiceStatus::OVERDUE, $invoice->getStatus());
    }

    public function testCannotMarkPaidAsOverdue(): void
    {
        $invoice = $this->issuedInvoice();
        $invoice->markAsPaid(new DateTimeImmutable(), Money::fromAmount(60.0));

        $this->expectException(InvalidInvoiceException::class);
        $invoice->markAsOverdue();
    }

    public function testCancelDraftRecordsEvent(): void
    {
        $invoice = $this->newInvoice();
        $invoice->pullDomainEvents();

        $invoice->cancel('client mistake');

        static::assertSame(InvoiceStatus::CANCELLED, $invoice->getStatus());
        $events = $invoice->pullDomainEvents();
        static::assertCount(1, $events);
        static::assertInstanceOf(InvoiceCancelledEvent::class, $events[0]);
    }

    public function testCancelSentRecordsEvent(): void
    {
        $invoice = $this->issuedInvoice();
        $invoice->cancel('client refund');

        static::assertSame(InvoiceStatus::CANCELLED, $invoice->getStatus());
    }

    public function testCannotCancelPaidInvoice(): void
    {
        $invoice = $this->issuedInvoice();
        $invoice->markAsPaid(new DateTimeImmutable(), Money::fromAmount(60.0));

        $this->expectException(InvalidInvoiceException::class);
        $invoice->cancel('mistake');
    }

    public function testCannotCancelTwice(): void
    {
        $invoice = $this->newInvoice();
        $invoice->cancel('reason 1');

        $this->expectException(InvalidInvoiceException::class);
        $invoice->cancel('reason 2');
    }

    public function testSetNotesAndPaymentTerms(): void
    {
        $invoice = $this->newInvoice();

        $invoice->setNotes('Internal note');
        $invoice->setPaymentTerms('Net 30');

        static::assertSame('Internal note', $invoice->getNotes());
        static::assertSame('Net 30', $invoice->getPaymentTerms());
    }

    public function testCannotSetNotesAfterIssue(): void
    {
        $invoice = $this->issuedInvoice();

        $this->expectException(InvalidInvoiceException::class);
        $invoice->setNotes('Late note');
    }

    public function testIsOverdueTrueWhenDueDatePast(): void
    {
        $invoice = $this->newInvoice();
        $invoice->addLine(InvoiceLineId::generate(), 'X', 1.0, Money::fromAmount(50.0), TaxRate::standardFrance());
        $invoice->issue(
            new DateTimeImmutable('-60 days'),
            new DateTimeImmutable('-30 days'),
        );

        static::assertTrue($invoice->isOverdue());
    }

    public function testIsOverdueFalseWhenDueDateInFuture(): void
    {
        $invoice = $this->issuedInvoice(); // due date +30 days

        static::assertFalse($invoice->isOverdue());
    }

    public function testIsOverdueFalseWhenStatusPaid(): void
    {
        $invoice = $this->issuedInvoice();
        $invoice->markAsPaid(new DateTimeImmutable(), Money::fromAmount(60.0));

        static::assertFalse($invoice->isOverdue());
    }

    public function testIsOverdueFalseWhenDraft(): void
    {
        $invoice = $this->newInvoice();

        static::assertFalse($invoice->isOverdue());
    }

    public function testGetDaysUntilDueNullWhenNoDate(): void
    {
        $invoice = $this->newInvoice();

        static::assertNull($invoice->getDaysUntilDue());
    }

    public function testGetDaysUntilDueNegativeWhenPast(): void
    {
        $invoice = $this->newInvoice();
        $invoice->addLine(InvoiceLineId::generate(), 'X', 1.0, Money::fromAmount(50.0), TaxRate::standardFrance());
        $invoice->issue(
            new DateTimeImmutable('-30 days'),
            new DateTimeImmutable('-10 days'),
        );

        static::assertLessThan(0, $invoice->getDaysUntilDue());
    }

    public function testGettersExposeIdentitiesAndOrderProject(): void
    {
        $invoice = Invoice::create(
            InvoiceId::fromLegacyInt(42),
            InvoiceNumber::fromString('F202601042'),
            CompanyId::fromLegacyInt(1),
            ClientId::fromLegacyInt(2),
            OrderId::fromLegacyInt(7),
            ProjectId::fromLegacyInt(11),
        );

        static::assertSame(42, $invoice->getId()->toLegacyInt());
        static::assertSame('F202601042', $invoice->getNumber()->getValue());
        static::assertSame(1, $invoice->getCompanyId()->toLegacyInt());
        static::assertSame(2, $invoice->getClientId()->toLegacyInt());
        static::assertSame(7, $invoice->getOrderId()?->toLegacyInt());
        static::assertSame(11, $invoice->getProjectId()?->toLegacyInt());
    }

    private function newInvoice(): Invoice
    {
        return Invoice::create(
            InvoiceId::fromLegacyInt(1),
            InvoiceNumber::fromString('F202601001'),
            CompanyId::fromLegacyInt(1),
            ClientId::fromLegacyInt(2),
        );
    }

    private function issuedInvoice(): Invoice
    {
        $invoice = $this->newInvoice();
        $invoice->addLine(
            InvoiceLineId::generate(),
            'Service',
            1.0,
            Money::fromAmount(50.0),
            TaxRate::standardFrance(),
        );
        $invoice->issue(
            new DateTimeImmutable(),
            new DateTimeImmutable('+30 days'),
        );

        return $invoice;
    }
}
