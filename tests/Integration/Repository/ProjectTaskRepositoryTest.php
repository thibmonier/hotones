<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Entity\ProjectTask;
use App\Factory\ContributorFactory;
use App\Factory\ProfileFactory;
use App\Factory\ProjectFactory;
use App\Factory\ProjectTaskFactory;
use App\Repository\ProjectTaskRepository;
use App\Tests\Support\MultiTenantTestTrait;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class ProjectTaskRepositoryTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;
    use MultiTenantTestTrait;

    private ProjectTaskRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = static::getContainer()->get(ProjectTaskRepository::class);
        $this->setUpMultiTenant();

        // ProjectTaskFactory requires Profile and Contributor
        ProfileFactory::createOne(['name' => 'Developer']);
        ContributorFactory::createOne();
    }

    public function testFindByProjectOrderedByPosition(): void
    {
        $project1 = ProjectFactory::createOne();
        $project2 = ProjectFactory::createOne();

        ProjectTaskFactory::createOne(['project' => $project1, 'position' => 3]);
        ProjectTaskFactory::createOne(['project' => $project1, 'position' => 1]);
        ProjectTaskFactory::createOne(['project' => $project1, 'position' => 2]);
        ProjectTaskFactory::createOne(['project' => $project2, 'position' => 1]);

        $results = $this->repository->findByProjectOrderedByPosition($project1);

        $this->assertCount(3, $results);
        $this->assertEquals(1, $results[0]->getPosition());
        $this->assertEquals(2, $results[1]->getPosition());
        $this->assertEquals(3, $results[2]->getPosition());
    }

    public function testFindMaxPositionForProject(): void
    {
        $project = ProjectFactory::createOne();

        ProjectTaskFactory::createOne(['project' => $project, 'position' => 1]);
        ProjectTaskFactory::createOne(['project' => $project, 'position' => 5]);
        ProjectTaskFactory::createOne(['project' => $project, 'position' => 3]);

        $maxPosition = $this->repository->findMaxPositionForProject($project);

        $this->assertEquals(5, $maxPosition);
    }

    public function testFindMaxPositionForProjectWithNoTasks(): void
    {
        $project = ProjectFactory::createOne();

        $maxPosition = $this->repository->findMaxPositionForProject($project);

        $this->assertEquals(0, $maxPosition);
    }

    public function testGetMaxPosition(): void
    {
        $project = ProjectFactory::createOne();

        ProjectTaskFactory::createOne(['project' => $project, 'position' => 2]);

        $maxPosition = $this->repository->getMaxPosition($project);

        $this->assertEquals(2, $maxPosition);
    }

    public function testFindProfitableTasksByProject(): void
    {
        $project = ProjectFactory::createOne();

        // Profitable tasks
        ProjectTaskFactory::createOne([
            'project'                => $project,
            'countsForProfitability' => true,
            'type'                   => ProjectTask::TYPE_REGULAR,
            'position'               => 2,
        ]);
        ProjectTaskFactory::createOne([
            'project'                => $project,
            'countsForProfitability' => true,
            'type'                   => ProjectTask::TYPE_REGULAR,
            'position'               => 1,
        ]);

        // Not profitable (countsForProfitability = false)
        ProjectTaskFactory::createOne([
            'project'                => $project,
            'countsForProfitability' => false,
            'type'                   => ProjectTask::TYPE_REGULAR,
        ]);

        // Not profitable (type = avv)
        ProjectTaskFactory::createOne([
            'project'                => $project,
            'countsForProfitability' => true,
            'type'                   => ProjectTask::TYPE_AVV,
        ]);

        $results = $this->repository->findProfitableTasksByProject($project);

        $this->assertCount(2, $results);
        // Should be ordered by position ASC
        $this->assertEquals(1, $results[0]->getPosition());
        $this->assertEquals(2, $results[1]->getPosition());
    }

    public function testCountProfitableTasksByStatus(): void
    {
        $project = ProjectFactory::createOne();

        // Profitable completed tasks
        ProjectTaskFactory::createOne([
            'project'                => $project,
            'status'                 => 'completed',
            'countsForProfitability' => true,
            'type'                   => ProjectTask::TYPE_REGULAR,
        ]);
        ProjectTaskFactory::createOne([
            'project'                => $project,
            'status'                 => 'completed',
            'countsForProfitability' => true,
            'type'                   => ProjectTask::TYPE_REGULAR,
        ]);

        // Profitable in_progress task
        ProjectTaskFactory::createOne([
            'project'                => $project,
            'status'                 => 'in_progress',
            'countsForProfitability' => true,
            'type'                   => ProjectTask::TYPE_REGULAR,
        ]);

        // Not profitable completed task
        ProjectTaskFactory::createOne([
            'project'                => $project,
            'status'                 => 'completed',
            'countsForProfitability' => false,
            'type'                   => ProjectTask::TYPE_REGULAR,
        ]);

        $completedCount  = $this->repository->countProfitableTasksByStatus($project, 'completed');
        $inProgressCount = $this->repository->countProfitableTasksByStatus($project, 'in_progress');

        $this->assertEquals(2, $completedCount);
        $this->assertEquals(1, $inProgressCount);
    }

    public function testCountProfitableTasks(): void
    {
        $project = ProjectFactory::createOne();

        // Profitable tasks
        ProjectTaskFactory::createOne([
            'project'                => $project,
            'countsForProfitability' => true,
            'type'                   => ProjectTask::TYPE_REGULAR,
        ]);
        ProjectTaskFactory::createOne([
            'project'                => $project,
            'countsForProfitability' => true,
            'type'                   => ProjectTask::TYPE_REGULAR,
        ]);
        ProjectTaskFactory::createOne([
            'project'                => $project,
            'countsForProfitability' => true,
            'type'                   => ProjectTask::TYPE_REGULAR,
        ]);

        // Not profitable tasks
        ProjectTaskFactory::createOne([
            'project'                => $project,
            'countsForProfitability' => false,
            'type'                   => ProjectTask::TYPE_REGULAR,
        ]);
        ProjectTaskFactory::createOne([
            'project'                => $project,
            'countsForProfitability' => true,
            'type'                   => ProjectTask::TYPE_NON_VENDU,
        ]);

        $count = $this->repository->countProfitableTasks($project);

        $this->assertEquals(3, $count);
    }

    public function testFindOverdueTasksByContributor(): void
    {
        $contributor = ContributorFactory::createOne();
        $project     = ProjectFactory::createOne();

        // Overdue task (endDate in past, not completed)
        ProjectTaskFactory::createOne([
            'project'             => $project,
            'assignedContributor' => $contributor,
            'endDate'             => new DateTime('-5 days'),
            'status'              => 'in_progress',
        ]);

        // Another overdue task (more recent)
        ProjectTaskFactory::createOne([
            'project'             => $project,
            'assignedContributor' => $contributor,
            'endDate'             => new DateTime('-2 days'),
            'status'              => 'todo',
        ]);

        // Not overdue (endDate in future)
        ProjectTaskFactory::createOne([
            'project'             => $project,
            'assignedContributor' => $contributor,
            'endDate'             => new DateTime('+5 days'),
            'status'              => 'in_progress',
        ]);

        // Overdue but completed (excluded)
        ProjectTaskFactory::createOne([
            'project'             => $project,
            'assignedContributor' => $contributor,
            'endDate'             => new DateTime('-3 days'),
            'status'              => 'completed',
        ]);

        $results = $this->repository->findOverdueTasksByContributor($contributor);

        $this->assertCount(2, $results);
        // Should be ordered by endDate ASC (oldest first)
        $this->assertEquals('in_progress', $results[0]->getStatus());
        $this->assertEquals('todo', $results[1]->getStatus());
    }

    public function testFindOverdueTasksByContributorReturnsEmpty(): void
    {
        $contributor = ContributorFactory::createOne();

        $results = $this->repository->findOverdueTasksByContributor($contributor);

        $this->assertEmpty($results);
    }
}
