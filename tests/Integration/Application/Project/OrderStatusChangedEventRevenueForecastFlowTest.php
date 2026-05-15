<?php

declare(strict_types=1);

namespace App\Tests\Integration\Application\Project;

use App\Application\Project\EventListener\InvalidateRevenueForecastCacheOnInvoiceCreated;
use App\Application\Project\EventListener\InvalidateRevenueForecastCacheOnOrderStatusChanged;
use App\Application\Project\EventListener\SendRevenueForecastRedAlertOnOrderStatusChanged;
use App\Application\Project\Query\RevenueForecastKpi\ComputeRevenueForecastKpiHandler;
use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Company\ValueObject\CompanyId;
use App\Domain\Invoice\Event\InvoiceCreatedEvent;
use App\Domain\Invoice\ValueObject\InvoiceId;
use App\Domain\Invoice\ValueObject\InvoiceNumber;
use App\Domain\Order\Event\OrderStatusChangedEvent;
use App\Domain\Order\ValueObject\OrderId;
use App\Domain\Order\ValueObject\OrderStatus;
use App\Domain\Project\Repository\RevenueForecastReadModelRepositoryInterface;
use App\Factory\OrderFactory;
use App\Factory\ProjectFactory;
use App\Service\Alerting\AlertSeverity;
use App\Service\Alerting\SlackAlertingInterface;
use App\Tests\Support\MultiTenantTestTrait;
use DateTime;
use DateTimeImmutable;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * End-to-end integration test US-114 T-114-06 — Revenue forecast flow.
 *
 * Couvre :
 *   1. Cache populé après lecture findPipelineOrders
 *   2. {@see InvalidateRevenueForecastCacheOnOrderStatusChanged} clear cache
 *   3. {@see InvalidateRevenueForecastCacheOnInvoiceCreated} clear cache
 *   4. {@see SendRevenueForecastRedAlertOnOrderStatusChanged} fire si forecast < seuil
 *   5. Pas d'alerte si forecast au-dessus du seuil
 */
final class OrderStatusChangedEventRevenueForecastFlowTest extends KernelTestCase
{
    use Factories;
    use MultiTenantTestTrait;
    use ResetDatabase;

    private CacheItemPoolInterface $kpiCache;
    private ComputeRevenueForecastKpiHandler $computeForecast;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->setUpMultiTenant();

        $this->kpiCache = static::getContainer()->get('cache.kpi');
        $this->computeForecast = static::getContainer()->get(ComputeRevenueForecastKpiHandler::class);
        $this->now = new DateTimeImmutable('2026-06-10T00:00:00+00:00');

        $this->kpiCache->clear();

