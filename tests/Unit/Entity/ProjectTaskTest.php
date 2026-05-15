<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Contributor;
use App\Entity\OrderLine;
use App\Entity\Profile;
use App\Entity\Project;
use App\Entity\ProjectTask;
use DateTime;
use PHPUnit\Framework\TestCase;

class ProjectTaskTest extends TestCase
{
    public function testSettersAndGetters(): void
    {
        $task = new ProjectTask();

        $task->setName('Task 1');
        static::assertSame('Task 1', $task->getName());

        $task->setDescription('Description');
        static::assertSame('Description', $task->getDescription());

        $task->setType('regular');
        static::assertSame('regular', $task->getType());

        $task->setIsDefault(true);
        static::assertTrue($task->getIsDefault());

        $task->setCountsForProfitability(true);
        static::assertTrue($task->getCountsForProfitability());

        $task->setPosition(1);
        static::assertSame(1, $task->getPosition());

        $task->setActive(true);
        static::assertTrue($task->getActive());

        $task->setEstimatedHoursSold(100);
        static::assertSame(100, $task->getEstimatedHoursSold());

        $task->setEstimatedHoursRevised(90);
        static::assertSame(90, $task->getEstimatedHoursRevised());

        $task->setProgressPercentage(50);
        static::assertSame(50, $task->getProgressPercentage());

        $task->setDailyRate('500.00');
        static::assertSame('500.00', $task->getDailyRate());

        $task->setStatus('in_progress');
        static::assertSame('in_progress', $task->getStatus());
    }

    public function testProjectRelation(): void
    {
        $task = new ProjectTask();
        $project = new Project();
        $project->setName('Test Project');

        $task->setProject($project);

        static::assertSame($project, $task->getProject());
        static::assertSame('Test Project', $task->getProject()->getName());
    }

    public function testOrderLineRelation(): void
    {
        $task = new ProjectTask();
        $orderLine = new OrderLine();

        $task->setOrderLine($orderLine);

        static::assertSame($orderLine, $task->getOrderLine());
    }

    public function testAssignedContributorRelation(): void
    {
        $task = new ProjectTask();
        $contributor = new Contributor();
        $contributor->setFirstName('John');
        $contributor->setLastName('Doe');

        $task->setAssignedContributor($contributor);

        static::assertSame($contributor, $task->getAssignedContributor());
    }

    public function testRequiredProfileRelation(): void
    {
        $task = new ProjectTask();
        $profile = new Profile();
        $profile->setName('Developer');

        $task->setRequiredProfile($profile);

        static::assertSame($profile, $task->getRequiredProfile());
    }

    public function testDatesHandling(): void
    {
        $task = new ProjectTask();

        $startDate = new DateTime('2025-01-01');
        $endDate = new DateTime('2025-12-31');

        $task->setStartDate($startDate);
        $task->setEndDate($endDate);

        static::assertEquals($startDate, $task->getStartDate());
        static::assertEquals($endDate, $task->getEndDate());
    }

    public function testDefaultValues(): void
    {
        $task = new ProjectTask();

        // Note: Default values are set in the entity constructor
        static::assertSame(0, $task->getProgressPercentage());
    }

    public function testEstimatedHoursLogic(): void
    {
        $task = new ProjectTask();

        // When both sold and revised are set
        $task->setEstimatedHoursSold(100);
        $task->setEstimatedHoursRevised(80);

        static::assertSame(80, $task->getEstimatedHoursRevised());
        static::assertSame(100, $task->getEstimatedHoursSold());
    }

    public function testProgressPercentageConstraints(): void
    {
        $task = new ProjectTask();

        $task->setProgressPercentage(0);
        static::assertSame(0, $task->getProgressPercentage());

        $task->setProgressPercentage(50);
        static::assertSame(50, $task->getProgressPercentage());

        $task->setProgressPercentage(100);
        static::assertSame(100, $task->getProgressPercentage());
    }

    public function testTaskTypeValues(): void
    {
        $task = new ProjectTask();

        $validTypes = ['regular', 'avv', 'non_vendu'];

        foreach ($validTypes as $type) {
            $task->setType($type);
            static::assertEquals($type, $task->getType());
        }
    }

    public function testStatusValues(): void
    {
        $task = new ProjectTask();

        $validStatuses = ['not_started', 'in_progress', 'completed', 'cancelled'];

        foreach ($validStatuses as $status) {
            $task->setStatus($status);
            static::assertEquals($status, $task->getStatus());
        }
    }
}
