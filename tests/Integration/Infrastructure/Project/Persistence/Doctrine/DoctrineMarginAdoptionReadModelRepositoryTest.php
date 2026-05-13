<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Project\Persistence\Doctrine;

use App\Domain\Project\Service\MarginAdoptionCalculator;
use App\Domain\Project\Service\ProjectMarginSnapshotRecord;
use App\Factory\ProjectFactory;
use App\Infrastructure\Project\Persistence\Doctrine\DoctrineMarginAdoptionReadModelRepository;
use App\Tests\Support\MultiTenantTestTrait;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Integration tests for {@see DoctrineMarginAdoptionReadModelRepository}.
 *
 * Verifies DQL projection + multi-tenant + status='active' filter + NULL
 * marginCalculatedAt passthrough.
 */
final class DoctrineMarginAdoptionReadModelRepositoryTest extends KernelTestCase
{
    use Factories;
    use MultiTenantTestTrait;
    use ResetDatabase;

    private DoctrineMarginAdoptionReadModelRepository $repository;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->setUpMultiTenant();
        $this->repository = static::getContainer()->get(DoctrineMarginAdoptionReadModelRepository::class);
        $this->now = new DateTimeImmutable('2026-05-12T00:00:00+00:00');
    }

    public function testReturnsEmptyArrayWhenNoProjects(): void
    {
        self::assertSame([], $this->repository->findActiveWithMarginSnapshot());
    }

    public function testReturnsActiveProjectsWithMarginSnapshot(): void
    {
        // Active + récent
        $this->createProject('Fresh', status: 'active', daysAgoMargin: 3);
        // Active + warning
        $this->createProject('Warning', status: 'active', daysAgoMargin: 15);
        // Active + critical
        $this->createProject('Critical', status: 'active', daysAgoMargin: 60);
        // Active + jamais calculé
        $this->createProject('Never', status: 'active', daysAgoMargin: null);

        $records = $this->repository->findActiveWithMarginSnapshot();

        self::assertCount(4, $records);
        foreach ($records as $record) {
            self::assertInstanceOf(ProjectMarginSnapshotRecord::class, $record);
        }

        $names = array_map(static fn (ProjectMarginSnapshotRecord $r): string => $r->projectName, $records);
        self::assertContains('Fresh', $names);
        self::assertContains('Never', $names);
    }

    public function testExcludesCompletedAndCancelledProjects(): void
    {
        $this->createProject('ActiveOnly', status: 'active', daysAgoMargin: 5);
        $this->createProject('Completed', status: 'completed', daysAgoMargin: 5);
        $this->createProject('Cancelled', status: 'cancelled', daysAgoMargin: 5);

        $records = $this->repository->findActiveWithMarginSnapshot();

        self::assertCount(1, $records);
        self::assertSame('ActiveOnly', $records[0]->projectName);
    }

    public function testFiltersByCurrentCompany(): void
    {
        $this->createProject('Own', status: 'active', daysAgoMargin: 5);

        $otherCompany = $this->createTestCompany('Other Tenant');
        ProjectFactory::createOne([
            'company' => $otherCompany,
            'name' => 'OtherTenantProject',
            'status' => 'active',
            'margeCalculatedAt' => $this->now->modify('-5 days'),
        ]);

        $records = $this->repository->findActiveWithMarginSnapshot();

        self::assertCount(1, $records);
        self::assertSame('Own', $records[0]->projectName);
    }

    public function testRecordsConsumableByCalculator(): void
    {
        $this->createProject('Fresh1', status: 'active', daysAgoMargin: 2);
        $this->createProject('Fresh2', status: 'active', daysAgoMargin: 5);
        $this->createProject('Warning', status: 'active', daysAgoMargin: 15);
        $this->createProject('Critical', status: 'active', daysAgoMargin: 60);
        $this->createProject('Never', status: 'active', daysAgoMargin: null);

        $records = $this->repository->findActiveWithMarginSnapshot();
        $stats = (new MarginAdoptionCalculator())->classify($records, $this->now);

        self::assertSame(5, $stats->totalActive);
        self::assertSame(2, $stats->freshCount);
        self::assertSame(1, $stats->staleWarningCount);
        self::assertSame(2, $stats->staleCriticalCount);
        self::assertEqualsWithDelta(40.0, $stats->freshPercent, 0.5);
    }

    private function createProject(string $name, string $status, ?int $daysAgoMargin): void
    {
        $marginAt = $daysAgoMargin === null
            ? null
            : $this->now->modify(sprintf('-%d days', $daysAgoMargin));

        ProjectFactory::createOne([
            'company' => $this->getTestCompany(),
            'name' => $name,
            'status' => $status,
            'margeCalculatedAt' => $marginAt,
        ]);
    }
}
