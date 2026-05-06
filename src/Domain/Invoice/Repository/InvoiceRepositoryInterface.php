<?php

declare(strict_types=1);

namespace App\Domain\Invoice\Repository;

use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Company\ValueObject\CompanyId;
use App\Domain\Invoice\Entity\Invoice;
use App\Domain\Invoice\Exception\InvoiceNotFoundException;
use App\Domain\Invoice\ValueObject\InvoiceId;
use App\Domain\Invoice\ValueObject\InvoiceNumber;
use App\Domain\Invoice\ValueObject\InvoiceStatus;

/**
 * Repository interface for Invoice aggregate.
 *
 * Defines persistence operations for invoices.
 */
interface InvoiceRepositoryInterface
{
    /**
     * Find an invoice by its ID.
     *
     * @throws InvoiceNotFoundException if not found
     */
    public function findById(InvoiceId $id): Invoice;

    /**
     * Find an invoice by its ID or return null.
     */
    public function findByIdOrNull(InvoiceId $id): ?Invoice;

    /**
     * Find an invoice by its number.
     */
    public function findByNumber(InvoiceNumber $number): ?Invoice;

    /**
     * Find all invoices for a client.
     *
     * @return array<Invoice>
     */
    public function findByClientId(ClientId $clientId): array;

    /**
     * Find all invoices for a company.
     *
     * @return array<Invoice>
     */
    public function findByCompanyId(CompanyId $companyId): array;

    /**
     * Find invoices by status.
     *
     * @return array<Invoice>
     */
    public function findByStatus(InvoiceStatus $status): array;

    /**
     * Find overdue invoices (sent but past due date).
     *
     * @return array<Invoice>
     */
    public function findOverdue(): array;

    /**
     * Save an invoice (create or update).
     */
    public function save(Invoice $invoice): void;

    /**
     * Delete an invoice.
     */
    public function delete(Invoice $invoice): void;

    /**
     * Generate the next invoice number for a given year/month.
     */
    public function nextNumber(int $year, int $month): InvoiceNumber;
}
