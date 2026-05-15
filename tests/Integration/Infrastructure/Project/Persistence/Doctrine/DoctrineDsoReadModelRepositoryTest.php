<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Project\Persistence\Doctrine;

use App\Domain\Project\Service\DsoCalculator;
use App\Domain\Project\Service\InvoicePaymentRecord;
use App\Entity\Invoice;
use App\Factory\ClientFactory;
use App\Factory\InvoiceFactory;
use App\Infrastructure\Project\Persistence\Doctrine\DoctrineDsoReadModelRepository;
use App\Tests\Support\MultiTenantTestTrait;
use DateTimeImmutable;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Integration tests for {@see DoctrineDsoReadModelRepository}.
 *
 * Verifies DQL projection + multi-tenant filtering + rolling window logic.
 * Companion to the Unit-tested {@see DsoCalculator} (T-110-01).
 */
final class DoctrineDsoReadModelRepositoryTest extends KernelTestCase
{
    use Factories;
    use MultiTenantTestTrait;
    use ResetDatabase;

    private DoctrineDsoReadModelRepository $repository;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->setUpMultiTenant();
        // Test the Doctrine adapter directly (bypasses CachingDsoReadModelRepository
        // decorator) so we exercise DQL/projection logic in isolation.
        $this->repository = static::getContainer()->get(DoctrineDsoReadModelRepository::class);
        $this->now = new DateTimeImmutable('2026-05-12T00:00:00+00:00');
    }

    public function testRejectsWindowDaysBelowOne(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Window days must be >= 1');

        $this->repository->findPaidInRollingWindow(0, $this->now);
    }

    public function testReturnsEmptyArrayWhenNoInvoices(): void
    {
        $records = $this->repository->findPaidInRollingWindow(30, $this->now);

        static::assertSame([], $records);
    }

    public function testIncludesOnlyPaidInvoicesInsideWindow(): void
    {
        $client = ClientFactory::createOne(['company' => $this->getTestCompany()]);

        // 10 days ago → inside 30/90/365
        $this->createPaidInvoice($client, daysAgoPaid: 10, amountTtc: '120.00', delayDays: 5);
        // 60 days ago → inside 90/365, outside 30
        $this->createPaidInvoice($client, daysAgoPaid: 60, amountTtc: '600.00', delayDays: 20);
        // 200 days ago → inside 365, outside 30/90
        $this->createPaidInvoice($client, daysAgoPaid: 200, amountTtc: '1000.00', delayDays: 45);
        // Unpaid → excluded
        $this->createUnpaidInvoice($client, amountTtc: '500.00');
        // Cancelled → excluded
        $this->createCancelledInvoice($client, amountTtc: '300.00');

        $records30 = $this->repository->findPaidInRollingWindow(30, $this->now);
        $records90 = $this->repository->findPaidInRollingWindow(90, $this->now);
        $records365 = $this->repository->findPaidInRollingWindow(365, $this->now);

        static::assertCount(1, $records30);
        static::assertCount(2, $records90);
        static::assertCount(3, $records365);

        foreach ($records30 as $record) {
            static::assertInstanceOf(InvoicePaymentRecord::class, $record);
            static::assertTrue($record->isPaid());
        }
    }

    public function testFiltersByCurrentCompany(): void
    {
        // Current company invoice (10 days ago)
        $ownClient = ClientFactory::createOne(['company' => $this->getTestCompany()]);
        $this->createPaidInvoice($ownClient, daysAgoPaid: 10, amountTtc: '500.00', delayDays: 7);

        // Other tenant invoice (10 days ago)
        $otherCompany = $this->createTestCompany('Other Tenant');
        $otherClient = ClientFactory::createOne(['company' => $otherCompany]);

        $em = $this->getEntityManager();
        $invoice = new Invoice();
        $invoice->setCompany($otherCompany);
        $invoice->setClient($otherClient);
        $invoice->invoiceNumber = 'F202605999';
        $invoice->status = Invoice::STATUS_PAID;
        $invoice->issuedAt = InvoiceFactory::toMutable($this->now->modify('-15 days'));
        $invoice->dueDate = InvoiceFactory::toMutable($this->now->modify('+15 days'));
        $invoice->paidAt = InvoiceFactory::toMutable($this->now->modify('-10 days'));
        $invoice->amountHt = '800.00';
        $invoice->amountTva = '160.00';
        $invoice->amountTtc = '960.00';
        $em->persist($invoice);
        $em->flush();

        $records = $this->repository->findPaidInRollingWindow(30, $this->now);

        // Only own company invoice returned
        static::assertCount(1, $records);
        static::assertSame(50_000, $records[0]->amountPaidCents);
    }

    public function testConvertsAmountTtcToCentsCorrectly(): void
    {
        $client = ClientFactory::createOne(['company' => $this->getTestCompany()]);
        $this->createPaidInvoice($client, daysAgoPaid: 5, amountTtc: '123.45', delayDays: 3);

        $records = $this->repository->findPaidInRollingWindow(30, $this->now);

        static::assertCount(1, $records);
        static::assertSame(12_345, $records[0]->amountPaidCents);
    }

    public function testRecordsAreConsumableByDsoCalculator(): void
    {
        $client = ClientFactory::createOne(['company' => $this->getTestCompany()]);
        // 1000€ paid after 10 days delay
        $this->createPaidInvoice($client, daysAgoPaid: 5, amountTtc: '1000.00', delayDays: 10);
        // 500€ paid after 20 days delay
        $this->createPaidInvoice($client, daysAgoPaid: 5, amountTtc: '500.00', delayDays: 20);

        $records = $this->repository->findPaidInRollingWindow(30, $this->now);
        $dso = (new DsoCalculator())->calculateRolling(
            $records,
            windowDays: 30,
            now: $this->now,
        );

        // Weighted DSO = (1000*10 + 500*20) / (1000 + 500) = 20000/1500 ≈ 13.3 (rounded to 1 decimal)
        static::assertEqualsWithDelta(13.3, $dso->getDays(), 0.1);
    }

    private function createPaidInvoice(object $client, int $daysAgoPaid, string $amountTtc, int $delayDays): void
    {
        $paidAt = $this->now->modify(sprintf('-%d days', $daysAgoPaid));
        $issuedAt = $paidAt->modify(sprintf('-%d days', $delayDays));

        InvoiceFactory::createOne([
            'company' => $this->getTestCompany(),
            'client' => $client,
            'status' => Invoice::STATUS_PAID,
            'issuedAt' => InvoiceFactory::toMutable($issuedAt),
            'dueDate' => InvoiceFactory::toMutable($issuedAt->modify('+30 days')),
            'paidAt' => InvoiceFactory::toMutable($paidAt),
            'amountHt' => $amountTtc, // simplification: TVA déjà incluse pour le test
            'amountTva' => '0.00',
            'tvaRate' => '0.00',
            'amountTtc' => $amountTtc,
        ]);
    }

    private function createUnpaidInvoice(object $client, string $amountTtc): void
    {
        $issuedAt = $this->now->modify('-15 days');

        InvoiceFactory::createOne([
            'company' => $this->getTestCompany(),
            'client' => $client,
            'status' => Invoice::STATUS_SENT,
            'issuedAt' => InvoiceFactory::toMutable($issuedAt),
            'dueDate' => InvoiceFactory::toMutable($issuedAt->modify('+30 days')),
            'paidAt' => null,
            'amountHt' => $amountTtc,
            'amountTva' => '0.00',
            'tvaRate' => '0.00',
            'amountTtc' => $amountTtc,
        ]);
    }

    private function createCancelledInvoice(object $client, string $amountTtc): void
    {
        $issuedAt = $this->now->modify('-15 days');

        InvoiceFactory::createOne([
            'company' => $this->getTestCompany(),
            'client' => $client,
            'status' => Invoice::STATUS_CANCELLED,
            'issuedAt' => InvoiceFactory::toMutable($issuedAt),
            'dueDate' => InvoiceFactory::toMutable($issuedAt->modify('+30 days')),
            'paidAt' => InvoiceFactory::toMutable($this->now->modify('-5 days')),
            'amountHt' => $amountTtc,
            'amountTva' => '0.00',
            'tvaRate' => '0.00',
            'amountTtc' => $amountTtc,
        ]);
    }
}
