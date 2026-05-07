<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Invoice\ValueObject;

use App\Domain\Invoice\ValueObject\InvoiceStatus;
use PHPUnit\Framework\TestCase;

final class InvoiceStatusTest extends TestCase
{
    public function testCases(): void
    {
        $this->assertSame('brouillon', InvoiceStatus::DRAFT->value);
        $this->assertSame('envoyee', InvoiceStatus::SENT->value);
        $this->assertSame('payee', InvoiceStatus::PAID->value);
        $this->assertSame('en_retard', InvoiceStatus::OVERDUE->value);
        $this->assertSame('annulee', InvoiceStatus::CANCELLED->value);
    }

    public function testIsEditableOnlyForDraft(): void
    {
        $this->assertTrue(InvoiceStatus::DRAFT->isEditable());
        $this->assertFalse(InvoiceStatus::SENT->isEditable());
        $this->assertFalse(InvoiceStatus::PAID->isEditable());
        $this->assertFalse(InvoiceStatus::OVERDUE->isEditable());
        $this->assertFalse(InvoiceStatus::CANCELLED->isEditable());
    }

    public function testIsFinalized(): void
    {
        $this->assertFalse(InvoiceStatus::DRAFT->isFinalized());
        $this->assertTrue(InvoiceStatus::SENT->isFinalized());
        $this->assertTrue(InvoiceStatus::PAID->isFinalized());
        $this->assertTrue(InvoiceStatus::OVERDUE->isFinalized());
        $this->assertFalse(InvoiceStatus::CANCELLED->isFinalized());
    }

    public function testGetLabel(): void
    {
        $this->assertSame('Brouillon', InvoiceStatus::DRAFT->getLabel());
        $this->assertSame('Payée', InvoiceStatus::PAID->getLabel());
        $this->assertSame('Envoyée', InvoiceStatus::SENT->getLabel());
        $this->assertSame('En retard', InvoiceStatus::OVERDUE->getLabel());
        $this->assertSame('Annulée', InvoiceStatus::CANCELLED->getLabel());
    }

    public function testIsClosed(): void
    {
        $this->assertTrue(InvoiceStatus::PAID->isClosed());
        $this->assertTrue(InvoiceStatus::CANCELLED->isClosed());
        $this->assertFalse(InvoiceStatus::DRAFT->isClosed());
        $this->assertFalse(InvoiceStatus::SENT->isClosed());
        $this->assertFalse(InvoiceStatus::OVERDUE->isClosed());
    }

    public function testIsAwaitingPayment(): void
    {
        $this->assertTrue(InvoiceStatus::SENT->isAwaitingPayment());
        $this->assertTrue(InvoiceStatus::OVERDUE->isAwaitingPayment());
        $this->assertFalse(InvoiceStatus::DRAFT->isAwaitingPayment());
        $this->assertFalse(InvoiceStatus::PAID->isAwaitingPayment());
        $this->assertFalse(InvoiceStatus::CANCELLED->isAwaitingPayment());
    }

    public function testCanTransitionFromDraft(): void
    {
        $this->assertTrue(InvoiceStatus::DRAFT->canTransitionTo(InvoiceStatus::SENT));
        $this->assertTrue(InvoiceStatus::DRAFT->canTransitionTo(InvoiceStatus::CANCELLED));
        $this->assertFalse(InvoiceStatus::DRAFT->canTransitionTo(InvoiceStatus::PAID));
        $this->assertFalse(InvoiceStatus::DRAFT->canTransitionTo(InvoiceStatus::OVERDUE));
    }

    public function testCanTransitionFromSent(): void
    {
        $this->assertTrue(InvoiceStatus::SENT->canTransitionTo(InvoiceStatus::PAID));
        $this->assertTrue(InvoiceStatus::SENT->canTransitionTo(InvoiceStatus::OVERDUE));
        $this->assertTrue(InvoiceStatus::SENT->canTransitionTo(InvoiceStatus::CANCELLED));
        $this->assertFalse(InvoiceStatus::SENT->canTransitionTo(InvoiceStatus::DRAFT));
    }

    public function testCanTransitionFromOverdue(): void
    {
        $this->assertTrue(InvoiceStatus::OVERDUE->canTransitionTo(InvoiceStatus::PAID));
        $this->assertTrue(InvoiceStatus::OVERDUE->canTransitionTo(InvoiceStatus::CANCELLED));
        $this->assertFalse(InvoiceStatus::OVERDUE->canTransitionTo(InvoiceStatus::DRAFT));
        $this->assertFalse(InvoiceStatus::OVERDUE->canTransitionTo(InvoiceStatus::SENT));
    }

    public function testTerminalStatesCannotTransition(): void
    {
        foreach (InvoiceStatus::cases() as $target) {
            $this->assertFalse(InvoiceStatus::PAID->canTransitionTo($target));
            $this->assertFalse(InvoiceStatus::CANCELLED->canTransitionTo($target));
        }
    }
}
