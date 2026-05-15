<?php

declare(strict_types=1);

namespace App\Tests\Integration\Application\Project;

use App\Application\Project\EventListener\InvalidateBillingLeadTimeCacheOnInvoiceCreated;
use App\Application\Project\EventListener\SendBillingLeadTimeRedAlertOnInvoiceCreated;
use App\Application\Project\Query\BillingLeadTimeKpi\ComputeBillingLeadTimeKpiHandler;
use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Company\ValueObject\CompanyId;
use App\Domain\Invoice\Event\InvoiceCreatedEvent;
use App\Domain\Invoice\ValueObject\InvoiceId;
use App\Domain\Invoice\ValueObject\InvoiceNumber;
use App\Domain\Project\Repository\BillingLeadTimeReadModelRepositoryInterface;
use App\Entity\Invoice;
use App\Factory\ClientFactory;
use App\Factory\InvoiceFactory;
use App\Factory\OrderFactory;
use App\Factory\ProjectFactory;
use App\Service\Alerting\AlertSeverity;
use App\Service\Alerting\SlackAlertingInterface;
use App\Tests\Support\MultiTenantTestTrait;
use DateTimeImmutable;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * End-to-end integration test for the InvoiceCreatedEvent → Billing lead time flow.
 *
 * Covers US-111 T-111-06 — verifies that publishing an `InvoiceCreatedEvent`
 * exercises:
 *   1. {@see InvalidateBillingLeadTimeCacheOnInvoiceCreated}    (T-111-03) → cache.kpi cleared
 *   2. {@see SendBillingLeadTimeRedAlertOnInvoiceCreated}        (T-111-05) → Slack si médiane > 30j
 *   3. Lead time KPI recomputed fresh post-invalidation
 *   4. Top 3 slow clients aggregation via real DB
 */
final class InvoiceCreatedEventBillingLeadTimeFlowTest extends KernelTestCase
{
    use Factories;
    use MultiTenantTestTrait;
    use ResetDatabase;

    private CacheItemPoolInterface $kpiCache;
    private ComputeBillingLeadTimeKpiHandler $computeBillingLeadTimeKpi;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->setUpMultiTenant();

        $this->kpiCache = static::getContainer()->get('cache.kpi');
        $this->computeBillingLeadTimeKpi = static::getContainer()->get(ComputeBillingLeadTimeKpiHandler::class);
        $this->now = new DateTimeImmutable('2026-05-12T00:00:00+00:00');

        $this->kpiCache->clear();

