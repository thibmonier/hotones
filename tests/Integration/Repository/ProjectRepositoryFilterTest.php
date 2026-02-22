<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Entity\Project;
use App\Repository\ProjectRepository;
use App\Tests\Support\MultiTenantTestTrait;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class ProjectRepositoryFilterTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;
    use MultiTenantTestTrait;

    private ProjectRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->setUpMultiTenant();
        $this->repository = static::getContainer()->get(ProjectRepository::class);
    }

    private function createProject(string $name, string $status = 'active', string $type = 'forfait', ?DateTime $start = null, ?DateTime $end = null): Project
    {
        $project = new Project();
        $project->setName($name);
        $project->setCompany($this->getTestCompany());
        $project->setStatus($status);
        $project->setProjectType($type);
        $project->setStartDate($start ?? new DateTime('2026-03-01'));
        $project->setEndDate($end ?? new DateTime('2026-06-30'));

        $em = $this->getEntityManager();
        $em->persist($project);
        $em->flush();

        return $project;
    }

    public function testFindBetweenDatesFilteredReturnsProjectsInDateRange(): void
    {
        $this->createProject('In Range', 'active', 'forfait', new DateTime('2026-02-01'), new DateTime('2026-04-01'));
        $this->createProject('Out of Range', 'active', 'forfait', new DateTime('2025-01-01'), new DateTime('2025-03-01'));

        $results = $this->repository->findBetweenDatesFiltered(
            new DateTime('2026-01-01'),
            new DateTime('2026-12-31'),
        );

        $names = array_map(fn (Project $p) => $p->getName(), $results);
        $this->assertContains('In Range', $names);
        $this->assertNotContains('Out of Range', $names);
    }

    public function testFilterByStatus(): void
    {
        $this->createProject('Active One', 'active');
        $this->createProject('Completed One', 'completed');

        $results = $this->repository->findBetweenDatesFiltered(
            new DateTime('2026-01-01'),
            new DateTime('2026-12-31'),
            'active',
        );

        $names = array_map(fn (Project $p) => $p->getName(), $results);
        $this->assertContains('Active One', $names);
        $this->assertNotContains('Completed One', $names);
    }

    public function testFilterByProjectType(): void
    {
        $this->createProject('Forfait', 'active', 'forfait');
        $this->createProject('Regie', 'active', 'regie');

        $results = $this->repository->findBetweenDatesFiltered(
            new DateTime('2026-01-01'),
            new DateTime('2026-12-31'),
            null,
            'forfait',
        );

        $names = array_map(fn (Project $p) => $p->getName(), $results);
        $this->assertContains('Forfait', $names);
        $this->assertNotContains('Regie', $names);
    }

    public function testFilterBySearch(): void
    {
        $this->createProject('Alpha Project');
        $this->createProject('Beta Project');

        $results = $this->repository->findBetweenDatesFiltered(
            new DateTime('2026-01-01'),
            new DateTime('2026-12-31'),
            null,
            null,
            null,
            'name',
            'ASC',
            null,
            null,
            null,
            null,
            null,
            null,
            'Alpha',
        );

        $names = array_map(fn (Project $p) => $p->getName(), $results);
        $this->assertContains('Alpha Project', $names);
        $this->assertNotContains('Beta Project', $names);
    }

    public function testCountBetweenDatesFiltered(): void
    {
        $this->createProject('Project A', 'active');
        $this->createProject('Project B', 'active');
        $this->createProject('Project C', 'completed');

        $count = $this->repository->countBetweenDatesFiltered(
            new DateTime('2026-01-01'),
            new DateTime('2026-12-31'),
            'active',
        );

        $this->assertSame(2, $count);
    }

    public function testSortByNameAsc(): void
    {
        $this->createProject('Zeta');
        $this->createProject('Alpha');

        $results = $this->repository->findBetweenDatesFiltered(
            new DateTime('2026-01-01'),
            new DateTime('2026-12-31'),
            null,
            null,
            null,
            'name',
            'ASC',
        );

        $names = array_map(fn (Project $p) => $p->getName(), $results);
        $this->assertSame('Alpha', $names[0]);
    }

    public function testPaginationWithLimitOffset(): void
    {
        for ($i = 0; $i < 5; ++$i) {
            $this->createProject('Project '.$i);
        }

        $results = $this->repository->findBetweenDatesFiltered(
            new DateTime('2026-01-01'),
            new DateTime('2026-12-31'),
            null,
            null,
            null,
            'name',
            'ASC',
            2,
            0,
        );

        $this->assertCount(2, $results);
    }

    public function testCombinedFilters(): void
    {
        $this->createProject('Active Forfait', 'active', 'forfait');
        $this->createProject('Active Regie', 'active', 'regie');
        $this->createProject('Completed Forfait', 'completed', 'forfait');

        $results = $this->repository->findBetweenDatesFiltered(
            new DateTime('2026-01-01'),
            new DateTime('2026-12-31'),
            'active',
            'forfait',
        );

        $this->assertCount(1, $results);
        $this->assertSame('Active Forfait', $results[0]->getName());
    }

    public function testMultiTenantIsolation(): void
    {
        $this->createProject('My Company Project');

        $otherCompany = $this->createTestCompany('Other Company');
        $otherProject = new Project();
        $otherProject->setName('Other Company Project');
        $otherProject->setCompany($otherCompany);
        $otherProject->setStatus('active');
        $otherProject->setProjectType('forfait');
        $otherProject->setStartDate(new DateTime('2026-03-01'));
        $otherProject->setEndDate(new DateTime('2026-06-30'));

        $em = $this->getEntityManager();
        $em->persist($otherProject);
        $em->flush();

        $results = $this->repository->findBetweenDatesFiltered(
            new DateTime('2026-01-01'),
            new DateTime('2026-12-31'),
        );

        $names = array_map(fn (Project $p) => $p->getName(), $results);
        $this->assertContains('My Company Project', $names);
        $this->assertNotContains('Other Company Project', $names);
    }
}
