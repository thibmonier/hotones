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
    }
}
