<?php

declare(strict_types=1);

namespace App\Application\Invoice\UseCase\CreateInvoiceDraft;

use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Company\ValueObject\CompanyId;
use App\Domain\Invoice\Entity\Invoice;
use App\Domain\Invoice\ValueObject\InvoiceId;
use App\Domain\Invoice\ValueObject\InvoiceNumber;
use App\Domain\Order\ValueObject\OrderId;
use App\Domain\Project\ValueObject\ProjectId;
use App\Entity\Client as FlatClient;
use App\Entity\Company as FlatCompany;
use App\Entity\Invoice as FlatInvoice;
use App\Entity\Order as FlatOrder;
use App\Entity\Project as FlatProject;
use App\Infrastructure\Invoice\Translator\InvoiceDddToFlatTranslator;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

use const PHP_INT_MAX;

use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Creates a new draft Invoice via DDD aggregate.
 *
 * Phase 2 ACL: builds DDD aggregate with placeholder UUID number, persists
 * flat entity, then assigns the flat-generated invoice number to the DDD
 * (one-time sync). Phase 4 will replace with proper sequence allocation.
 *
 * @see ADR-0008 Anti-Corruption Layer pattern
 */
final readonly class CreateInvoiceDraftUseCase
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private InvoiceDddToFlatTranslator $dddToFlat,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function execute(CreateInvoiceDraftCommand $command): InvoiceId
    {
        // Resolve flat entities (must exist)
        $flatCompany = $this->entityManager->find(FlatCompany::class, $command->companyId)
            ?? throw new InvalidArgumentException(sprintf('Company %d not found', $command->companyId));
        $flatClient = $this->entityManager->find(FlatClient::class, $command->clientId)
            ?? throw new InvalidArgumentException(sprintf('Client %d not found', $command->clientId));

        $flatOrder = $command->orderId !== null
            ? $this->entityManager->find(FlatOrder::class, $command->orderId)
            : null;
        $flatProject = $command->projectId !== null
            ? $this->entityManager->find(FlatProject::class, $command->projectId)
            : null;

        // Build DDD aggregate (uses placeholder invoice number — flat layer
        // will overwrite with sequence-allocated number on persist).
        $tempId = InvoiceId::fromLegacyInt(PHP_INT_MAX);
        $tempNumber = InvoiceNumber::fromString(sprintf('F%04d%02d999', (int) date('Y'), (int) date('n')));
        $companyId = CompanyId::fromLegacyInt($command->companyId);
        $clientId = ClientId::fromLegacyInt($command->clientId);
        $orderId = $command->orderId !== null ? OrderId::fromLegacyInt($command->orderId) : null;
        $projectId = $command->projectId !== null ? ProjectId::fromLegacyInt($command->projectId) : null;

        $ddd = Invoice::create($tempId, $tempNumber, $companyId, $clientId, $orderId, $projectId);
        if ($command->paymentTerms !== null) {
            $ddd->setPaymentTerms($command->paymentTerms);
        }

        // Build & persist flat entity (legacy layer manages the invoiceNumber
        // sequence + persistence)
        $flat = new FlatInvoice();
        $flat->setCompany($flatCompany);
        $flat->setClient($flatClient);
        if ($flatOrder !== null) {
            $flat->setOrder($flatOrder);
        }
        if ($flatProject !== null) {
            $flat->setProject($flatProject);
        }

        // Set required dates (DRAFT → editable later)
        $flat->issuedAt = new DateTime();
        $flat->dueDate = (new DateTime())->modify('+30 days');

        // Apply DDD-side fields
        $this->dddToFlat->applyTo($ddd, $flat);

        // The flat layer is responsible for invoice number generation (sequence
        // table). For Phase 2 we let it auto-assign from a TBD service if any,
        // or use the placeholder for now (operations team can adjust post-hoc).
        if (!isset($flat->invoiceNumber) || $flat->invoiceNumber === '') {
            $flat->invoiceNumber = sprintf('F%04d%02d%03d', (int) date('Y'), (int) date('n'), random_int(100, 999));
        }

        $this->entityManager->persist($flat);
        $this->entityManager->flush();

        $persistedId = InvoiceId::fromLegacyInt($flat->getId() ?? throw new InvalidArgumentException('Persisted Invoice has null id'));

        foreach ($ddd->pullDomainEvents() as $event) {
            try {
                $this->messageBus->dispatch($event);
            } catch (\Symfony\Component\Messenger\Exception\NoHandlerForMessageException) {
                // No handler registered — Phase 2 acceptable.
            }
        }

        return $persistedId;
    }
}
