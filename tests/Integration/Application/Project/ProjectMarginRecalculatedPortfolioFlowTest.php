<?php

declare(strict_types=1);

namespace App\Tests\Integration\Application\Project;

use App\Application\Project\EventListener\InvalidatePortfolioMarginCacheOnProjectMarginRecalculated;
use App\Application\Project\EventListener\SendPortfolioMarginRedAlertOnRecalculated;
use App\Application\Project\Query\PortfolioMarginKpi\ComputePortfolioMarginKpiHandler;
use App\Domain\Project\Event\ProjectMarginRecalculatedEvent;
use App\Domain\Project\Repository\PortfolioMarginReadModelRepositoryInterface;
use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\Shared\ValueObject\Money;
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
 * End-to-end integration test US-117 T-117-06 — Portfolio margin flow.
 *
 * Couvre :
 *   1. Cache populé après lecture findActiveProjectsWithSnapshot
 *   2. {@see InvalidatePortfolioMarginCacheOnProjectMarginRecalculated} clear cache
 *   3. {@see SendPortfolioMarginRedAlertOnRecalculated} fire si marge < seuil
 *   4. Pas d'alerte si marge au-dessus du seuil
 *   5. Pas d'alerte sur portefeuille vide (spam guard)
 *   6. Exclusion projets completed/cancelled au niveau SQL
 *   7. Marge pondérée correcte sur dataset connu
 */
final class ProjectMarginRecalculatedPortfolioFlowTest extends KernelTestCase
{
    use Factories;
    use MultiTenantTestTrait;
    use ResetDatabase;

    private CacheItemPoolInterface $kpiCache;
    private ComputePortfolioMarginKpiHandler $computeKpi;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->setUpMultiTenant();

        $this->kpiCache = static::getContainer()->get('cache.kpi');
        $this->computeKpi = static::getContainer()->get(ComputePortfolioMarginKpiHandler::class);
        $this->now = new DateTimeImmutable('2026-06-10T00:00:00+00:00');