        // Ensure ProjectFactory has at least one Project for OrderFactory defaults.
        ProjectFactory::createOne(['company' => $this->getTestCompany()]);
    }

    public function testCacheIsInvalidatedOnInvoiceCreatedEvent(): void
    {
        $client = ClientFactory::createOne(['company' => $this->getTestCompany(), 'name' => 'Acme']);
        $this->createQuoteInvoice($client, daysAgoEmitted: 10, leadTimeDays: 7);

        $repository = static::getContainer()->get(BillingLeadTimeReadModelRepositoryInterface::class);
        $beforeDispatch = $repository->findEmittedInRollingWindow(30, $this->now);
        static::assertCount(1, $beforeDispatch);

        $cacheKey = sprintf(
            'billing_lead_time.emitted_records.company_%d.window_30.day_%s',
            $this->getTestCompany()->getId(),
            $this->now->format('Y-m-d'),
        );
        static::assertTrue($this->kpiCache->hasItem($cacheKey), 'cache populated after first read');

        $invalidator = static::getContainer()->get(InvalidateBillingLeadTimeCacheOnInvoiceCreated::class);
        $invalidator($this->makeEvent());

        static::assertFalse(
            $this->kpiCache->hasItem($cacheKey),
            'cache.kpi pool cleared by InvalidateBillingLeadTimeCacheOnInvoiceCreated',
        );
    }

    public function testSlackAlertTriggeredWhenMedian30JCrossesRedThreshold(): void
    {
        $client = ClientFactory::createOne(['company' => $this->getTestCompany()]);
        // 1 invoice with 50-day lead time → médiane 30j ≈ 50 (> 30 red threshold)
        $this->createQuoteInvoice($client, daysAgoEmitted: 5, leadTimeDays: 50);

        $slackSpy = new BillingLeadTimeSlackAlertingSpy();
        $listener = new SendBillingLeadTimeRedAlertOnInvoiceCreated(
            computeBillingLeadTimeKpi: $this->computeBillingLeadTimeKpi,
            slackAlertingService: $slackSpy,
            logger: new NullLogger(),
        );

        $listener($this->makeEvent());

        static::assertSame(1, $slackSpy->callCount, 'Slack alert sent when médiane 30j > 30j');
    }

    public function testNoSlackAlertWhenMedianBelowThreshold(): void
    {
        $client = ClientFactory::createOne(['company' => $this->getTestCompany()]);
        // 1 invoice with 10-day lead time → médiane 30j ≈ 10 (< 30)
        $this->createQuoteInvoice($client, daysAgoEmitted: 5, leadTimeDays: 10);

        $slackSpy = new BillingLeadTimeSlackAlertingSpy();
        $listener = new SendBillingLeadTimeRedAlertOnInvoiceCreated(
            computeBillingLeadTimeKpi: $this->computeBillingLeadTimeKpi,
            slackAlertingService: $slackSpy,
            logger: new NullLogger(),
        );

        $listener($this->makeEvent());

        static::assertSame(0, $slackSpy->callCount);
    }

    public function testKpiRefreshesAfterEventDispatchWithNewInvoiceData(): void
    {
        $client = ClientFactory::createOne(['company' => $this->getTestCompany()]);

        // Initial state : 1 invoice 10-day lead time → médiane ≈ 10
        $this->createQuoteInvoice($client, daysAgoEmitted: 10, leadTimeDays: 10);
        $first = ($this->computeBillingLeadTimeKpi)($this->now);
        static::assertEqualsWithDelta(10.0, $first->stats30->p50->getDays(), 0.5);

        // Add slow invoice 50-day lead time → should shift median up
        $this->createQuoteInvoice($client, daysAgoEmitted: 5, leadTimeDays: 50);

        $invalidator = static::getContainer()->get(InvalidateBillingLeadTimeCacheOnInvoiceCreated::class);
        $invalidator($this->makeEvent());

        $after = ($this->computeBillingLeadTimeKpi)($this->now);
        static::assertGreaterThan(
            $first->stats30->p50->getDays(),
            $after->stats30->p50->getDays(),
            'Lead time median should increase after slow invoice once cache cleared',
        );
    }

    public function testTopSlowClientsAggregatedFromRealData(): void
    {
        $clientAcme = ClientFactory::createOne(['company' => $this->getTestCompany(), 'name' => 'Acme']);
        $clientBeta = ClientFactory::createOne(['company' => $this->getTestCompany(), 'name' => 'Beta']);
        $clientGamma = ClientFactory::createOne(['company' => $this->getTestCompany(), 'name' => 'Gamma']);

        // Acme : avg 30
        $this->createQuoteInvoice($clientAcme, daysAgoEmitted: 5, leadTimeDays: 25);
        $this->createQuoteInvoice($clientAcme, daysAgoEmitted: 5, leadTimeDays: 35);
        // Beta : avg 10
        $this->createQuoteInvoice($clientBeta, daysAgoEmitted: 5, leadTimeDays: 10);
        // Gamma : avg 50
        $this->createQuoteInvoice($clientGamma, daysAgoEmitted: 5, leadTimeDays: 50);

        $kpi = ($this->computeBillingLeadTimeKpi)($this->now);

        static::assertCount(3, $kpi->topSlowClients);
        static::assertSame('Gamma', $kpi->topSlowClients[0]->clientName);
        static::assertEqualsWithDelta(50.0, $kpi->topSlowClients[0]->averageLeadTimeDays, 0.5);
        static::assertSame('Acme', $kpi->topSlowClients[1]->clientName);
        static::assertEqualsWithDelta(30.0, $kpi->topSlowClients[1]->averageLeadTimeDays, 0.5);
        static::assertSame(2, $kpi->topSlowClients[1]->sampleCount);
        static::assertSame('Beta', $kpi->topSlowClients[2]->clientName);
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

    private function makeEvent(): InvoiceCreatedEvent
    {
        return new InvoiceCreatedEvent(
            invoiceId: InvoiceId::fromLegacyInt(1),
            invoiceNumber: InvoiceNumber::fromString('F202605001'),
            companyId: CompanyId::fromLegacyInt(1),
            clientId: ClientId::fromLegacyInt(1),
        );
    }
}

/**
 * @internal slack alerting test double for E2E assertions
 */
final class BillingLeadTimeSlackAlertingSpy implements SlackAlertingInterface
{
    public int $callCount = 0;

    public function sendAlert(string $title, string $body, AlertSeverity $severity = AlertSeverity::INFO): bool
    {
        ++$this->callCount;

        return true;
    }
}
