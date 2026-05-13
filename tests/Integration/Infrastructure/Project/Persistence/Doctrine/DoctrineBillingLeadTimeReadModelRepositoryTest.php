<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Project\Persistence\Doctrine;

use App\Domain\Project\Service\BillingLeadTimeCalculator;
use App\Domain\Project\Service\QuoteInvoiceRecord;
use App\Entity\Invoice;
use App\Entity\Order;
use App\Factory\ClientFactory;
use App\Factory\InvoiceFactory;
use App\Factory\OrderFactory;
use App\Factory\ProjectFactory;
use App\Infrastructure\Project\Persistence\Doctrine\DoctrineBillingLeadTimeReadModelRepository;
use App\Tests\Support\MultiTenantTestTrait;
use DateTimeImmutable;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Integration tests for {@see DoctrineBillingLeadTimeReadModelRepository}.
 *
 * Verifies DQL projection JOIN Invoice ↔ Order ↔ Client + multi-tenant
 * filtering + rolling window + excludes (unsigned quotes, cancelled/draft invoices).
 */
final class DoctrineBillingLeadTimeReadModelRepositoryTest extends KernelTestCase
{
    use Factories;
    use MultiTenantTestTrait;
    use ResetDatabase;

    private DoctrineBillingLeadTimeReadModelRepository $repository;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->setUpMultiTenant();
        $this->repository = static::getContainer()->get(DoctrineBillingLeadTimeReadModelRepository::class);
        $this->now = new DateTimeImmutable('2026-05-12T00:00:00+00:00');

