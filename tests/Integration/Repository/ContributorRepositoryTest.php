<?php

namespace App\Tests\Integration\Repository;

use App\Factory\ContributorFactory;
use App\Factory\ProfileFactory;
use App\Factory\ProjectFactory;
use App\Factory\ProjectTaskFactory;
use App\Factory\TimesheetFactory;
use App\Factory\UserFactory;
use App\Repository\ContributorRepository;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class ContributorRepositoryTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    private ContributorRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = static::getContainer()->get(ContributorRepository::class);
    }

    public function testFindActiveContributors(): void
    {
        // Create active and inactive contributors
        ContributorFactory::createOne(['lastName' => 'Zulu', 'active' => true]);
        ContributorFactory::createOne(['lastName' => 'Alpha', 'active' => true]);
        ContributorFactory::createOne(['lastName' => 'Beta', 'active' => false]);

        $contributors = $this->repository->findActiveContributors();

        $this->assertCount(2, $contributors);
        // Should be ordered by lastName ASC
        $this->assertEquals('Alpha', $contributors[0]->getLastName());
        $this->assertEquals('Zulu', $contributors[1]->getLastName());
    }

    public function testFindActiveContributorsByProfile(): void
    {
        $profile1 = ProfileFactory::createOne(['name' => 'Developer']);
        $profile2 = ProfileFactory::createOne(['name' => 'Designer']);

        $contributor1 = ContributorFactory::createOne(['active' => true]);
        $contributor1->addProfile($profile1);

        $contributor2 = ContributorFactory::createOne(['active' => true]);
        $contributor2->addProfile($profile1);
        $contributor2->addProfile($profile2);

        $contributor3 = ContributorFactory::createOne(['active' => true]);
        $contributor3->addProfile($profile2);

        $contributor4 = ContributorFactory::createOne(['active' => false]);
        $contributor4->addProfile($profile1);

        $contributors = $this->repository->findActiveContributorsByProfile($profile1);

        // Should return only active contributors with profile1
        $this->assertCount(2, $contributors);
    }

    public function testCountActiveContributors(): void
    {
        ContributorFactory::createMany(5, ['active' => true]);
        ContributorFactory::createMany(3, ['active' => false]);

        $count = $this->repository->countActiveContributors();

        $this->assertEquals(5, $count);
    }

    public function testFindByUser(): void
    {
        $user1 = UserFactory::createOne();
        $user2 = UserFactory::createOne();

        $contributor1 = ContributorFactory::createOne(['user' => $user1]);
        ContributorFactory::createOne(['user' => $user2]);
        ContributorFactory::createOne(['user' => null]);

        $result = $this->repository->findByUser($user1);

        $this->assertNotNull($result);
        $this->assertEquals($contributor1->getId(), $result->getId());
    }

    public function testFindByUserReturnsNullWhenNotFound(): void
    {
        $user = UserFactory::createOne();

        $result = $this->repository->findByUser($user);

        $this->assertNull($result);
    }

    public function testFindWithProfiles(): void
    {
        $profile1 = ProfileFactory::createOne();
        $profile2 = ProfileFactory::createOne();

        $contributor1 = ContributorFactory::createOne(['active' => true]);
        $contributor1->addProfile($profile1);
        $contributor1->addProfile($profile2);

        ContributorFactory::createOne(['active' => true]); // No profiles
        ContributorFactory::createOne(['active' => false]); // Inactive

        $contributors = $this->repository->findWithProfiles();

        $this->assertCount(2, $contributors);
        // Profiles should be eagerly loaded (no N+1 query)
        $this->assertNotNull($contributors[0]->getProfiles());
    }

    public function testSearchByName(): void
    {
        ContributorFactory::createOne(['firstName' => 'John', 'lastName' => 'Doe', 'active' => true]);
        ContributorFactory::createOne(['firstName' => 'Jane', 'lastName' => 'Smith', 'active' => true]);
        ContributorFactory::createOne(['firstName' => 'Bob', 'lastName' => 'Johnson', 'active' => true]);
        ContributorFactory::createOne(['firstName' => 'Alice', 'lastName' => 'Wonder', 'active' => false]);

        $results = $this->repository->searchByName('John');

        // Should find "John Doe" and "Bob Johnson" (both active)
        $this->assertCount(2, $results);
    }

    public function testSearchByNameIsCaseInsensitive(): void
    {
        ContributorFactory::createOne(['firstName' => 'JOHN', 'lastName' => 'DOE', 'active' => true]);

        $results = $this->repository->searchByName('john');

        $this->assertCount(1, $results);
    }

    public function testFindWithHoursForPeriod(): void
    {
        $contributor1 = ContributorFactory::createOne(['active' => true]);
        $contributor2 = ContributorFactory::createOne(['active' => true]);
        $contributor3 = ContributorFactory::createOne(['active' => false]);
        $project      = ProjectFactory::createOne();

        // Contributor1: 16 hours in period
        TimesheetFactory::createOne([
            'contributor' => $contributor1,
            'project'     => $project,
            'date'        => new DateTime('2025-01-10'),
            'hours'       => '8.00',
        ]);
        TimesheetFactory::createOne([
            'contributor' => $contributor1,
            'project'     => $project,
            'date'        => new DateTime('2025-01-15'),
            'hours'       => '8.00',
        ]);

        // Contributor2: 4 hours in period
        TimesheetFactory::createOne([
            'contributor' => $contributor2,
            'project'     => $project,
            'date'        => new DateTime('2025-01-12'),
            'hours'       => '4.00',
        ]);

        // Contributor3: inactive (should be excluded)
        TimesheetFactory::createOne([
            'contributor' => $contributor3,
            'project'     => $project,
            'date'        => new DateTime('2025-01-12'),
            'hours'       => '10.00',
        ]);

        $start   = new DateTime('2025-01-01');
        $end     = new DateTime('2025-01-31');
        $results = $this->repository->findWithHoursForPeriod($start, $end);

        // Should return only active contributors
        // Note: Results are mixed array with [0] = Contributor entity, ['totalHours'] = aggregate
        $this->assertCount(2, $results);
        // Verify structure: each result contains Contributor entity at index 0
        $this->assertIsObject($results[0][0]);
        $this->assertInstanceOf(\App\Entity\Contributor::class, $results[0][0]);
    }

    public function testFindProjectsWithAssignedTasks(): void
    {
        // Create required profiles for ProjectTaskFactory
        ProfileFactory::createOne(['name' => 'Developer']);

        $contributor = ContributorFactory::createOne();
        $project1    = ProjectFactory::createOne(['status' => 'active']);
        $project2    = ProjectFactory::createOne(['status' => 'active']);
        $project3    = ProjectFactory::createOne(['status' => 'archived']);

        // Task assigned to contributor in active project
        ProjectTaskFactory::createOne([
            'project'             => $project1,
            'assignedContributor' => $contributor,
            'active'              => true,
        ]);

        // Task assigned to contributor in another active project
        ProjectTaskFactory::createOne([
            'project'             => $project2,
            'assignedContributor' => $contributor,
            'active'              => true,
        ]);

        // Task in archived project (should be excluded)
        ProjectTaskFactory::createOne([
            'project'             => $project3,
            'assignedContributor' => $contributor,
            'active'              => true,
        ]);

        // Inactive task (should be excluded)
        ProjectTaskFactory::createOne([
            'project'             => $project1,
            'assignedContributor' => $contributor,
            'active'              => false,
        ]);

        $projects = $this->repository->findProjectsWithAssignedTasks($contributor);

        // Should return only active projects with active tasks
        $this->assertCount(2, $projects);
    }

    public function testFindProjectsWithTasksForContributor(): void
    {
        // Create required profiles for ProjectTaskFactory
        ProfileFactory::createOne(['name' => 'Developer']);

        $contributor = ContributorFactory::createOne();
        // Use specific names to control sort order (sorted by name ASC)
        $project1 = ProjectFactory::createOne(['status' => 'active', 'name' => 'A-Project']);
        $project2 = ProjectFactory::createOne(['status' => 'active', 'name' => 'B-Project']);

        // 2 tasks for contributor in project1
        ProjectTaskFactory::createOne([
            'project'             => $project1,
            'assignedContributor' => $contributor,
            'active'              => true,
            'position'            => 1,
        ]);
        ProjectTaskFactory::createOne([
            'project'             => $project1,
            'assignedContributor' => $contributor,
            'active'              => true,
            'position'            => 2,
        ]);

        // 1 task for contributor in project2
        ProjectTaskFactory::createOne([
            'project'             => $project2,
            'assignedContributor' => $contributor,
            'active'              => true,
            'position'            => 1,
        ]);

        $results = $this->repository->findProjectsWithTasksForContributor($contributor);

        $this->assertCount(2, $results);
        // Verify structure
        $this->assertArrayHasKey('project', $results[0]);
        $this->assertArrayHasKey('tasks', $results[0]);
        // Results are sorted by project name ASC, so A-Project (2 tasks) should be first
        $this->assertCount(2, $results[0]['tasks']);
        $this->assertCount(1, $results[1]['tasks']);
    }

    public function testSearch(): void
    {
        $user1 = UserFactory::createOne(['email' => 'john@example.com']);
        ContributorFactory::createOne([
            'firstName' => 'John',
            'lastName'  => 'Doe',
            'user'      => $user1,
        ]);
        ContributorFactory::createOne([
            'firstName' => 'Jane',
            'lastName'  => 'Smith',
        ]);
        ContributorFactory::createOne([
            'firstName' => 'Bob',
            'lastName'  => 'Johnson',
        ]);

        // Search by first name
        $results = $this->repository->search('John', 10);
        $this->assertCount(2, $results); // John Doe + Bob Johnson

        // Search by email
        $results = $this->repository->search('john@example', 10);
        $this->assertCount(1, $results); // John Doe via email
    }

    public function testSearchRespectsLimit(): void
    {
        ContributorFactory::createMany(10, ['firstName' => 'Test']);

        $results = $this->repository->search('Test', 3);

        $this->assertCount(3, $results);
    }
}
