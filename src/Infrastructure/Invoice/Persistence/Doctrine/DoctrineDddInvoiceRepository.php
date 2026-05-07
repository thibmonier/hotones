<?php

declare(strict_types=1);

namespace App\Infrastructure\Invoice\Persistence\Doctrine;

use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Company\ValueObject\CompanyId;
use App\Domain\Invoice\Entity\Invoice as DddInvoice;
use App\Domain\Invoice\Exception\InvoiceNotFoundException;
use App\Domain\Invoice\Repository\InvoiceRepositoryInterface;
use App\Domain\Invoice\ValueObject\InvoiceId;
use App\Domain\Invoice\ValueObject\InvoiceNumber;
use App\Domain\Invoice\ValueObject\InvoiceStatus;
use App\Entity\Invoice as FlatInvoice;
use App\Infrastructure\Invoice\Translator\InvoiceDddToFlatTranslator;
use App\Infrastructure\Invoice\Translator\InvoiceFlatToDddTranslator;
use App\Repository\InvoiceRepository as FlatInvoiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;

/**
 * Anti-Corruption Layer adapter — implements DDD `InvoiceRepositoryInterface`
 * by delegating to the legacy `InvoiceRepository`.
 *
 * @see ADR-0008 Anti-Corruption Layer pattern
 * @see ADR-0010 Invoice BC coexistence
 */
final readonly class DoctrineDddInvoiceRepository implements InvoiceRepositoryInterface
{
    public function __construct(
        private FlatInvoiceRepository $flatRepository,
        private EntityManagerInterface $entityManager,
        private InvoiceFlatToDddTranslator $flatToDdd,
        private InvoiceDddToFlatTranslator $dddToFlat,
    ) {
    }

    public function findById(InvoiceId $id): DddInvoice
    {
        $invoice = $this->findByIdOrNull($id);
        if ($invoice === null) {
            throw new InvoiceNotFoundException(sprintf('Invoice %s not found', (string) $id));
        }

        return $invoice;
    }

    public function findByIdOrNull(InvoiceId $id): ?DddInvoice
    {
        if (!$id->isLegacy()) {
            return null;
        }

        $flat = $this->flatRepository->find($id->toLegacyInt());

        return $flat !== null ? $this->flatToDdd->translate($flat) : null;
    }

    public function findByNumber(InvoiceNumber $number): ?DddInvoice
    {
        $flat = $this->flatRepository->findOneBy(['invoiceNumber' => $number->getValue()]);

        return $flat !== null ? $this->flatToDdd->translate($flat) : null;
    }

    /**
     * @return array<DddInvoice>
     */
    public function findByClientId(ClientId $clientId): array
    {
        if (!$clientId->isLegacy()) {
            return [];
        }

        $flats = $this->flatRepository->findBy(['client' => $clientId->toLegacyInt()]);

        return array_map(fn (FlatInvoice $flat): DddInvoice => $this->flatToDdd->translate($flat), $flats);
    }

    /**
     * @return array<DddInvoice>
     */
    public function findByCompanyId(CompanyId $companyId): array
    {
        if (!$companyId->isLegacy()) {
            return [];
        }

        $flats = $this->flatRepository->findBy(['company' => $companyId->toLegacyInt()]);

        return array_map(fn (FlatInvoice $flat): DddInvoice => $this->flatToDdd->translate($flat), $flats);
    }

    /**
     * @return array<DddInvoice>
     */
    public function findByStatus(InvoiceStatus $status): array
    {
        $flats = $this->flatRepository->findBy(['status' => $status->value]);

        return array_map(fn (FlatInvoice $flat): DddInvoice => $this->flatToDdd->translate($flat), $flats);
    }

    /**
     * @return array<DddInvoice>
     */
    public function findOverdue(): array
    {
        return $this->findByStatus(InvoiceStatus::OVERDUE);
    }

    public function save(DddInvoice $invoice): void
    {
        $id = $invoice->getId();
        if (!$id->isLegacy()) {
            throw new RuntimeException('Saving DDD Invoice with pure UUID id is not yet supported during Phase 2.');
        }

        $flat = $this->flatRepository->find($id->toLegacyInt()) ?? throw new InvoiceNotFoundException(sprintf('Cannot update Invoice %s: not found', (string) $id));

        $this->dddToFlat->applyTo($invoice, $flat);

        $this->entityManager->persist($flat);
        $this->entityManager->flush();
    }

    public function delete(DddInvoice $invoice): void
    {
        $id = $invoice->getId();
        if (!$id->isLegacy()) {
            throw new RuntimeException('Deleting DDD Invoice with pure UUID id not yet supported');
        }

        $flat = $this->flatRepository->find($id->toLegacyInt()) ?? throw new InvoiceNotFoundException(sprintf('Cannot delete Invoice %s: not found', (string) $id));

        $this->entityManager->remove($flat);
        $this->entityManager->flush();
    }

    public function nextNumber(int $year, int $month): InvoiceNumber
    {
        // Phase 2: stub — flat layer handles invoice number generation. Phase 4
        // will move sequence allocation to a dedicated service.
        return InvoiceNumber::fromString(sprintf('F%04d%02d001', $year, $month));
    }
}
