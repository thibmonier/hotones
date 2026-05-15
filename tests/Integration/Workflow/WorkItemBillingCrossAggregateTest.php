<?php

declare(strict_types=1);

namespace App\Tests\Integration\Workflow;

use App\Application\WorkItem\EventListener\BillRelatedWorkItemsOnInvoiceCreated;
use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Company\ValueObject\CompanyId;
use App\Domain\Contributor\ValueObject\ContributorId;
use App\Domain\Invoice\Event\InvoiceCreatedEvent;
use App\Domain\Invoice\ValueObject\InvoiceId;
use App\Domain\Invoice\ValueObject\InvoiceNumber;
use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\WorkItem\Entity\WorkItem;
use App\Domain\WorkItem\Repository\WorkItemRepositoryInterface;
use App\Domain\WorkItem\ValueObject\HourlyRate;
use App\Domain\WorkItem\ValueObject\WorkedHours;
use App\Domain\WorkItem\ValueObject\WorkItemId;
use App\Domain\WorkItem\ValueObject\WorkItemStatus;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;
use Symfony\Component\Messenger\Envelope;

/**
 * Sprint-023 sub-epic B BUFFER tests Integration sprint-021 suite —
 * rattrapage Workflow E2E cross-aggregate Invoice listener trigger
 * validation (US-101 sprint-021).
 *
 * Vérifie chain end-to-end :
 * 1. `InvoiceCreatedEvent` dispatched avec `workItemIds` payload (AT-3.2)
 * 2. Listener `BillRelatedWorkItemsOnInvoiceCreated` consume event
 * 3. WorkItem validated → markAsBilled(invoiceId) → status BILLED
 * 4. WorkItemBilledEvent dispatched
 *
 * Test Integration sans DB : utilise in-memory WorkItemRepository fake
 * (boucle save/findByIdOrNull). Validates orchestration listener +
 * Domain transitions sans schema fixtures.
 */
final class WorkItemBillingCrossAggregateTest extends TestCase
{
    public function testInvoiceCreatedEventTransitionsValidatedWorkItemToBilled(): void
    {
        // Arrange : 2 WorkItems validated dans repo in-memory
        $workItem1 = $this->makeValidated();
        $workItem2 = $this->makeValidated();
        $workItem1Id = $workItem1->getId();
        $workItem2Id = $workItem2->getId();

        $repo = new class($workItem1, $workItem2) implements WorkItemRepositoryInterface {
            private array $items = [];

            public function __construct(WorkItem ...$workItems)
            {
                foreach ($workItems as $w) {
                    $this->items[(string) $w->getId()] = $w;
                }
            }

            public function findById(WorkItemId $id): WorkItem
            {
                return $this->items[(string) $id] ?? throw new RuntimeException('not found');
            }

            public function findByIdOrNull(WorkItemId $id): ?WorkItem
            {
                return $this->items[(string) $id] ?? null;
            }

            public function findByProject(ProjectId $projectId): array
            {
                return [];
            }

            public function findByContributorAndDateRange(
                ContributorId $contributorId,
                DateTimeImmutable $from,
                DateTimeImmutable $to,
            ): array {
                return [];
            }

            public function findByContributorAndDate(
                ContributorId $contributorId,
                DateTimeImmutable $date,
            ): array {
                return [];
            }

            public function save(WorkItem $workItem): void
            {
                $this->items[(string) $workItem->getId()] = $workItem;
            }
        };

        $eventBus = $this->createMock(\Symfony\Component\Messenger\MessageBusInterface::class);
        $dispatched = [];
        $eventBus->method('dispatch')
            ->willReturnCallback(static function (object $event) use (&$dispatched): Envelope {
                $dispatched[] = $event;

                return new Envelope($event);
            });

        $listener = new BillRelatedWorkItemsOnInvoiceCreated($repo, $eventBus, new NullLogger());

        $invoiceCreated = InvoiceCreatedEvent::create(
            invoiceId: InvoiceId::fromLegacyInt(7),
            invoiceNumber: InvoiceNumber::generate(2026, 5, 1),
            companyId: CompanyId::fromLegacyInt(1),
            clientId: ClientId::fromLegacyInt(10),
            workItemIds: [$workItem1Id, $workItem2Id],
        );

        // Act
        $listener($invoiceCreated);

        // Assert : both WorkItems billed + WorkItemBilledEvent dispatched per item
        static::assertSame(WorkItemStatus::BILLED, $workItem1->getStatus());
        static::assertSame(WorkItemStatus::BILLED, $workItem2->getStatus());

        $billedEvents = array_filter($dispatched, static fn ($e) => $e instanceof \App\Domain\WorkItem\Event\WorkItemBilledEvent);
        static::assertCount(2, $billedEvents);
    }