        $this->kpiCache->clear();
    }

    public function testCachePopulatedAfterReadAndClearedOnRecalculatedEvent(): void
    {
        $this->createActiveProjectWithSnapshot(coutCents: 80_000_00, factureCents: 100_000_00);

        $repository = static::getContainer()->get(PortfolioMarginReadModelRepositoryInterface::class);
        $before = $repository->findActiveProjectsWithSnapshot($this->now);
        self::assertCount(1, $before);

        $cacheKey = sprintf(
            'portfolio_margin.snapshot.company_%d.day_%s',
            $this->getTestCompany()->getId(),
            $this->now->format('Y-m-d'),
        );
        self::assertTrue($this->kpiCache->hasItem($cacheKey), 'cache populated after first read');

        $invalidator = static::getContainer()->get(InvalidatePortfolioMarginCacheOnProjectMarginRecalculated::class);
        $invalidator($this->makeEvent());

        self::assertFalse(
            $this->kpiCache->hasItem($cacheKey),
            'cache.kpi cleared by InvalidatePortfolioMarginCacheOnProjectMarginRecalculated',
        );
    }

    public function testSlackAlertFiresWhenAverageMarginBelowRedThreshold(): void
    {
        // marge 5 % < 10 % seuil rouge (cout 95 % du facture)
        $this->createActiveProjectWithSnapshot(coutCents: 95_000_00, factureCents: 100_000_00);
        $this->createActiveProjectWithSnapshot(coutCents: 95_000_00, factureCents: 100_000_00);

        $slackSpy = new PortfolioMarginSlackAlertingSpy();
        $listener = new SendPortfolioMarginRedAlertOnRecalculated(
            computePortfolioMarginKpi: $this->computeKpi,
            slackAlertingService: $slackSpy,
            logger: new NullLogger(),
        );

        $listener($this->makeEvent());

        self::assertSame(1, $slackSpy->callCount, 'alerte fire marge 5 % < seuil 10 %');
        self::assertSame(AlertSeverity::CRITICAL, $slackSpy->lastSeverity);
    }

    public function testNoAlertWhenMarginAboveRedThreshold(): void
    {
        // marge 25 % > 10 % seuil rouge
        $this->createActiveProjectWithSnapshot(coutCents: 75_000_00, factureCents: 100_000_00);

        $slackSpy = new PortfolioMarginSlackAlertingSpy();
        $listener = new SendPortfolioMarginRedAlertOnRecalculated(
            computePortfolioMarginKpi: $this->computeKpi,
            slackAlertingService: $slackSpy,
            logger: new NullLogger(),
        );

        $listener($this->makeEvent());

        self::assertSame(0, $slackSpy->callCount);
    }

    public function testNoAlertWhenPortfolioEmpty(): void
    {
        $slackSpy = new PortfolioMarginSlackAlertingSpy();
        $listener = new SendPortfolioMarginRedAlertOnRecalculated(
            computePortfolioMarginKpi: $this->computeKpi,
            slackAlertingService: $slackSpy,
            logger: new NullLogger(),
        );

        $listener($this->makeEvent());

        self::assertSame(0, $slackSpy->callCount, 'pas d\'alerte sur portefeuille vide (spam guard)');
    }

    public function testCompletedAndCancelledProjectsExcludedFromQuery(): void
    {
        // 1 active + 1 completed + 1 cancelled → repo SQL ne retourne que active
        $this->createActiveProjectWithSnapshot(coutCents: 80_000_00, factureCents: 100_000_00);
        $this->createProjectWithStatus(
            status: 'completed',
            coutCents: 90_000_00,
            factureCents: 100_000_00,
        );
        $this->createProjectWithStatus(
            status: 'cancelled',
            coutCents: 90_000_00,
            factureCents: 100_000_00,
        );

        $kpi = ($this->computeKpi)($this->now);

        self::assertSame(1, $kpi->projectsWithSnapshot);
        self::assertSame(0, $kpi->projectsWithoutSnapshot);
        self::assertEqualsWithDelta(20.0, $kpi->averagePercent, 0.1);
    }

    public function testWeightedAverageOnKnownDataset(): void
    {
        // P1 : facture 100k, cout 80k → marge 20 %
        // P2 : facture 300k, cout 270k → marge 10 %
        // moyenne pondérée = (20 × 100 + 10 × 300) / 400 = 5000 / 400 = 12.5 %
        $this->createActiveProjectWithSnapshot(coutCents: 80_000_00, factureCents: 100_000_00);
        $this->createActiveProjectWithSnapshot(coutCents: 270_000_00, factureCents: 300_000_00);

        $kpi = ($this->computeKpi)($this->now);

        self::assertSame(2, $kpi->projectsWithSnapshot);
        self::assertEqualsWithDelta(12.5, $kpi->averagePercent, 0.1);
    }

    public function testProjectsWithoutSnapshotCountedSeparately(): void
    {
        $this->createActiveProjectWithSnapshot(coutCents: 80_000_00, factureCents: 100_000_00);
        $this->createActiveProjectWithoutSnapshot();

        $kpi = ($this->computeKpi)($this->now);

        self::assertSame(1, $kpi->projectsWithSnapshot);
        self::assertSame(1, $kpi->projectsWithoutSnapshot);
        self::assertSame(2, $kpi->totalActiveProjects());
    }

    private function createActiveProjectWithSnapshot(int $coutCents, int $factureCents): void
    {
        $this->createProjectWithStatus(
            status: 'active',
            coutCents: $coutCents,
            factureCents: $factureCents,
        );
    }

    private function createActiveProjectWithoutSnapshot(): void
    {
        ProjectFactory::createOne([
            'company' => $this->getTestCompany(),
            'status' => 'active',
        ]);
    }

    private function createProjectWithStatus(string $status, int $coutCents, int $factureCents): void
    {
        $project = ProjectFactory::createOne([
            'company' => $this->getTestCompany(),
            'status' => $status,
        ]);

        $em = static::getContainer()->get('doctrine')->getManager();
        $em->getConnection()->executeStatement(
            'UPDATE projects SET cout_total_cents = :cout, facture_total_cents = :facture, marge_calculated_at = :calculatedAt WHERE id = :id',
            [
                'cout' => $coutCents,
                'facture' => $factureCents,
                'calculatedAt' => $this->now->format('Y-m-d H:i:s'),
                'id' => $project->id,
            ],
        );
        // Pas de em->clear() : détache l'utilisateur authentifié et casse
        // ProjectEventSubscriber sur les créations suivantes (cascade actor).
        // Doctrine read via getArrayResult bypasse l'identity map.
    }

    private function makeEvent(): ProjectMarginRecalculatedEvent
    {
        return ProjectMarginRecalculatedEvent::create(
            projectId: ProjectId::fromLegacyInt(1),
            projectName: 'Test Project',
            costTotal: Money::fromCents(80_000_00),
            invoicedPaidTotal: Money::fromCents(100_000_00),
            marginPercent: 20.0,
        );
    }
}

final class PortfolioMarginSlackAlertingSpy implements SlackAlertingInterface
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
