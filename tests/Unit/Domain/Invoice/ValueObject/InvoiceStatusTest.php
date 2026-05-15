<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Invoice\ValueObject;

use App\Domain\Invoice\ValueObject\InvoiceStatus;
use PHPUnit\Framework\TestCase;

final class InvoiceStatusTest extends TestCase
{
    public function testCases(): void
    {
        static::assertSame('brouillon', InvoiceStatus::DRAFT->value);
        static::assertSame('envoyee', InvoiceStatus::SENT->value);
        static::assertSame('payee', InvoiceStatus::PAID->value);
        static::assertSame('en_retard', InvoiceStatus::OVERDUE->value);
        static::assertSame('annulee', InvoiceStatus::CANCELLED->value);
    }

    public function testIsEditableOnlyForDraft(): void
    {
        static::assertTrue(InvoiceStatus::DRAFT->isEditable());
        static::assertFalse(InvoiceStatus::SENT->isEditable());
        static::assertFalse(InvoiceStatus::PAID->isEditable());
        static::assertFalse(InvoiceStatus::OVERDUE->isEditable());
        static::assertFalse(InvoiceStatus::CANCELLED->isEditable());
    }

    public function testIsFinalized(): void
    {
        static::assertFalse(InvoiceStatus::DRAFT->isFinalized());
        static::assertTrue(InvoiceStatus::SENT->isFinalized());
        static::assertTrue(InvoiceStatus::PAID->isFinalized());
        static::assertTrue(InvoiceStatus::OVERDUE->isFinalized());
        static::assertFalse(InvoiceStatus::CANCELLED->isFinalized());
    }

    public function testGetLabel(): void
    {
        static::assertSame('Brouillon', InvoiceStatus::DRAFT->getLabel());
        static::assertSame('Payée', InvoiceStatus::PAID->getLabel());
        static::assertSame('Envoyée', InvoiceStatus::SENT->getLabel());
        static::assertSame('En retard', InvoiceStatus::OVERDUE->getLabel());
        static::assertSame('Annulée', InvoiceStatus::CANCELLED->getLabel());
    }

    public function testIsClosed(): void
    {
        static::assertTrue(InvoiceStatus::PAID->isClosed());
        static::assertTrue(InvoiceStatus::CANCELLED->isClosed());
        static::assertFalse(InvoiceStatus::DRAFT->isClosed());
        static::assertFalse(InvoiceStatus::SENT->isClosed());
        static::assertFalse(InvoiceStatus::OVERDUE->isClosed());
    }

    public function testIsAwaitingPayment(): void
    {
        static::assertTrue(InvoiceStatus::SENT->isAwaitingPayment());
        static::assertTrue(InvoiceStatus::OVERDUE->isAwaitingPayment());
        static::assertFalse(InvoiceStatus::DRAFT->isAwaitingPayment());
        static::assertFalse(InvoiceStatus::PAID->isAwaitingPayment());
        static::assertFalse(InvoiceStatus::CANCELLED->isAwaitingPayment());
    }

    public function testCanTransitionFromDraft(): void
    {
        static::assertTrue(InvoiceStatus::DRAFT->canTransitionTo(InvoiceStatus::SENT));
        static::assertTrue(InvoiceStatus::DRAFT->canTransitionTo(InvoiceStatus::CANCELLED));
        static::assertFalse(InvoiceStatus::DRAFT->canTransitionTo(InvoiceStatus::PAID));
        static::assertFalse(InvoiceStatus::DRAFT->canTransitionTo(InvoiceStatus::OVERDUE));
    }

    public function testCanTransitionFromSent(): void
    {
        static::assertTrue(InvoiceStatus::SENT->canTransitionTo(InvoiceStatus::PAID));
        static::assertTrue(InvoiceStatus::SENT->canTransitionTo(InvoiceStatus::OVERDUE));
        static::assertTrue(InvoiceStatus::SENT->canTransitionTo(InvoiceStatus::CANCELLED));
        static::assertFalse(InvoiceStatus::SENT->canTransitionTo(InvoiceStatus::DRAFT));
    }

    public function testCanTransitionFromOverdue(): void
    {
        static::assertTrue(InvoiceStatus::OVERDUE->canTransitionTo(InvoiceStatus::PAID));
        static::assertTrue(InvoiceStatus::OVERDUE->canTransitionTo(InvoiceStatus::CANCELLED));
        static::assertFalse(InvoiceStatus::OVERDUE->canTransitionTo(InvoiceStatus::DRAFT));
        static::assertFalse(InvoiceStatus::OVERDUE->canTransitionTo(InvoiceStatus::SENT));
    }

    public function testTerminalStatesCannotTransition(): void
    {
        foreach (InvoiceStatus::cases() as $target) {
            static::assertFalse(InvoiceStatus::PAID->canTransitionTo($target));
            static::assertFalse(InvoiceStatus::CANCELLED->canTransitionTo($target));
        }
    }
}
