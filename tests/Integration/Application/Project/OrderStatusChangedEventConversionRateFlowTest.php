<?php

declare(strict_types=1);

namespace App\Tests\Integration\Application\Project;

use App\Application\Project\EventListener\InvalidateConversionRateCacheOnOrderStatusChanged;
use App\Application\Project\EventListener\SendConversionRateRedAlertOnOrderStatusChanged;
use App\Application\Project\Query\ConversionRateKpi\ComputeConversionRateKpiHandler;
use App\Domain\Order\Event\OrderStatusChangedEvent;
use App\Domain\Order\ValueObject\OrderId;
use App\Domain\Order\ValueObject\OrderStatus;
use App\Domain\Project\Repository\ConversionRateReadModelRepositoryInterface;
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
 * End-to-end integration test US-115 T-115-06 — Conversion rate flow.
 *
 * Couvre :
 *   1. Cache populé après lecture findConversionRecords
 *   2. {@see InvalidateConversionRateCacheOnOrderStatusChanged} clear cache
 *   3. {@see SendConversionRateRedAlertOnOrderStatusChanged} fire si taux < seuil
 *   4. Pas d'alerte si taux au-dessus du seuil
 *   5. Pas d'alerte sur pipeline vide (spam guard)
 */
final class OrderStatusChangedEventConversionRateFlowTest extends KernelTestCase
{
    use Factories;
    use MultiTenantTestTrait;
    use ResetDatabase;

    private CacheItemPoolInterface $kpiCache;
    private ComputeConversionRateKpiHandler $computeConversion;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->setUpMultiTenant();

        $this->kpiCache = static::getContainer()->get('cache.kpi');
        $this->computeConversion = static::getContainer()->get(ComputeConversionRateKpiHandler::class);
        $this->now = new DateTimeImmutable('2026-06-10T00:00:00+00:00');

        $this->kpiCache->clear();

        ProjectFactory::createOne(['company' => $this->getTestCompany()]);
    }

    public function testCachePopulatedAfterReadAndClearedOnOrderStatusChangedEvent(): void
    {
        $this->createOrderAtDaysAgo(status: 'signe', daysAgo: 5);

        $repository = static::getContainer()->get(ConversionRateReadModelRepositoryInterface::class);
        $beforeDispatch = $repository->findConversionRecords($this->now);
        self::assertCount(1, $beforeDispatch);

        $cacheKey = sprintf(
            'conversion_rate.records.company_%d.day_%s',
            $this->getTestCompany()->getId(),
            $this->now->format('Y-m-d'),
        );
        self::assertTrue($this->kpiCache->hasItem($cacheKey), 'cache populated after first read');

        $invalidator = static::getContainer()->get(InvalidateConversionRateCacheOnOrderStatusChanged::class);
        $invalidator($this->makeOrderEvent());

        self::assertFalse(
            $this->kpiCache->hasItem($cacheKey),
            'cache.kpi cleared by InvalidateConversionRateCacheOnOrderStatusChanged',
        );
    }

    public function testSlackAlertFiresWhenRate30BelowRedThreshold(): void
    {
        // Taux 30j : 0/3 = 0 % < seuil 25 %
        $this->createOrderAtDaysAgo(status: 'perdu', daysAgo: 5);
        $this->createOrderAtDaysAgo(status: 'perdu', daysAgo: 10);
        $this->createOrderAtDaysAgo(status: 'abandonne', daysAgo: 15);

        $slackSpy = new ConversionRateSlackAlertingSpy();
        $listener = new SendConversionRateRedAlertOnOrderStatusChanged(
            computeConversionRateKpi: $this->computeConversion,
            slackAlertingService: $slackSpy,
            logger: new NullLogger(),
        );

        $listener($this->makeOrderEvent());

        self::assertSame(1, $slackSpy->callCount, 'alerte fire taux 0% < seuil 25%');
        self::assertSame(AlertSeverity::CRITICAL, $slackSpy->lastSeverity);
    }

    public function testNoAlertWhenRate30AboveRedThreshold(): void
    {
        // Taux 30j : 2/2 = 100 % > seuil 25 %
        $this->createOrderAtDaysAgo(status: 'signe', daysAgo: 5);
        $this->createOrderAtDaysAgo(status: 'gagne', daysAgo: 10);

        $slackSpy = new ConversionRateSlackAlertingSpy();
        $listener = new SendConversionRateRedAlertOnOrderStatusChanged(
            computeConversionRateKpi: $this->computeConversion,
            slackAlertingService: $slackSpy,
            logger: new NullLogger(),
        );

        $listener($this->makeOrderEvent());

        self::assertSame(0, $slackSpy->callCount);
    }

    public function testNoAlertWhenPipelineEmpty(): void
    {
        $slackSpy = new ConversionRateSlackAlertingSpy();
        $listener = new SendConversionRateRedAlertOnOrderStatusChanged(
            computeConversionRateKpi: $this->computeConversion,
            slackAlertingService: $slackSpy,
            logger: new NullLogger(),
        );

        $listener($this->makeOrderEvent());

        self::assertSame(0, $slackSpy->callCount, 'pas d\'alerte sur pipeline vide (spam guard)');
    }

    public function testStandbyExcludedFromDenominator(): void
    {
        // 1 signé + 2 standby → calculator exclut standby
        // → taux = 1/1 = 100 % (au-dessus seuil)
        // Vérifie surtout que la query repo récupère uniquement
        // les statuts contribuant + que le résultat est cohérent.
        $this->createOrderAtDaysAgo(status: 'signe', daysAgo: 5);
        $this->createOrderAtDaysAgo(status: 'standby', daysAgo: 10);
        $this->createOrderAtDaysAgo(status: 'standby', daysAgo: 15);

        $rate = ($this->computeConversion)($this->now);

        // Repo filtre déjà standby → emitted30 = 1 (signe)
        self::assertSame(1, $rate->emitted30Count);
        self::assertSame(1, $rate->converted30Count);
        self::assertSame(100.0, $rate->rate30Percent);
    }

    private function createOrderAtDaysAgo(string $status, int $daysAgo): void
    {
        // L'OrderFactory met `createdAt` aléatoire. On l'override en passant
        // l'option mais le mapping Doctrine `createdAt` est protected et géré
        // par Gedmo Timestampable → on doit forcer via update direct post-create.
        $order = OrderFactory::createOne([
            'company' => $this->getTestCompany(),
            'status' => $status,
        ]);

        $em = static::getContainer()->get('doctrine')->getManager();
        $em->getConnection()->executeStatement(
            'UPDATE orders SET created_at = :createdAt WHERE id = :id',
            [
                'createdAt' => $this->now->modify(sprintf('-%d days', $daysAgo))->format('Y-m-d H:i:s'),
                'id' => $order->id,
            ],
        );
        $em->clear();
    }

    private function makeOrderEvent(): OrderStatusChangedEvent
    {
        // Constructeur direct pour ancrer occurredOn sur $this->now (testabilité).
        return new OrderStatusChangedEvent(
            orderId: OrderId::fromLegacyInt(1),
            previousStatus: OrderStatus::TO_SIGN,
            newStatus: OrderStatus::SIGNED,
            occurredOn: $this->now,
        );
    }
}

final class ConversionRateSlackAlertingSpy implements SlackAlertingInterface
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