        // OrderFactory::defaults() references ProjectFactory::random() — ensure
        // at least one Project exists in the current company before any OrderFactory call.
        ProjectFactory::createOne(['company' => $this->getTestCompany()]);
    }

    public function testRejectsWindowDaysBelowOne(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->repository->findEmittedInRollingWindow(0, $this->now);
    }

    public function testReturnsEmptyArrayWhenNoInvoices(): void
    {
        self::assertSame([], $this->repository->findEmittedInRollingWindow(30, $this->now));
    }

    public function testIncludesOnlySignedQuoteInvoicedInsideWindow(): void
    {
        $client = ClientFactory::createOne(['company' => $this->getTestCompany(), 'name' => 'Acme']);

        // 10 days ago emission, 14 days lead time → inside 30/90/365
        $this->createQuoteInvoice($client, daysAgoEmitted: 10, leadTimeDays: 14);
        // 60 days ago emission → inside 90/365 only
        $this->createQuoteInvoice($client, daysAgoEmitted: 60, leadTimeDays: 30);
        // 200 days ago emission → 365 only
        $this->createQuoteInvoice($client, daysAgoEmitted: 200, leadTimeDays: 45);
        // Unsigned quote → excluded
        $this->createUnsignedQuoteInvoice($client);
        // Cancelled invoice → excluded
        $this->createCancelledQuoteInvoice($client, leadTimeDays: 7);
        // Draft invoice → excluded
        $this->createDraftQuoteInvoice($client, leadTimeDays: 7);

        $w30 = $this->repository->findEmittedInRollingWindow(30, $this->now);
        $w90 = $this->repository->findEmittedInRollingWindow(90, $this->now);
        $w365 = $this->repository->findEmittedInRollingWindow(365, $this->now);

        self::assertCount(1, $w30);
        self::assertCount(2, $w90);
        self::assertCount(3, $w365);

        foreach ($w30 as $record) {
            self::assertInstanceOf(QuoteInvoiceRecord::class, $record);
            self::assertSame('Acme', $record->clientName);
        }
    }

    public function testFiltersByCurrentCompany(): void
    {
        $ownClient = ClientFactory::createOne(['company' => $this->getTestCompany(), 'name' => 'Own']);
        $this->createQuoteInvoice($ownClient, daysAgoEmitted: 10, leadTimeDays: 7);

        // Another tenant
        $otherCompany = $this->createTestCompany('Other Tenant');
        $otherClient = ClientFactory::createOne(['company' => $otherCompany, 'name' => 'Other']);
        $this->createQuoteInvoiceForCompany(
            company: $otherCompany,
            client: $otherClient,
            daysAgoEmitted: 10,
            leadTimeDays: 100,
        );

        $records = $this->repository->findEmittedInRollingWindow(30, $this->now);

        self::assertCount(1, $records);
        self::assertSame('Own', $records[0]->clientName);
    }

    public function testRecordsAreConsumableByCalculator(): void
    {
        $client = ClientFactory::createOne(['company' => $this->getTestCompany()]);
        $this->createQuoteInvoice($client, daysAgoEmitted: 5, leadTimeDays: 10);
        $this->createQuoteInvoice($client, daysAgoEmitted: 5, leadTimeDays: 20);
        $this->createQuoteInvoice($client, daysAgoEmitted: 5, leadTimeDays: 30);

        $records = $this->repository->findEmittedInRollingWindow(30, $this->now);
        $stats = (new BillingLeadTimeCalculator())->calculateRolling(
            $records,
            windowDays: 30,
            now: $this->now,
        );

        self::assertSame(3, $stats->count);
        self::assertEqualsWithDelta(20.0, $stats->p50->getDays(), 0.5);
    }

    private function createQuoteInvoice(object $client, int $daysAgoEmitted, int $leadTimeDays): void
    {
        $emittedAt = $this->now->modify(sprintf('-%d days', $daysAgoEmitted));
        $signedAt = $emittedAt->modify(sprintf('-%d days', $leadTimeDays));

        $order = OrderFactory::createOne([
            'company' => $this->getTestCompany(),
            'project' => null,
            'validatedAt' => InvoiceFactory::toMutable($signedAt),
        ]);

        InvoiceFactory::createOne([
            'company' => $this->getTestCompany(),
            'client' => $client,
            'order' => $order,
            'status' => Invoice::STATUS_SENT,
            'issuedAt' => InvoiceFactory::toMutable($emittedAt),
            'dueDate' => InvoiceFactory::toMutable($emittedAt->modify('+30 days')),
            'paidAt' => null,
            'amountHt' => '100.00',
            'amountTva' => '20.00',
            'tvaRate' => '20.00',
            'amountTtc' => '120.00',
        ]);
    }

    private function createUnsignedQuoteInvoice(object $client): void
    {
        $emittedAt = $this->now->modify('-10 days');

        $order = OrderFactory::createOne([
            'company' => $this->getTestCompany(),
            'project' => null,
            'validatedAt' => null,
        ]);

        InvoiceFactory::createOne([
            'company' => $this->getTestCompany(),
            'client' => $client,
            'order' => $order,
            'status' => Invoice::STATUS_SENT,
            'issuedAt' => InvoiceFactory::toMutable($emittedAt),
            'dueDate' => InvoiceFactory::toMutable($emittedAt->modify('+30 days')),
            'paidAt' => null,
            'amountHt' => '100.00',
            'amountTva' => '20.00',
            'tvaRate' => '20.00',
            'amountTtc' => '120.00',
        ]);
    }

    private function createCancelledQuoteInvoice(object $client, int $leadTimeDays): void
    {
        $emittedAt = $this->now->modify('-10 days');
        $signedAt = $emittedAt->modify(sprintf('-%d days', $leadTimeDays));

        $order = OrderFactory::createOne([
            'company' => $this->getTestCompany(),
            'project' => null,
            'validatedAt' => InvoiceFactory::toMutable($signedAt),
        ]);

        InvoiceFactory::createOne([
            'company' => $this->getTestCompany(),
            'client' => $client,
            'order' => $order,
            'status' => Invoice::STATUS_CANCELLED,
            'issuedAt' => InvoiceFactory::toMutable($emittedAt),
            'dueDate' => InvoiceFactory::toMutable($emittedAt->modify('+30 days')),
            'paidAt' => null,
            'amountHt' => '100.00',
            'amountTva' => '20.00',
            'tvaRate' => '20.00',
            'amountTtc' => '120.00',
        ]);
    }

    private function createDraftQuoteInvoice(object $client, int $leadTimeDays): void
    {
        $emittedAt = $this->now->modify('-10 days');
        $signedAt = $emittedAt->modify(sprintf('-%d days', $leadTimeDays));

        $order = OrderFactory::createOne([
            'company' => $this->getTestCompany(),
            'project' => null,
            'validatedAt' => InvoiceFactory::toMutable($signedAt),
        ]);

        InvoiceFactory::createOne([
            'company' => $this->getTestCompany(),
            'client' => $client,
            'order' => $order,
            'status' => Invoice::STATUS_DRAFT,
            'issuedAt' => InvoiceFactory::toMutable($emittedAt),
            'dueDate' => InvoiceFactory::toMutable($emittedAt->modify('+30 days')),
            'paidAt' => null,
            'amountHt' => '100.00',
            'amountTva' => '20.00',
            'tvaRate' => '20.00',
            'amountTtc' => '120.00',
        ]);
    }

    /**
     * Create a quote+invoice pair on a non-current company (multi-tenant isolation test).
     */
    private function createQuoteInvoiceForCompany(
        object $company,
        object $client,
        int $daysAgoEmitted,
        int $leadTimeDays,
    ): void {
        $emittedAt = $this->now->modify(sprintf('-%d days', $daysAgoEmitted));
        $signedAt = $emittedAt->modify(sprintf('-%d days', $leadTimeDays));

        $em = $this->getEntityManager();
        $order = new Order();
        $order->setCompany($company);
        $order->name = 'Other Quote';
        $order->orderNumber = sprintf('OTHER-%d', random_int(1, 99999));
        $order->totalAmount = '1000.00';
        $order->status = 'gagne';
        $order->validatedAt = InvoiceFactory::toMutable($signedAt);
        $em->persist($order);

        $invoice = new Invoice();
        $invoice->setCompany($company);
        $invoice->setClient($client);
        $invoice->setOrder($order);
        $invoice->invoiceNumber = sprintf('OTHER-INV-%d', random_int(1, 99999));
        $invoice->status = Invoice::STATUS_SENT;
        $invoice->issuedAt = InvoiceFactory::toMutable($emittedAt);
        $invoice->dueDate = InvoiceFactory::toMutable($emittedAt->modify('+30 days'));
        $invoice->amountHt = '100.00';
        $invoice->amountTva = '20.00';
        $invoice->amountTtc = '120.00';
        $em->persist($invoice);
        $em->flush();
    }
}
