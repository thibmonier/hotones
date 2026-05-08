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

        self::assertSame(InvoiceStatus::DRAFT, $invoice->getStatus());
        self::assertTrue($invoice->getAmountHt()->equals(Money::zero()));
        self::assertTrue($invoice->getAmountTva()->equals(Money::zero()));
        self::assertTrue($invoice->getAmountTtc()->equals(Money::zero()));
        self::assertNull($invoice->getIssuedAt());
        self::assertNull($invoice->getDueDate());
        self::assertNull($invoice->getPaidAt());
    }

    public function testCreateRecordsInvoiceCreatedEvent(): void
    {
        $invoice = $this->newInvoice();
        $events = $invoice->pullDomainEvents();

        self::assertCount(1, $events);
        self::assertInstanceOf(InvoiceCreatedEvent::class, $events[0]);
    }

    public function testReconstituteDoesNotRecordEvents(): void
    {
        $invoice = Invoice::reconstitute(
            InvoiceId::fromLegacyInt(7),
            InvoiceNumber::fromString('F202601001'),
            CompanyId::fromLegacyInt(1),
            ClientId::fromLegacyInt(2),
        );

        self::assertSame([], $invoice->pullDomainEvents());
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
        self::assertSame(20000, $invoice->getAmountHt()->getAmountCents());
        self::assertSame(4000, $invoice->getAmountTva()->getAmountCents());
        self::assertSame(24000, $invoice->getAmountTtc()->getAmountCents());
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
        self::assertSame(25000, $invoice->getAmountHt()->getAmountCents());
        self::assertSame(5000, $invoice->getAmountTva()->getAmountCents());
        self::assertSame(30000, $invoice->getAmountTtc()->getAmountCents());
    }

    public function testUpdateLineMutatesTotals(): void
    {
        $invoice = $this->newInvoice();
        $lineId = InvoiceLineId::generate();
        $invoice->addLine($lineId, 'Initial', 1.0, Money::fromAmount(100.0), TaxRate::standardFrance());

        $invoice->updateLine($lineId, 'Updated', 2.0, Money::fromAmount(150.0), TaxRate::standardFrance());

        // 2 * 150 = 300 HT, TVA 60, TTC 360
        self::assertSame(30000, $invoice->getAmountHt()->getAmountCents());
        self::assertSame(36000, $invoice->getAmountTtc()->getAmountCents());
    }

    public function testRemoveLineRecalculatesTotals(): void
    {
        $invoice = $this->newInvoice();
        $keep = InvoiceLineId::generate();
        $remove = InvoiceLineId::generate();
        $invoice->addLine($keep, 'Keep', 1.0, Money::fromAmount(100.0), TaxRate::standardFrance());
        $invoice->addLine($remove, 'Remove', 1.0, Money::fromAmount(50.0), TaxRate::standardFrance());

        $invoice->removeLine($remove);

        self::assertSame(10000, $invoice->getAmountHt()->getAmountCents());
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

        self::assertSame(InvoiceStatus::SENT, $invoice->getStatus());
        self::assertEquals($issuedAt, $invoice->getIssuedAt());
        self::assertEquals($dueDate, $invoice->getDueDate());

        $events = $invoice->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(InvoiceIssuedEvent::class, $events[0]);
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

        self::assertSame(InvoiceStatus::PAID, $invoice->getStatus());
        self::assertEquals($paidAt, $invoice->getPaidAt());

        $events = $invoice->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(InvoicePaidEvent::class, $events[0]);
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

        self::assertSame(InvoiceStatus::OVERDUE, $invoice->getStatus());
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

        self::assertSame(InvoiceStatus::CANCELLED, $invoice->getStatus());
        $events = $invoice->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(InvoiceCancelledEvent::class, $events[0]);
    }

    public function testCancelSentRecordsEvent(): void
    {
        $invoice = $this->issuedInvoice();
        $invoice->cancel('client refund');

        self::assertSame(InvoiceStatus::CANCELLED, $invoice->getStatus());
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

        self::assertSame('Internal note', $invoice->getNotes());
        self::assertSame('Net 30', $invoice->getPaymentTerms());
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

        self::assertTrue($invoice->isOverdue());
    }

    public function testIsOverdueFalseWhenDueDateInFuture(): void
    {
        $invoice = $this->issuedInvoice(); // due date +30 days

        self::assertFalse($invoice->isOverdue());
    }

    public function testIsOverdueFalseWhenStatusPaid(): void
    {
        $invoice = $this->issuedInvoice();
        $invoice->markAsPaid(new DateTimeImmutable(), Money::fromAmount(60.0));

        self::assertFalse($invoice->isOverdue());
    }

    public function testIsOverdueFalseWhenDraft(): void
    {
        $invoice = $this->newInvoice();

        self::assertFalse($invoice->isOverdue());
    }

    public function testGetDaysUntilDueNullWhenNoDate(): void
    {
        $invoice = $this->newInvoice();

        self::assertNull($invoice->getDaysUntilDue());
    }

    public function testGetDaysUntilDueNegativeWhenPast(): void
    {
        $invoice = $this->newInvoice();
        $invoice->addLine(InvoiceLineId::generate(), 'X', 1.0, Money::fromAmount(50.0), TaxRate::standardFrance());
        $invoice->issue(
            new DateTimeImmutable('-30 days'),
            new DateTimeImmutable('-10 days'),
        );

        self::assertLessThan(0, $invoice->getDaysUntilDue());
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

        self::assertSame(42, $invoice->getId()->toLegacyInt());
        self::assertSame('F202601042', $invoice->getNumber()->getValue());
        self::assertSame(1, $invoice->getCompanyId()->toLegacyInt());
        self::assertSame(2, $invoice->getClientId()->toLegacyInt());
        self::assertSame(7, $invoice->getOrderId()?->toLegacyInt());
        self::assertSame(11, $invoice->getProjectId()?->toLegacyInt());
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
