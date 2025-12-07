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
        $this->assertEquals('Task 1', $task->getName());

        $task->setDescription('Description');
        $this->assertEquals('Description', $task->getDescription());

        $task->setType('regular');
        $this->assertEquals('regular', $task->getType());

        $task->setIsDefault(true);
        $this->assertTrue($task->getIsDefault());

        $task->setCountsForProfitability(true);
        $this->assertTrue($task->getCountsForProfitability());

        $task->setPosition(1);
        $this->assertEquals(1, $task->getPosition());

        $task->setActive(true);
        $this->assertTrue($task->getActive());

        $task->setEstimatedHoursSold(100);
        $this->assertEquals(100, $task->getEstimatedHoursSold());

        $task->setEstimatedHoursRevised(90);
        $this->assertEquals(90, $task->getEstimatedHoursRevised());

        $task->setProgressPercentage(50);
        $this->assertEquals(50, $task->getProgressPercentage());

        $task->setDailyRate('500.00');
        $this->assertEquals('500.00', $task->getDailyRate());

        $task->setStatus('in_progress');
        $this->assertEquals('in_progress', $task->getStatus());
    }

    public function testProjectRelation(): void
    {
        $task    = new ProjectTask();
        $project = new Project();
        $project->setName('Test Project');

        $task->setProject($project);

        $this->assertSame($project, $task->getProject());
        $this->assertEquals('Test Project', $task->getProject()->getName());
    }

    public function testOrderLineRelation(): void
    {
        $task      = new ProjectTask();
        $orderLine = new OrderLine();

        $task->setOrderLine($orderLine);

        $this->assertSame($orderLine, $task->getOrderLine());
    }

    public function testAssignedContributorRelation(): void
    {
        $task        = new ProjectTask();
        $contributor = new Contributor();
        $contributor->setFirstName('John');
        $contributor->setLastName('Doe');

        $task->setAssignedContributor($contributor);

        $this->assertSame($contributor, $task->getAssignedContributor());
    }

    public function testRequiredProfileRelation(): void
    {
        $task    = new ProjectTask();
        $profile = new Profile();
        $profile->setName('Developer');

        $task->setRequiredProfile($profile);

        $this->assertSame($profile, $task->getRequiredProfile());
    }

    public function testDatesHandling(): void
    {
        $task = new ProjectTask();

        $startDate = new DateTime('2025-01-01');
        $endDate   = new DateTime('2025-12-31');

        $task->setStartDate($startDate);
        $task->setEndDate($endDate);

        $this->assertEquals($startDate, $task->getStartDate());
        $this->assertEquals($endDate, $task->getEndDate());
    }

    public function testDefaultValues(): void
    {
        $task = new ProjectTask();

        // Note: Default values are set in the entity constructor
        $this->assertEquals(0, $task->getProgressPercentage());
    }

    public function testEstimatedHoursLogic(): void
    {
        $task = new ProjectTask();

        // When both sold and revised are set
        $task->setEstimatedHoursSold(100);
        $task->setEstimatedHoursRevised(80);

        $this->assertEquals(80, $task->getEstimatedHoursRevised());
        $this->assertEquals(100, $task->getEstimatedHoursSold());
    }

    public function testProgressPercentageConstraints(): void
    {
        $task = new ProjectTask();

        $task->setProgressPercentage(0);
        $this->assertEquals(0, $task->getProgressPercentage());

        $task->setProgressPercentage(50);
        $this->assertEquals(50, $task->getProgressPercentage());

        $task->setProgressPercentage(100);
        $this->assertEquals(100, $task->getProgressPercentage());
    }

    public function testTaskTypeValues(): void
    {
        $task = new ProjectTask();

        $validTypes = ['regular', 'avv', 'non_vendu'];

        foreach ($validTypes as $type) {
            $task->setType($type);
            $this->assertEquals($type, $task->getType());
        }
    }

    public function testStatusValues(): void
    {
        $task = new ProjectTask();

        $validStatuses = ['not_started', 'in_progress', 'completed', 'cancelled'];

        foreach ($validStatuses as $status) {
            $task->setStatus($status);
            $this->assertEquals($status, $task->getStatus());
        }
    }
}
