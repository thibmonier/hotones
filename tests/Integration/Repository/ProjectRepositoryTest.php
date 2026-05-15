<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Factory\ClientFactory;
use App\Factory\OrderFactory;
use App\Factory\ProjectFactory;
use App\Factory\UserFactory;
use App\Repository\ProjectRepository;
use App\Tests\Support\MultiTenantTestTrait;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class ProjectRepositoryTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;
    use MultiTenantTestTrait;

    private ProjectRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = static::getContainer()->get(ProjectRepository::class);
        $this->setUpMultiTenant();
    }

    public function testCountActiveProjectsAndStatusStats(): void
    {
        // Seed
        ProjectFactory::createMany(3, ['status' => 'active']);
        ProjectFactory::createMany(2, ['status' => 'completed']);
        ProjectFactory::createOne(['status' => 'cancelled']);

        static::assertSame(3, $this->repository->countActiveProjects());

        $stats = $this->repository->getProjectsByStatus();
        static::assertSame(3, (int) ($stats['active'] ?? 0));
        static::assertSame(2, (int) ($stats['completed'] ?? 0));
        static::assertSame(1, (int) ($stats['cancelled'] ?? 0));
    }

    public function testFindAllOrderedByName(): void
    {
        ProjectFactory::createOne(['name' => 'Zulu Project']);
        ProjectFactory::createOne(['name' => 'Alpha Project']);
        ProjectFactory::createOne(['name' => 'Beta Project']);

        $projects = $this->repository->findAllOrderedByName();

        static::assertCount(3, $projects);
        static::assertSame('Alpha Project', $projects[0]->getName());
        static::assertSame('Beta Project', $projects[1]->getName());
        static::assertSame('Zulu Project', $projects[2]->getName());
    }

    public function testFindActiveOrderedByName(): void
    {
        ProjectFactory::createOne(['name' => 'Active B', 'status' => 'active']);
        ProjectFactory::createOne(['name' => 'Active A', 'status' => 'active']);
        ProjectFactory::createOne(['name' => 'Completed C', 'status' => 'completed']);

        $projects = $this->repository->findActiveOrderedByName();

        static::assertCount(2, $projects);
        static::assertSame('Active A', $projects[0]->getName());
        static::assertSame('Active B', $projects[1]->getName());
    }

    public function testFindRecentProjects(): void
    {
        // Create projects (createdAt is auto-generated)
        ProjectFactory::createOne(['name' => 'Old Project']);
        sleep(1); // Ensure different timestamps
        ProjectFactory::createOne(['name' => 'Recent Project']);

        $projects = $this->repository->findRecentProjects(5);

        static::assertLessThanOrEqual(5, count($projects));
        // Most recent first
        static::assertSame('Recent Project', $projects[0]->getName());
    }

    public function testFindRecentProjectsRespectsLimit(): void
    {
        ProjectFactory::createMany(10);

        $projects = $this->repository->findRecentProjects(3);

        static::assertCount(3, $projects);
    }

    public function testSearchProjects(): void
    {
        ProjectFactory::createOne(['name' => 'E-commerce Platform']);
        ProjectFactory::createOne(['name' => 'Mobile App']);
        ProjectFactory::createOne(['name' => 'E-learning System']);

        $results = $this->repository->searchProjects('e-');

        static::assertCount(2, $results);
    }

    public function testSearchProjectsIsCaseInsensitive(): void
    {
        ProjectFactory::createOne(['name' => 'PROJECT Alpha']);

        $results = $this->repository->searchProjects('project');

        static::assertCount(1, $results);
        static::assertSame('PROJECT Alpha', $results[0]->getName());
    }

    public function testFindActiveBetweenDates(): void
    {
        $start = new DateTime('2025-01-01');
        $end = new DateTime('2025-12-31');

        // Project overlapping the period
        ProjectFactory::createOne([
            'status' => 'active',
            'startDate' => new DateTime('2025-06-01'),
            'endDate' => new DateTime('2025-08-31'),
        ]);

        // Project outside the period
        ProjectFactory::createOne([
            'status' => 'active',
            'startDate' => new DateTime('2024-01-01'),
            'endDate' => new DateTime('2024-12-31'),
        ]);

        // Completed project in period (should be excluded)
        ProjectFactory::createOne([
            'status' => 'completed',
            'startDate' => new DateTime('2025-03-01'),
            'endDate' => new DateTime('2025-05-31'),
        ]);

        $projects = $this->repository->findActiveBetweenDates($start, $end);

        static::assertCount(1, $projects);
    }

    public function testGetDistinctProjectTypes(): void
    {
        ProjectFactory::createOne(['projectType' => 'forfait']);
        ProjectFactory::createOne(['projectType' => 'regie']);
        ProjectFactory::createOne(['projectType' => 'forfait']); // Duplicate

        $types = $this->repository->getDistinctProjectTypes();

        static::assertCount(2, $types);
        static::assertContains('forfait', $types);
        static::assertContains('regie', $types);
    }

    public function testGetDistinctStatuses(): void
    {
        ProjectFactory::createOne(['status' => 'active']);
        ProjectFactory::createOne(['status' => 'completed']);
        ProjectFactory::createOne(['status' => 'active']); // Duplicate

        $statuses = $this->repository->getDistinctStatuses();

        static::assertCount(2, $statuses);
        static::assertContains('active', $statuses);
        static::assertContains('completed', $statuses);
    }

    public function testSearch(): void
    {
        ProjectFactory::createOne(['name' => 'Website Redesign']);
        ProjectFactory::createOne(['name' => 'Mobile App']);

        $results = $this->repository->search('website', 10);

        static::assertCount(1, $results);
        static::assertSame('Website Redesign', $results[0]->getName());
    }

    public function testSearchRespectsLimit(): void
    {
        ProjectFactory::createMany(10, ['name' => 'Test Project']);

        $results = $this->repository->search('test', 3);

        static::assertCount(3, $results);
    }

    public function testGetTotalRevenueReturnsZeroWhenNoOrders(): void
    {
        ProjectFactory::createMany(3);

        $revenue = $this->repository->getTotalRevenue();

        static::assertSame('0', $revenue);
    }

    public function testGetTotalRevenueOnlyCountsSignedOrders(): void
    {
        $project = ProjectFactory::createOne();

        // Signed order - should be counted
        OrderFactory::createOne([
            'project' => $project,
            'status' => 'signe',
            'totalAmount' => '10000.00',
        ]);

        // Pending order - should NOT be counted
        OrderFactory::createOne([
            'project' => $project,
            'status' => 'a_signer',
            'totalAmount' => '5000.00',
        ]);

        $revenue = $this->repository->getTotalRevenue();

        // Revenue should be 10000 (may or may not have decimal places)
        static::assertSame(0, bccomp($revenue, '10000.00', 2));
    }

    public function testGetAggregatedMetricsForReturnsEmptyArrayWhenNoProjects(): void
    {
        $metrics = $this->repository->getAggregatedMetricsFor([]);

        static::assertEmpty($metrics);
    }

    public function testGetAggregatedMetricsForReturnsCorrectStructure(): void
    {
        $project = ProjectFactory::createOne();

        $metrics = $this->repository->getAggregatedMetricsFor([$project->getId()]);

        static::assertIsArray($metrics);
        static::assertArrayHasKey($project->getId(), $metrics);

        $projectMetrics = $metrics[$project->getId()];
        static::assertArrayHasKey('total_revenue', $projectMetrics);
        static::assertArrayHasKey('total_margin', $projectMetrics);
        static::assertArrayHasKey('total_purchases', $projectMetrics);
        static::assertArrayHasKey('orders_count', $projectMetrics);
        static::assertArrayHasKey('signed_orders_count', $projectMetrics);
    }

    public function testGetTotalPurchasesForProjectsReturnsZeroWhenEmpty(): void
    {
        $total = $this->repository->getTotalPurchasesForProjects([]);

        static::assertSame('0', $total);
    }

    public function testFindOneWithRelationsLoadsRelations(): void
    {
        $client = ClientFactory::createOne();
        $user = UserFactory::createOne();

        $project = ProjectFactory::createOne([
            'client' => $client,
            'projectManager' => $user,
        ]);

        $result = $this->repository->findOneWithRelations($project->getId());

        static::assertNotNull($result);
        static::assertEquals($project->getId(), $result->getId());
        // Relations should be loaded
        static::assertNotNull($result->getClient());
        static::assertEquals($client->getId(), $result->getClient()->getId());
    }

    public function testFindOneWithRelationsReturnsNullWhenNotFound(): void
    {
        $result = $this->repository->findOneWithRelations(99_999);

        static::assertNull($result);
    }
}
