<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\WorkItem\EventListener;

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
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class BillRelatedWorkItemsOnInvoiceCreatedTest extends TestCase
{
    public function testNoOpWhenWorkItemIdsEmpty(): void
    {
        $workItemRepo = $this->createMock(WorkItemRepositoryInterface::class);
        $workItemRepo->expects(self::never())->method('findByIdOrNull');

        $eventBus = $this->createMock(MessageBusInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $listener = new BillRelatedWorkItemsOnInvoiceCreated($workItemRepo, $eventBus, $logger);

        $event = InvoiceCreatedEvent::create(
            InvoiceId::fromLegacyInt(1),
            InvoiceNumber::generate(2026, 5, 1),
            CompanyId::fromLegacyInt(1),
            ClientId::fromLegacyInt(10),
            workItemIds: [],
        );

        $listener($event);
    }

    public function testTransitionsValidatedWorkItemsToBilled(): void
    {
        $workItemId1 = WorkItemId::generate();
        $workItemId2 = WorkItemId::generate();
        $invoiceId = InvoiceId::fromLegacyInt(7);

        $workItem1 = $this->makeValidated();
        $workItem2 = $this->makeValidated();

        $workItemRepo = $this->createMock(WorkItemRepositoryInterface::class);
        $workItemRepo->method('findByIdOrNull')
            ->willReturnOnConsecutiveCalls($workItem1, $workItem2);
        $workItemRepo->expects(self::exactly(2))->method('save');

        $eventBus = $this->createMock(MessageBusInterface::class);
        $eventBus->method('dispatch')
            ->willReturnCallback(static fn (object $e): Envelope => new Envelope($e));

        $logger = $this->createMock(LoggerInterface::class);

        $listener = new BillRelatedWorkItemsOnInvoiceCreated($workItemRepo, $eventBus, $logger);

        $event = InvoiceCreatedEvent::create(
            $invoiceId,
            InvoiceNumber::generate(2026, 5, 1),
            CompanyId::fromLegacyInt(1),
            ClientId::fromLegacyInt(10),
            workItemIds: [$workItemId1, $workItemId2],
        );

        $listener($event);

        static::assertSame(WorkItemStatus::BILLED, $workItem1->getStatus());
        static::assertSame(WorkItemStatus::BILLED, $workItem2->getStatus());
    }

    public function testSkipsMissingWorkItemAndContinues(): void
    {
        $workItem = $this->makeValidated();

        $workItemRepo = $this->createMock(WorkItemRepositoryInterface::class);
        $workItemRepo->method('findByIdOrNull')
            ->willReturnOnConsecutiveCalls(null, $workItem); // 1st missing, 2nd OK

        $workItemRepo->expects(self::once())->method('save');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::atLeastOnce())
            ->method('warning')
            ->with(static::stringContains('not found'));

        $eventBus = $this->createMock(MessageBusInterface::class);
        $eventBus->method('dispatch')
            ->willReturnCallback(static fn (object $e): Envelope => new Envelope($e));

        $listener = new BillRelatedWorkItemsOnInvoiceCreated($workItemRepo, $eventBus, $logger);

        $event = InvoiceCreatedEvent::create(
            InvoiceId::fromLegacyInt(7),
            InvoiceNumber::generate(2026, 5, 1),
            CompanyId::fromLegacyInt(1),
            ClientId::fromLegacyInt(10),
            workItemIds: [WorkItemId::generate(), WorkItemId::generate()],
        );

        $listener($event);

        static::assertSame(WorkItemStatus::BILLED, $workItem->getStatus());
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
