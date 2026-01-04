<?php

namespace App\Tests\Integration\Repository;

use App\Factory\ContributorFactory;
use App\Factory\ProjectFactory;
use App\Factory\TimesheetFactory;
use App\Repository\TimesheetRepository;
use App\Tests\Support\MultiTenantTestTrait;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class TimesheetRepositoryTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;
    use MultiTenantTestTrait;

    private TimesheetRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = static::getContainer()->get(TimesheetRepository::class);
        $this->setUpMultiTenant();
    }

    public function testGetTotalHoursForMonth(): void
    {
        $contributor = ContributorFactory::createOne();
        $project     = ProjectFactory::createOne(['status' => 'active']);

        // Create 3 entries in April 2024 and 1 in May 2024
        TimesheetFactory::createOne([
            'contributor' => $contributor,
            'project'     => $project,
            'date'        => new DateTime('2024-04-02'),
            'hours'       => '8.00',
        ]);
        TimesheetFactory::createOne([
            'contributor' => $contributor,
            'project'     => $project,
            'date'        => new DateTime('2024-04-10'),
            'hours'       => '7.50',
        ]);
        TimesheetFactory::createOne([
            'contributor' => $contributor,
            'project'     => $project,
            'date'        => new DateTime('2024-04-20'),
            'hours'       => '4.00',
        ]);
        TimesheetFactory::createOne([
            'contributor' => $contributor,
            'project'     => $project,
            'date'        => new DateTime('2024-05-01'),
            'hours'       => '8.00',
        ]);

        $start = new DateTime('2024-04-01');
        $end   = new DateTime('2024-04-30');
        $sum   = $this->repository->getTotalHoursForMonth($start, $end);

        $this->assertEquals(19.5, $sum, '', 0.001);
    }

    public function testFindByContributorAndDateRange(): void
    {
        $contributor1 = ContributorFactory::createOne();
        $contributor2 = ContributorFactory::createOne();
        $project      = ProjectFactory::createOne();

        // Timesheets for contributor1 in range
        TimesheetFactory::createOne([
            'contributor' => $contributor1,
            'project'     => $project,
            'date'        => new DateTime('2025-01-15'),
            'hours'       => '8.00',
        ]);
        TimesheetFactory::createOne([
            'contributor' => $contributor1,
            'project'     => $project,
            'date'        => new DateTime('2025-01-20'),
            'hours'       => '7.00',
        ]);

        // Timesheet for contributor1 outside range
        TimesheetFactory::createOne([
            'contributor' => $contributor1,
            'project'     => $project,
            'date'        => new DateTime('2025-02-01'),
            'hours'       => '5.00',
        ]);

        // Timesheet for contributor2 in range (should be excluded)
        TimesheetFactory::createOne([
            'contributor' => $contributor2,
            'project'     => $project,
            'date'        => new DateTime('2025-01-18'),
            'hours'       => '6.00',
        ]);

        $start      = new DateTime('2025-01-01');
        $end        = new DateTime('2025-01-31');
        $timesheets = $this->repository->findByContributorAndDateRange($contributor1, $start, $end);

        $this->assertCount(2, $timesheets);
    }

    public function testFindRecentByContributor(): void
    {
        $contributor = ContributorFactory::createOne();
        $project     = ProjectFactory::createOne();

        TimesheetFactory::createMany(10, [
            'contributor' => $contributor,
            'project'     => $project,
        ]);

        $recent = $this->repository->findRecentByContributor($contributor, 3);

        $this->assertCount(3, $recent);
    }

    public function testFindForPeriodWithProject(): void
    {
        $project1    = ProjectFactory::createOne();
        $project2    = ProjectFactory::createOne();
        $contributor = ContributorFactory::createOne();

        // Timesheets for project1 in period
        TimesheetFactory::createOne([
            'contributor' => $contributor,
            'project'     => $project1,
            'date'        => new DateTime('2025-01-10'),
            'hours'       => '8.00',
        ]);
        TimesheetFactory::createOne([
            'contributor' => $contributor,
            'project'     => $project1,
            'date'        => new DateTime('2025-01-15'),
            'hours'       => '6.00',
        ]);

        // Timesheet for project2 in period (should be excluded)
        TimesheetFactory::createOne([
            'contributor' => $contributor,
            'project'     => $project2,
            'date'        => new DateTime('2025-01-12'),
            'hours'       => '7.00',
        ]);

        $start      = new DateTime('2025-01-01');
        $end        = new DateTime('2025-01-31');
        $timesheets = $this->repository->findForPeriodWithProject($start, $end, $project1);

        $this->assertCount(2, $timesheets);
    }

    public function testFindForPeriodWithProjectReturnsAllWhenNoProjectSpecified(): void
    {
        $contributor = ContributorFactory::createOne();
        $project1    = ProjectFactory::createOne();
        $project2    = ProjectFactory::createOne();

        TimesheetFactory::createOne([
            'contributor' => $contributor,
            'project'     => $project1,
            'date'        => new DateTime('2025-01-10'),
            'hours'       => '8.00',
        ]);
        TimesheetFactory::createOne([
            'contributor' => $contributor,
            'project'     => $project2,
            'date'        => new DateTime('2025-01-15'),
            'hours'       => '7.00',
        ]);

        $start      = new DateTime('2025-01-01');
        $end        = new DateTime('2025-01-31');
        $timesheets = $this->repository->findForPeriodWithProject($start, $end);

        $this->assertCount(2, $timesheets);
    }

    public function testFindForPeriodWithProjects(): void
    {
        $contributor = ContributorFactory::createOne();
        $project1    = ProjectFactory::createOne();
        $project2    = ProjectFactory::createOne();
        $project3    = ProjectFactory::createOne();

        TimesheetFactory::createOne([
            'contributor' => $contributor,
            'project'     => $project1,
            'date'        => new DateTime('2025-01-10'),
        ]);
        TimesheetFactory::createOne([
            'contributor' => $contributor,
            'project'     => $project2,
            'date'        => new DateTime('2025-01-15'),
        ]);
        TimesheetFactory::createOne([
            'contributor' => $contributor,
            'project'     => $project3,
            'date'        => new DateTime('2025-01-20'),
        ]);

        $start      = new DateTime('2025-01-01');
        $end        = new DateTime('2025-01-31');
        $projectIds = [$project1->getId(), $project2->getId()];
        $timesheets = $this->repository->findForPeriodWithProjects($start, $end, $projectIds);

        $this->assertCount(2, $timesheets);
    }

    public function testGetHoursGroupedByProjectForContributor(): void
    {
        $contributor = ContributorFactory::createOne();
        $project1    = ProjectFactory::createOne(['name' => 'Project A']);
        $project2    = ProjectFactory::createOne(['name' => 'Project B']);

        TimesheetFactory::createOne([
            'contributor' => $contributor,
            'project'     => $project1,
            'date'        => new DateTime('2025-01-10'),
            'hours'       => '8.00',
        ]);
        TimesheetFactory::createOne([
            'contributor' => $contributor,
            'project'     => $project1,
            'date'        => new DateTime('2025-01-15'),
            'hours'       => '6.00',
        ]);
        TimesheetFactory::createOne([
            'contributor' => $contributor,
            'project'     => $project2,
            'date'        => new DateTime('2025-01-20'),
            'hours'       => '5.00',
        ]);

        $start   = new DateTime('2025-01-01');
        $end     = new DateTime('2025-01-31');
        $grouped = $this->repository->getHoursGroupedByProjectForContributor($contributor, $start, $end);

        $this->assertCount(2, $grouped);
        // Verify structure: ['project' => [...], 'totalHours' => ...]
        $this->assertArrayHasKey('project', $grouped[0]);
        $this->assertArrayHasKey('totalHours', $grouped[0]);
        $this->assertArrayHasKey('id', $grouped[0]['project']);
        $this->assertArrayHasKey('name', $grouped[0]['project']);
    }

    public function testFindExistingTimesheet(): void
    {
        $contributor = ContributorFactory::createOne();
        $project     = ProjectFactory::createOne();
        $date        = new DateTime('2025-01-15');

        TimesheetFactory::createOne([
            'contributor' => $contributor,
            'project'     => $project,
            'date'        => $date,
            'hours'       => '8.00',
        ]);

        $result = $this->repository->findExistingTimesheet($contributor, $project, $date);

        $this->assertNotNull($result);
        $this->assertEquals($contributor->getId(), $result->getContributor()->getId());
        $this->assertEquals($project->getId(), $result->getProject()->getId());
    }

    public function testFindExistingTimesheetReturnsNullWhenNotFound(): void
    {
        $contributor = ContributorFactory::createOne();
        $project     = ProjectFactory::createOne();
        $date        = new DateTime('2025-01-15');

        $result = $this->repository->findExistingTimesheet($contributor, $project, $date);

        $this->assertNull($result);
    }

    public function testGetStatsPerContributor(): void
    {
        $contributor1 = ContributorFactory::createOne(['cjm' => '400.00']);
        $contributor2 = ContributorFactory::createOne(['cjm' => '500.00']);
        $project      = ProjectFactory::createOne();

        TimesheetFactory::createOne([
            'contributor' => $contributor1,
            'project'     => $project,
            'date'        => new DateTime('2025-01-10'),
            'hours'       => '16.00', // 2 days
        ]);
        TimesheetFactory::createOne([
            'contributor' => $contributor2,
            'project'     => $project,
            'date'        => new DateTime('2025-01-15'),
            'hours'       => '8.00', // 1 day
        ]);

        $start = new DateTime('2025-01-01');
        $end   = new DateTime('2025-01-31');
        $stats = $this->repository->getStatsPerContributor($start, $end);

        $this->assertCount(2, $stats);
        // Verify structure: contributorName, totalHours, totalEntries
        $this->assertArrayHasKey('contributorName', $stats[0]);
        $this->assertArrayHasKey('totalHours', $stats[0]);
        $this->assertArrayHasKey('totalEntries', $stats[0]);
    }

    public function testGetStatsPerContributorForProjects(): void
    {
        $contributor = ContributorFactory::createOne();
        $project1    = ProjectFactory::createOne();
        $project2    = ProjectFactory::createOne();
        $project3    = ProjectFactory::createOne();

        TimesheetFactory::createOne([
            'contributor' => $contributor,
            'project'     => $project1,
            'date'        => new DateTime('2025-01-10'),
            'hours'       => '8.00',
        ]);
        TimesheetFactory::createOne([
            'contributor' => $contributor,
            'project'     => $project2,
            'date'        => new DateTime('2025-01-15'),
            'hours'       => '6.00',
        ]);
        TimesheetFactory::createOne([
            'contributor' => $contributor,
            'project'     => $project3,
            'date'        => new DateTime('2025-01-20'),
            'hours'       => '7.00',
        ]);

        $start      = new DateTime('2025-01-01');
        $end        = new DateTime('2025-01-31');
        $projectIds = [$project1->getId(), $project2->getId()];
        $stats      = $this->repository->getStatsPerContributorForProjects($start, $end, $projectIds);

        $this->assertCount(1, $stats);
        // Should only include hours from project1 and project2 (8.00 + 6.00 = 14.00)
        $totalHours = (float) $stats[0]['totalHours'];
        $this->assertEquals(14.0, $totalHours, '', 0.001);
    }

    public function testGetTotalHoursForPeriodAndProjects(): void
    {
        $contributor = ContributorFactory::createOne();
        $project1    = ProjectFactory::createOne();
        $project2    = ProjectFactory::createOne();

        TimesheetFactory::createOne([
            'contributor' => $contributor,
            'project'     => $project1,
            'date'        => new DateTime('2025-01-10'),
            'hours'       => '8.00',
        ]);
        TimesheetFactory::createOne([
            'contributor' => $contributor,
            'project'     => $project2,
            'date'        => new DateTime('2025-01-15'),
            'hours'       => '6.00',
        ]);

        $start      = new DateTime('2025-01-01');
        $end        = new DateTime('2025-01-31');
        $projectIds = [$project1->getId(), $project2->getId()];
        $totalHours = $this->repository->getTotalHoursForPeriodAndProjects($start, $end, $projectIds);

        $this->assertEquals(14.0, $totalHours, '', 0.001);
    }

    public function testGetMonthlyHoursForProject(): void
    {
        $this->markTestSkipped('Uses MySQL YEAR/MONTH functions not compatible with SQLite test environment');
    }

    public function testGetPeriodAggregatesForProjects(): void
    {
        $contributor = ContributorFactory::createOne(['cjm' => '400.00']);
        $project1    = ProjectFactory::createOne();
        $project2    = ProjectFactory::createOne();

        TimesheetFactory::createOne([
            'contributor' => $contributor,
            'project'     => $project1,
            'date'        => new DateTime('2025-01-10'),
            'hours'       => '16.00', // 2 days
        ]);
        TimesheetFactory::createOne([
            'contributor' => $contributor,
            'project'     => $project2,
            'date'        => new DateTime('2025-01-15'),
            'hours'       => '8.00', // 1 day
        ]);

        $start      = new DateTime('2025-01-01');
        $end        = new DateTime('2025-01-31');
        $projectIds = [$project1->getId(), $project2->getId()];
        $aggregates = $this->repository->getPeriodAggregatesForProjects($start, $end, $projectIds);

        // Returns a single associative array, not array of arrays
        $this->assertIsArray($aggregates);
        $this->assertArrayHasKey('totalHours', $aggregates);
        $this->assertArrayHasKey('totalHumanCost', $aggregates);
        $this->assertArrayHasKey('totalRevenue', $aggregates);
        // Verify total hours is 24.00 (16.00 + 8.00)
        $this->assertEquals(24.0, $aggregates['totalHours'], '', 0.001);
    }
}