    public function testInvoiceCreatedEventEmptyWorkItemIdsIsNoOp(): void
    {
        $repo = $this->createMock(WorkItemRepositoryInterface::class);
        $repo->expects(self::never())->method('findByIdOrNull');
        $repo->expects(self::never())->method('save');

        $eventBus = $this->createMock(\Symfony\Component\Messenger\MessageBusInterface::class);
        $eventBus->expects(self::never())->method('dispatch');

        $listener = new BillRelatedWorkItemsOnInvoiceCreated($repo, $eventBus, new NullLogger());

        $event = InvoiceCreatedEvent::create(
            invoiceId: InvoiceId::fromLegacyInt(7),
            invoiceNumber: InvoiceNumber::generate(2026, 5, 1),
            companyId: CompanyId::fromLegacyInt(1),
            clientId: ClientId::fromLegacyInt(10),
            workItemIds: [],
        );

        $listener($event);
    }

    public function testInvoiceCreatedEventSkipsDraftWorkItemThrowsException(): void
    {
        // WorkItem en draft (pas validated) — markAsBilled throws
        // WorkItemInvalidTransitionException (sprint-021 US-101)
        $workItem = WorkItem::create(
            id: WorkItemId::generate(),
            projectId: ProjectId::generate(),
            contributorId: ContributorId::fromLegacyInt(42),
            workedOn: new DateTimeImmutable('2026-05-12'),
            hours: WorkedHours::fromFloat(7.0),
            costRate: HourlyRate::fromAmount(50.0),
            billedRate: HourlyRate::fromAmount(100.0),
        );
        // Pas de markAsValidated() — reste en DRAFT
        $workItem->pullDomainEvents();

        $repo = new class($workItem) implements WorkItemRepositoryInterface {
            private WorkItem $workItem;

            public function __construct(WorkItem $w)
            {
                $this->workItem = $w;
            }

            public function findById(WorkItemId $id): WorkItem
            {
                return $this->workItem;
            }

            public function findByIdOrNull(WorkItemId $id): ?WorkItem
            {
                return $this->workItem;
            }

            public function findByProject(ProjectId $projectId): array
            {
                return [];
            }

            public function findByContributorAndDateRange(
                ContributorId $contributorId,
                DateTimeImmutable $from,
                DateTimeImmutable $to,
            ): array {
                return [];
            }

            public function findByContributorAndDate(
                ContributorId $contributorId,
                DateTimeImmutable $date,
            ): array {
                return [];
            }

            public function save(WorkItem $workItem): void
            {
            }
        };

        $eventBus = $this->createMock(\Symfony\Component\Messenger\MessageBusInterface::class);
        $listener = new BillRelatedWorkItemsOnInvoiceCreated($repo, $eventBus, new NullLogger());

        $event = InvoiceCreatedEvent::create(
            invoiceId: InvoiceId::fromLegacyInt(7),
            invoiceNumber: InvoiceNumber::generate(2026, 5, 1),
            companyId: CompanyId::fromLegacyInt(1),
            clientId: ClientId::fromLegacyInt(10),
            workItemIds: [$workItem->getId()],
        );

        $this->expectException(\App\Domain\WorkItem\Exception\WorkItemInvalidTransitionException::class);
        $listener($event);
    }

    private function makeValidated(): WorkItem
    {
        $workItem = WorkItem::create(
            id: WorkItemId::generate(),
            projectId: ProjectId::generate(),
            contributorId: ContributorId::fromLegacyInt(42),
            workedOn: new DateTimeImmutable('2026-05-12'),
            hours: WorkedHours::fromFloat(7.0),
            costRate: HourlyRate::fromAmount(50.0),
            billedRate: HourlyRate::fromAmount(100.0),
        );
        $workItem->markAsValidated();
        $workItem->pullDomainEvents();

        return $workItem;
    }
}
