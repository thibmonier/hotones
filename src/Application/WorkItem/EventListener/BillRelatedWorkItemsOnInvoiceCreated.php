<?php

declare(strict_types=1);

namespace App\Application\WorkItem\EventListener;

use App\Domain\Invoice\Event\InvoiceCreatedEvent;
use App\Domain\WorkItem\Repository\WorkItemRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * EPIC-003 Phase 3 (sprint-021 US-101) — listener cross-aggregate
 * Application Layer ACL Invoice → WorkItem.
 *
 * Consume `InvoiceCreatedEvent` (avec workItemIds payload — AT-3.2 ADR-0016)
 * et déclenche transition workflow `bill` (validated → billed) sur chaque
 * WorkItem associé. Non-fatal si WorkItem absent OU déjà billed (idempotent).
 *
 * Pattern strangler fig — Application Layer orchestre, Domain reste pur.
 *
 * Latence acceptée < 10s (async via Symfony Messenger).
 */
#[AsMessageHandler]
final readonly class BillRelatedWorkItemsOnInvoiceCreated
{
    public function __construct(
        private WorkItemRepositoryInterface $workItemRepository,
        private MessageBusInterface $eventBus,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(InvoiceCreatedEvent $event): void
    {
        $workItemIds = $event->getWorkItemIds();

        if ($workItemIds === []) {
            return;
        }

        $invoiceId = $event->getInvoiceId();
        $billed = 0;
        $skipped = 0;

        foreach ($workItemIds as $workItemId) {
            $workItem = $this->workItemRepository->findByIdOrNull($workItemId);

            if ($workItem === null) {
                ++$skipped;

                $this->logger->warning('WorkItem not found while billing on InvoiceCreatedEvent', [
                    'work_item_id' => (string) $workItemId,
                    'invoice_id' => (string) $invoiceId,
                ]);

                continue;
            }

            $workItem->markAsBilled($invoiceId);
            $this->workItemRepository->save($workItem);

            foreach ($workItem->pullDomainEvents() as $domainEvent) {
                $this->eventBus->dispatch($domainEvent);
            }

            ++$billed;
        }

        $this->logger->info('WorkItems billed on InvoiceCreatedEvent', [
            'invoice_id' => (string) $invoiceId,
            'billed_count' => $billed,
            'skipped_count' => $skipped,
            'total_requested' => count($workItemIds),
        ]);
    }
}