        ProjectFactory::createOne(['company' => $this->getTestCompany()]);
    }

    public function testCacheIsPopulatedAfterFirstReadAndClearedOnOrderStatusChangedEvent(): void
    {
        $this->createPipelineOrder(status: 'signe', amountTtc: 10_000.0, validUntilDaysFromNow: 15);

        $repository = static::getContainer()->get(RevenueForecastReadModelRepositoryInterface::class);
        $beforeDispatch = $repository->findPipelineOrders($this->now);
        self::assertCount(1, $beforeDispatch);

        $cacheKey = sprintf(
            'revenue_forecast.pipeline.company_%d.day_%s',
            $this->getTestCompany()->getId(),
            $this->now->format('Y-m-d'),
        );
        self::assertTrue($this->kpiCache->hasItem($cacheKey), 'cache populated after first read');

        $invalidator = static::getContainer()->get(InvalidateRevenueForecastCacheOnOrderStatusChanged::class);
        $invalidator($this->makeOrderEvent());

        self::assertFalse(
            $this->kpiCache->hasItem($cacheKey),
            'cache.kpi cleared by InvalidateRevenueForecastCacheOnOrderStatusChanged',
        );
    }

    public function testCacheIsClearedOnInvoiceCreatedEvent(): void
    {
        $this->createPipelineOrder(status: 'gagne', amountTtc: 20_000.0, validUntilDaysFromNow: 20);

        $repository = static::getContainer()->get(RevenueForecastReadModelRepositoryInterface::class);
        $repository->findPipelineOrders($this->now);

        $cacheKey = sprintf(
            'revenue_forecast.pipeline.company_%d.day_%s',
            $this->getTestCompany()->getId(),
            $this->now->format('Y-m-d'),
        );
        self::assertTrue($this->kpiCache->hasItem($cacheKey));

        $invalidator = static::getContainer()->get(InvalidateRevenueForecastCacheOnInvoiceCreated::class);
        $invalidator(new InvoiceCreatedEvent(
            invoiceId: InvoiceId::fromLegacyInt(1),
            invoiceNumber: InvoiceNumber::fromString('F202606001'),
            companyId: CompanyId::fromLegacyInt(1),
            clientId: ClientId::fromLegacyInt(1),
        ));

        self::assertFalse($this->kpiCache->hasItem($cacheKey));
    }

    public function testSlackAlertFiresWhenForecastBelowRedThreshold(): void
    {
        // Forecast 30j = 3 000 € (signe = 100% du montant)
        $this->createPipelineOrder(status: 'signe', amountTtc: 3_000.0, validUntilDaysFromNow: 15);

        $slackSpy = new RevenueForecastSlackAlertingSpy();
        $listener = new SendRevenueForecastRedAlertOnOrderStatusChanged(
            computeRevenueForecastKpi: $this->computeForecast,
            slackAlertingService: $slackSpy,
            logger: new NullLogger(),
        );

        $listener($this->makeOrderEvent());

        self::assertSame(1, $slackSpy->callCount, 'alerte fire car forecast30 = 3 000 € < seuil rouge 5 000 €');
        self::assertSame(AlertSeverity::CRITICAL, $slackSpy->lastSeverity);
    }

    public function testNoAlertWhenForecastAboveRedThreshold(): void
    {
        // Forecast 30j = 8 000 € (au-dessus du seuil 5 000 €)
        $this->createPipelineOrder(status: 'signe', amountTtc: 8_000.0, validUntilDaysFromNow: 15);

        $slackSpy = new RevenueForecastSlackAlertingSpy();
        $listener = new SendRevenueForecastRedAlertOnOrderStatusChanged(
            computeRevenueForecastKpi: $this->computeForecast,
            slackAlertingService: $slackSpy,
            logger: new NullLogger(),
        );

        $listener($this->makeOrderEvent());

        self::assertSame(0, $slackSpy->callCount);
    }

    public function testNoAlertWhenPipelineIsEmpty(): void
    {
        // Aucune commande pipeline → forecast = 0 → pas d'alerte (spam guard)
        $slackSpy = new RevenueForecastSlackAlertingSpy();
        $listener = new SendRevenueForecastRedAlertOnOrderStatusChanged(
            computeRevenueForecastKpi: $this->computeForecast,
            slackAlertingService: $slackSpy,
            logger: new NullLogger(),
        );

        $listener($this->makeOrderEvent());

        self::assertSame(0, $slackSpy->callCount, 'pas d\'alerte sur pipeline vide (forecast = 0)');
    }

    private function createPipelineOrder(string $status, float $amountTtc, int $validUntilDaysFromNow): void
    {
        $validUntil = DateTime::createFromImmutable(
            $this->now->modify(sprintf('+%d days', $validUntilDaysFromNow)),
        );

        OrderFactory::createOne([
            'company' => $this->getTestCompany(),
            'status' => $status,
            'totalAmount' => (string) $amountTtc,
            'validUntil' => $validUntil,
        ]);
    }

    private function makeOrderEvent(): OrderStatusChangedEvent
    {
        // Constructeur direct pour ancrer occurredOn sur $this->now
        // (vs ::create() qui utilise new DateTimeImmutable() — non testable).
        return new OrderStatusChangedEvent(
            orderId: OrderId::fromLegacyInt(1),
            previousStatus: OrderStatus::TO_SIGN,
            newStatus: OrderStatus::SIGNED,
            occurredOn: $this->now,
        );
    }
}

final class RevenueForecastSlackAlertingSpy implements SlackAlertingInterface
{
    public int $callCount = 0;
    public AlertSeverity $lastSeverity = AlertSeverity::INFO;

    public function sendAlert(string $title, string $body, AlertSeverity $severity = AlertSeverity::INFO): bool
    {
        ++$this->callCount;
        $this->lastSeverity = $severity;

        return true;
    }
}
