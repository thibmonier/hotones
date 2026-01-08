<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Entity\Client;
use App\Entity\Order;
use App\Entity\OrderLine;
use App\Entity\OrderSection;
use App\Entity\Project;
use App\Repository\OrderRepository;
use App\Tests\Support\MultiTenantTestTrait;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class OrderRepositoryTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;
    use MultiTenantTestTrait;

    private EntityManagerInterface $entityManager;
    private OrderRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->repository    = $this->entityManager->getRepository(Order::class);
        $this->setUpMultiTenant();
    }

    #[Override]
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    public function testFindWithFiltersNoFilters(): void
    {
        // Arrange
        $project = $this->createProject('Test Project');
        $order1  = $this->createOrder($project, 'Order 1', 'draft');
        sleep(1); // Ensure different timestamps
        $order2 = $this->createOrder($project, 'Order 2', 'signe');

        // Act
        $results = $this->repository->findWithFilters();

        // Assert
        $this->assertGreaterThanOrEqual(2, count($results));
        // Just verify both orders exist in results
        $ids = array_map(fn ($o) => $o->id, $results);
        $this->assertContains($order1->id, $ids);
        $this->assertContains($order2->id, $ids);
    }

    public function testFindWithFiltersWithProject(): void
    {
        // Arrange
        $project1 = $this->createProject('Project 1');
        $project2 = $this->createProject('Project 2');
        $order1   = $this->createOrder($project1, 'Order 1');
        $order2   = $this->createOrder($project2, 'Order 2');

        // Act
        $results = $this->repository->findWithFilters(project: $project1);

        // Assert
        $this->assertCount(1, $results);
        $this->assertEquals($order1->id, $results[0]->id);
    }

    public function testFindWithFiltersWithStatus(): void
    {
        // Arrange
        $project = $this->createProject('Test Project');
        $order1  = $this->createOrder($project, 'Order 1', 'draft');
        $order2  = $this->createOrder($project, 'Order 2', 'signe');

        // Act
        $results = $this->repository->findWithFilters(status: 'signe');

        // Assert
        $this->assertCount(1, $results);
        $this->assertEquals($order2->id, $results[0]->id);
    }

    public function testFindWithFiltersSorting(): void
    {
        // Arrange
        $project = $this->createProject('Test Project');
        $order1  = $this->createOrder($project, 'Alpha', 'draft', 1000.0);
        $order2  = $this->createOrder($project, 'Beta', 'draft', 2000.0);

        // Act - Sort by name
        $results = $this->repository->findWithFilters(sortField: 'name', sortDir: 'ASC');

        // Assert
        $this->assertEquals($order1->id, $results[0]->id);
        $this->assertEquals($order2->id, $results[1]->id);

        // Act - Sort by total
        $results = $this->repository->findWithFilters(sortField: 'total', sortDir: 'DESC');

        // Assert
        $this->assertEquals($order2->id, $results[0]->id);
        $this->assertEquals($order1->id, $results[1]->id);
    }

    public function testFindWithFiltersPagination(): void
    {
        // Arrange
        $project = $this->createProject('Test Project');
        for ($i = 1; $i <= 5; ++$i) {
            $this->createOrder($project, "Order $i");
        }

        // Act
        $results = $this->repository->findWithFilters(limit: 2, offset: 1);

        // Assert
        $this->assertCount(2, $results);
    }

    public function testCountWithFilters(): void
    {
        // Arrange
        $project = $this->createProject('Test Project');
        $this->createOrder($project, 'Order 1', 'draft');
        $this->createOrder($project, 'Order 2', 'signe');
        $this->createOrder($project, 'Order 3', 'signe');

        // Act
        $total        = $this->repository->countWithFilters();
        $totalProject = $this->repository->countWithFilters(project: $project);
        $totalStatus  = $this->repository->countWithFilters(status: 'signe');

        // Assert
        $this->assertEquals(3, $total);
        $this->assertEquals(3, $totalProject);
        $this->assertEquals(2, $totalStatus);
    }

    public function testFindLastOrderNumberForMonth(): void
    {
        // Arrange
        $project = $this->createProject('Test Project');
        $order1  = $this->createOrder($project, 'Order 1');
        $order1->setOrderNumber('D202401-001');
        $order2 = $this->createOrder($project, 'Order 2');
        $order2->setOrderNumber('D202401-002');
        $order3 = $this->createOrder($project, 'Order 3');
        $order3->setOrderNumber('D202402-001');
        $this->entityManager->flush();

        // Act
        $result = $this->repository->findLastOrderNumberForMonth('2024', '01');

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals('D202401-002', $result->getOrderNumber());
    }

    public function testFindLastOrderNumberForMonthNoResults(): void
    {
        // Act
        $result = $this->repository->findLastOrderNumberForMonth('2024', '01');

        // Assert
        $this->assertNull($result);
    }

    public function testFindByProject(): void
    {
        // Arrange
        $project1 = $this->createProject('Project 1');
        $project2 = $this->createProject('Project 2');
        $order1   = $this->createOrder($project1, 'Order 1');
        $order2   = $this->createOrder($project1, 'Order 2');
        $order3   = $this->createOrder($project2, 'Order 3');

        // Act
        $results = $this->repository->findByProject($project1);

        // Assert
        $this->assertCount(2, $results);
    }

    public function testFindByStatus(): void
    {
        // Arrange
        $project = $this->createProject('Test Project');
        $this->createOrder($project, 'Order 1', 'draft');
        $this->createOrder($project, 'Order 2', 'signe');
        $this->createOrder($project, 'Order 3', 'signe');

        // Act
        $results = $this->repository->findByStatus('signe');

        // Assert
        $this->assertCount(2, $results);
    }

    public function testFindOneWithRelations(): void
    {
        // Arrange
        $project = $this->createProject('Test Project');
        $order   = $this->createOrder($project, 'Order 1');

        $section = new OrderSection();
        $section->setCompany($this->getTestCompany());
        $section->setOrder($order);
        $section->setTitle('Section 1');
        $section->setPosition(1);
        $this->entityManager->persist($section);

        $line = new OrderLine();
        $line->setCompany($this->getTestCompany());
        $line->setSection($section);
        $line->setDescription('Line 1');
        $line->setType('service');
        $line->setDays('1');
        $line->setPosition(1);
        $this->entityManager->persist($line);

        $this->entityManager->flush();
        $this->entityManager->clear(); // Clear to force fresh query

        // Act
        $result = $this->repository->findOneWithRelations($order->id);

        // Assert
        $this->assertNotNull($result);
        $sections = $result->getSections();
        $this->assertCount(1, $sections);
        $firstSection = $sections->first();
        $this->assertNotNull($firstSection);
        $this->assertCount(1, $firstSection->getLines());
    }

    public function testPreloadForProjects(): void
    {
        // Arrange
        $project1 = $this->createProject('Project 1');
        $project2 = $this->createProject('Project 2');
        $order1   = $this->createOrder($project1, 'Order 1');
        $order2   = $this->createOrder($project2, 'Order 2');

        // Act (should not throw exception)
        $this->repository->preloadForProjects([$project1, $project2]);

        // Assert - Just verify it executes without error
        $this->assertTrue(true);
    }

    public function testPreloadForProjectsEmpty(): void
    {
        // Act (should not throw exception)
        $this->repository->preloadForProjects([]);

        // Assert
        $this->assertTrue(true);
    }

    public function testCountByStatus(): void
    {
        // Arrange
        $project = $this->createProject('Test Project');
        $this->createOrder($project, 'Order 1', 'draft');
        $this->createOrder($project, 'Order 2', 'signe');
        $this->createOrder($project, 'Order 3', 'signe');

        // Act
        $draftCount  = $this->repository->countByStatus('draft');
        $signeCount  = $this->repository->countByStatus('signe');
        $perdueCount = $this->repository->countByStatus('perdue');

        // Assert
        $this->assertEquals(1, $draftCount);
        $this->assertEquals(2, $signeCount);
        $this->assertEquals(0, $perdueCount);
    }

    public function testGetTotalAmountByStatus(): void
    {
        // Arrange
        $project = $this->createProject('Test Project');
        $this->createOrder($project, 'Order 1', 'signe', 1000.0);
        $this->createOrder($project, 'Order 2', 'signe', 2000.0);
        $this->createOrder($project, 'Order 3', 'draft', 500.0);

        // Act
        $total = $this->repository->getTotalAmountByStatus('signe');

        // Assert
        $this->assertEquals(3000.0, $total);
    }

    public function testGetStatsByStatus(): void
    {
        // Arrange
        $project = $this->createProject('Test Project');
        $this->createOrder($project, 'Order 1', 'draft', 1000.0);
        $this->createOrder($project, 'Order 2', 'signe', 2000.0);
        $this->createOrder($project, 'Order 3', 'signe', 3000.0);

        // Act
        $stats = $this->repository->getStatsByStatus();

        // Assert
        $this->assertArrayHasKey('draft', $stats);
        $this->assertArrayHasKey('signe', $stats);
        $this->assertEquals(1, $stats['draft']['count']);
        $this->assertEquals(1000.0, $stats['draft']['total']);
        $this->assertEquals(2, $stats['signe']['count']);
        $this->assertEquals(5000.0, $stats['signe']['total']);
    }

    public function testGetStatsByStatusWithDateRange(): void
    {
        // Arrange
        $project = $this->createProject('Test Project');
        $order1  = $this->createOrder($project, 'Order 1', 'signe', 1000.0);
        $order2  = $this->createOrder($project, 'Order 2', 'signe', 2000.0);

        // Modify creation dates
        $order1->setCreatedAt(new DateTimeImmutable('2024-01-15'));
        $order2->setCreatedAt(new DateTimeImmutable('2024-02-15'));
        $this->entityManager->flush();

        // Act
        $stats = $this->repository->getStatsByStatus(
            new DateTime('2024-01-01'),
            new DateTime('2024-01-31'),
        );

        // Assert
        $this->assertEquals(1, $stats['signe']['count']);
        $this->assertEquals(1000.0, $stats['signe']['total']);
    }

    public function testGetSignedRevenueForPeriod(): void
    {
        // Arrange
        $project = $this->createProject('Test Project');
        $order1  = $this->createOrder($project, 'Order 1', 'signe', 1000.0);
        $order2  = $this->createOrder($project, 'Order 2', 'gagne', 2000.0);
        $order3  = $this->createOrder($project, 'Order 3', 'draft', 500.0);

        $order1->validatedAt = new DateTime('2024-01-15');
        $order2->validatedAt = new DateTime('2024-01-20');
        $this->entityManager->flush();

        // Act
        $revenue = $this->repository->getSignedRevenueForPeriod(
            new DateTime('2024-01-01'),
            new DateTime('2024-01-31'),
        );

        // Assert
        $this->assertEquals(3000.0, $revenue);
    }

    public function testGetRecentOrders(): void
    {
        // Arrange
        $project = $this->createProject('Test Project');
        for ($i = 1; $i <= 15; ++$i) {
            $this->createOrder($project, "Order $i");
        }

        // Act
        $results = $this->repository->getRecentOrders(10);

        // Assert
        $this->assertCount(10, $results);
    }

    public function testGetConversionRate(): void
    {
        // Arrange
        $project = $this->createProject('Test Project');
        $this->createOrder($project, 'Order 1', 'draft');
        $this->createOrder($project, 'Order 2', 'signe');
        $this->createOrder($project, 'Order 3', 'signe');
        $this->createOrder($project, 'Order 4', 'perdue');

        // Act - 2 signed out of 4 total = 50%
        $rate = $this->repository->getConversionRate(
            new DateTime('2020-01-01'),
            new DateTime('2030-12-31'),
        );

        // Assert
        $this->assertEquals(50.0, $rate);
    }

    public function testGetConversionRateNoOrders(): void
    {
        // Act
        $rate = $this->repository->getConversionRate(
            new DateTime('2024-01-01'),
            new DateTime('2024-12-31'),
        );

        // Assert
        $this->assertEquals(0.0, $rate);
    }

    public function testGetYearComparison(): void
    {
        // Arrange
        $project = $this->createProject('Test Project');

        // 2023 orders
        $order2023a = $this->createOrder($project, 'Order 2023-1', 'signe', 1000.0);
        $order2023a->setCreatedAt(new DateTimeImmutable('2023-03-01'));
        $order2023a->validatedAt = new DateTime('2023-03-15');

        $order2023b = $this->createOrder($project, 'Order 2023-2', 'draft', 500.0);
        $order2023b->setCreatedAt(new DateTimeImmutable('2023-06-01'));

        // 2024 orders
        $order2024a = $this->createOrder($project, 'Order 2024-1', 'signe', 2000.0);
        $order2024a->setCreatedAt(new DateTimeImmutable('2024-03-01'));
        $order2024a->validatedAt = new DateTime('2024-03-15');

        $order2024b = $this->createOrder($project, 'Order 2024-2', 'signe', 3000.0);
        $order2024b->setCreatedAt(new DateTimeImmutable('2024-06-01'));
        $order2024b->validatedAt = new DateTime('2024-06-15');

        $this->entityManager->flush();

        // Act
        $comparison = $this->repository->getYearComparison(2024, 2023);

        // Assert
        $this->assertEquals(2024, $comparison['current']['year']);
        $this->assertEquals(2023, $comparison['previous']['year']);
        $this->assertEquals(5000.0, $comparison['current']['revenue']);
        $this->assertEquals(1000.0, $comparison['previous']['revenue']);
        $this->assertEquals(2, $comparison['current']['count']);
        $this->assertEquals(2, $comparison['previous']['count']);
    }

    public function testCountOrdersInPeriod(): void
    {
        // Arrange
        $project = $this->createProject('Test Project');
        $order1  = $this->createOrder($project, 'Order 1');
        $order2  = $this->createOrder($project, 'Order 2');
        $order3  = $this->createOrder($project, 'Order 3');

        $order1->setCreatedAt(new DateTimeImmutable('2024-01-15'));
        $order2->setCreatedAt(new DateTimeImmutable('2024-02-15'));
        $order3->setCreatedAt(new DateTimeImmutable('2024-03-15'));
        $this->entityManager->flush();

        // Act
        $count = $this->repository->countOrdersInPeriod(
            new DateTime('2024-01-01'),
            new DateTime('2024-02-28'),
        );

        // Assert
        $this->assertEquals(2, $count);
    }

    public function testSearch(): void
    {
        // Arrange
        $client  = $this->createClient('ACME Corp');
        $project = $this->createProjectWithClient('Test Project', $client);
        $order1  = $this->createOrder($project, 'Website Redesign');
        $order1->setOrderNumber('D202401-001');
        $order2 = $this->createOrder($project, 'Mobile App');
        $order2->setOrderNumber('D202401-002');
        $this->entityManager->flush();

        // Act - Search by order number
        $results1 = $this->repository->search('D202401-001');

        // Act - Search by name
        $results2 = $this->repository->search('Website');

        // Act - Search by client name
        $results3 = $this->repository->search('ACME');

        // Assert
        $this->assertCount(1, $results1);
        $this->assertEquals($order1->id, $results1[0]->id);

        $this->assertCount(1, $results2);
        $this->assertEquals($order1->id, $results2[0]->id);

        $this->assertCount(2, $results3);
    }

    public function testSearchWithLimit(): void
    {
        // Arrange
        $project = $this->createProject('Test Project');
        for ($i = 1; $i <= 10; ++$i) {
            $order = $this->createOrder($project, "Order $i");
            $order->setOrderNumber('D202401-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT));
        }
        $this->entityManager->flush();

        // Act
        $results = $this->repository->search('D202401', 3);

        // Assert
        $this->assertCount(3, $results);
    }

    // Helper methods

    private function createClient(string $name): Client
    {
        $client = new Client();
        $client->setCompany($this->getTestCompany());
        $client->setName($name);
        $this->entityManager->persist($client);
        $this->entityManager->flush();

        return $client;
    }

    private function createProject(string $name): Project
    {
        $client = new Client();
        $client->setCompany($this->getTestCompany());
        $client->setName('Test Client');
        $this->entityManager->persist($client);

        $project = new Project();
        $project->setCompany($this->getTestCompany());
        $project->setName($name);
        $project->setClient($client);
        $project->setProjectType('forfait');
        $project->setStartDate(new DateTime());
        $this->entityManager->persist($project);
        $this->entityManager->flush();

        return $project;
    }

    private function createProjectWithClient(string $name, Client $client): Project
    {
        $project = new Project();
        $project->setCompany($this->getTestCompany());
        $project->setName($name);
        $project->setClient($client);
        $project->setProjectType('forfait');
        $project->setStartDate(new DateTime());
        $this->entityManager->persist($project);
        $this->entityManager->flush();

        return $project;
    }

    private function createOrder(
        Project $project,
        string $name,
        string $status = 'draft',
        float $totalAmount = 0.0
    ): Order {
        $order = new Order();
        $order->setCompany($this->getTestCompany());
        $order->setProject($project);
        $order->setName($name);
        $order->setStatus($status);
        $order->setOrderNumber('D'.date('Ym').'-'.uniqid());
        $order->setTotalAmount((string) $totalAmount);
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $order;
    }
}
