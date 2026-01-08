<?php

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

        $this->assertSame(3, $this->repository->countActiveProjects());

        $stats = $this->repository->getProjectsByStatus();
        $this->assertSame(3, (int) ($stats['active'] ?? 0));
        $this->assertSame(2, (int) ($stats['completed'] ?? 0));
        $this->assertSame(1, (int) ($stats['cancelled'] ?? 0));
    }

    public function testFindAllOrderedByName(): void
    {
        ProjectFactory::createOne(['name' => 'Zulu Project']);
        ProjectFactory::createOne(['name' => 'Alpha Project']);
        ProjectFactory::createOne(['name' => 'Beta Project']);

        $projects = $this->repository->findAllOrderedByName();

        $this->assertCount(3, $projects);
        $this->assertEquals('Alpha Project', $projects[0]->getName());
        $this->assertEquals('Beta Project', $projects[1]->getName());
        $this->assertEquals('Zulu Project', $projects[2]->getName());
    }

    public function testFindActiveOrderedByName(): void
    {
        ProjectFactory::createOne(['name' => 'Active B', 'status' => 'active']);
        ProjectFactory::createOne(['name' => 'Active A', 'status' => 'active']);
        ProjectFactory::createOne(['name' => 'Completed C', 'status' => 'completed']);

        $projects = $this->repository->findActiveOrderedByName();

        $this->assertCount(2, $projects);
        $this->assertEquals('Active A', $projects[0]->getName());
        $this->assertEquals('Active B', $projects[1]->getName());
    }

    public function testFindRecentProjects(): void
    {
        // Create projects (createdAt is auto-generated)
        ProjectFactory::createOne(['name' => 'Old Project']);
        sleep(1); // Ensure different timestamps
        ProjectFactory::createOne(['name' => 'Recent Project']);

        $projects = $this->repository->findRecentProjects(5);

        $this->assertLessThanOrEqual(5, count($projects));
        // Most recent first
        $this->assertEquals('Recent Project', $projects[0]->getName());
    }

    public function testFindRecentProjectsRespectsLimit(): void
    {
        ProjectFactory::createMany(10);

        $projects = $this->repository->findRecentProjects(3);

        $this->assertCount(3, $projects);
    }

    public function testSearchProjects(): void
    {
        ProjectFactory::createOne(['name' => 'E-commerce Platform']);
        ProjectFactory::createOne(['name' => 'Mobile App']);
        ProjectFactory::createOne(['name' => 'E-learning System']);

        $results = $this->repository->searchProjects('e-');

        $this->assertCount(2, $results);
    }

    public function testSearchProjectsIsCaseInsensitive(): void
    {
        ProjectFactory::createOne(['name' => 'PROJECT Alpha']);

        $results = $this->repository->searchProjects('project');

        $this->assertCount(1, $results);
        $this->assertEquals('PROJECT Alpha', $results[0]->getName());
    }

    public function testFindActiveBetweenDates(): void
    {
        $start = new DateTime('2025-01-01');
        $end   = new DateTime('2025-12-31');

        // Project overlapping the period
        ProjectFactory::createOne([
            'status'    => 'active',
            'startDate' => new DateTime('2025-06-01'),
            'endDate'   => new DateTime('2025-08-31'),
        ]);

        // Project outside the period
        ProjectFactory::createOne([
            'status'    => 'active',
            'startDate' => new DateTime('2024-01-01'),
            'endDate'   => new DateTime('2024-12-31'),
        ]);

        // Completed project in period (should be excluded)
        ProjectFactory::createOne([
            'status'    => 'completed',
            'startDate' => new DateTime('2025-03-01'),
            'endDate'   => new DateTime('2025-05-31'),
        ]);

        $projects = $this->repository->findActiveBetweenDates($start, $end);

        $this->assertCount(1, $projects);
    }

    public function testGetDistinctProjectTypes(): void
    {
        ProjectFactory::createOne(['projectType' => 'forfait']);
        ProjectFactory::createOne(['projectType' => 'regie']);
        ProjectFactory::createOne(['projectType' => 'forfait']); // Duplicate

        $types = $this->repository->getDistinctProjectTypes();

        $this->assertCount(2, $types);
        $this->assertContains('forfait', $types);
        $this->assertContains('regie', $types);
    }

    public function testGetDistinctStatuses(): void
    {
        ProjectFactory::createOne(['status' => 'active']);
        ProjectFactory::createOne(['status' => 'completed']);
        ProjectFactory::createOne(['status' => 'active']); // Duplicate

        $statuses = $this->repository->getDistinctStatuses();

        $this->assertCount(2, $statuses);
        $this->assertContains('active', $statuses);
        $this->assertContains('completed', $statuses);
    }

    public function testSearch(): void
    {
        ProjectFactory::createOne(['name' => 'Website Redesign']);
        ProjectFactory::createOne(['name' => 'Mobile App']);

        $results = $this->repository->search('website', 10);

        $this->assertCount(1, $results);
        $this->assertEquals('Website Redesign', $results[0]->getName());
    }

    public function testSearchRespectsLimit(): void
    {
        ProjectFactory::createMany(10, ['name' => 'Test Project']);

        $results = $this->repository->search('test', 3);

        $this->assertCount(3, $results);
    }

    public function testGetTotalRevenueReturnsZeroWhenNoOrders(): void
    {
        ProjectFactory::createMany(3);

        $revenue = $this->repository->getTotalRevenue();

        $this->assertEquals('0', $revenue);
    }

    public function testGetTotalRevenueOnlyCountsSignedOrders(): void
    {
        $project = ProjectFactory::createOne();

        // Signed order - should be counted
        OrderFactory::createOne([
            'project'     => $project,
            'status'      => 'signe',
            'totalAmount' => '10000.00',
        ]);

        // Pending order - should NOT be counted
        OrderFactory::createOne([
            'project'     => $project,
            'status'      => 'a_signer',
            'totalAmount' => '5000.00',
        ]);

        $revenue = $this->repository->getTotalRevenue();

        // Revenue should be 10000 (may or may not have decimal places)
        $this->assertEquals(0, bccomp($revenue, '10000.00', 2));
    }

    public function testGetAggregatedMetricsForReturnsEmptyArrayWhenNoProjects(): void
    {
        $metrics = $this->repository->getAggregatedMetricsFor([]);

        $this->assertEmpty($metrics);
    }

    public function testGetAggregatedMetricsForReturnsCorrectStructure(): void
    {
        $project = ProjectFactory::createOne();

        $metrics = $this->repository->getAggregatedMetricsFor([$project->getId()]);

        $this->assertIsArray($metrics);
        $this->assertArrayHasKey($project->getId(), $metrics);

        $projectMetrics = $metrics[$project->getId()];
        $this->assertArrayHasKey('total_revenue', $projectMetrics);
        $this->assertArrayHasKey('total_margin', $projectMetrics);
        $this->assertArrayHasKey('total_purchases', $projectMetrics);
        $this->assertArrayHasKey('orders_count', $projectMetrics);
        $this->assertArrayHasKey('signed_orders_count', $projectMetrics);
    }

    public function testGetTotalPurchasesForProjectsReturnsZeroWhenEmpty(): void
    {
        $total = $this->repository->getTotalPurchasesForProjects([]);

        $this->assertEquals('0', $total);
    }

    public function testFindOneWithRelationsLoadsRelations(): void
    {
        $client = ClientFactory::createOne();
        $user   = UserFactory::createOne();

        $project = ProjectFactory::createOne([
            'client'         => $client,
            'projectManager' => $user,
        ]);

        $result = $this->repository->findOneWithRelations($project->getId());

        $this->assertNotNull($result);
        $this->assertEquals($project->getId(), $result->getId());
        // Relations should be loaded
        $this->assertNotNull($result->getClient());
        $this->assertEquals($client->getId(), $result->getClient()->getId());
    }

    public function testFindOneWithRelationsReturnsNullWhenNotFound(): void
    {
        $result = $this->repository->findOneWithRelations(99999);

        $this->assertNull($result);
    }
}
