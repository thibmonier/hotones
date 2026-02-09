<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Client;
use App\Entity\Project;
use App\Entity\User;
use DateTime;
use PHPUnit\Framework\TestCase;

class ProjectTest extends TestCase
{
    public function testProjectCreationAndDefaultValues(): void
    {
        $project = new Project();
        $project->setName('Test Project'); // Name is required

        // Test initial state
        $this->assertNull($project->getId());
        $this->assertEquals('Test Project', $project->getName());
        $this->assertNull($project->getClient());
        $this->assertNull($project->getDescription());
        $this->assertNull($project->getPurchasesAmount());
        $this->assertNull($project->getPurchasesDescription());
        $this->assertNull($project->getStartDate());
        $this->assertNull($project->getEndDate());
        $this->assertEquals('active', $project->getStatus());
        $this->assertFalse($project->isInternal());
        $this->assertEquals('forfait', $project->getProjectType());
        $this->assertNull($project->getKeyAccountManager());
        $this->assertNull($project->getProjectManager());
    }

    public function testProjectPropertiesSettersAndGetters(): void
    {
        $project = new Project();
        $client  = new Client();
        $kam     = new User();
        $pm      = new User();

        $project->setName('Test Project');
        $project->setClient($client);
        $project->setDescription('A test project description');
        $project->setPurchasesAmount('1000.00');
        $project->setPurchasesDescription('External services');
        $project->setStartDate(new DateTime('2023-01-01'));
        $project->setEndDate(new DateTime('2023-12-31'));
        $project->setStatus('completed');
        $project->setIsInternal(true);
        $project->setProjectType('regie');
        $project->setKeyAccountManager($kam);
        $project->setProjectManager($pm);

        $this->assertEquals('Test Project', $project->getName());
        $this->assertSame($client, $project->getClient());
        $this->assertEquals('A test project description', $project->getDescription());
        $this->assertEquals('1000.00', $project->getPurchasesAmount());
        $this->assertEquals('External services', $project->getPurchasesDescription());
        $this->assertEquals(new DateTime('2023-01-01'), $project->getStartDate());
        $this->assertEquals(new DateTime('2023-12-31'), $project->getEndDate());
        $this->assertEquals('completed', $project->getStatus());
        $this->assertTrue($project->isInternal());
        $this->assertEquals('regie', $project->getProjectType());
        $this->assertSame($kam, $project->getKeyAccountManager());
        $this->assertSame($pm, $project->getProjectManager());
    }

    public function testProjectStatusValidation(): void
    {
        $project = new Project();

        // Test valid statuses
        $validStatuses = ['active', 'completed', 'cancelled'];
        foreach ($validStatuses as $status) {
            $project->setStatus($status);
            $this->assertEquals($status, $project->getStatus());
        }
    }

    public function testProjectTypeValidation(): void
    {
        $project = new Project();

        // Test valid project types
        $validTypes = ['forfait', 'regie'];
        foreach ($validTypes as $type) {
            $project->setProjectType($type);
            $this->assertEquals($type, $project->getProjectType());
        }
    }

    public function testProjectDateManagement(): void
    {
        $project   = new Project();
        $startDate = new DateTime('2023-01-15');
        $endDate   = new DateTime('2023-06-15');

        $project->setStartDate($startDate);
        $project->setEndDate($endDate);

        $this->assertSame($startDate, $project->getStartDate());
        $this->assertSame($endDate, $project->getEndDate());

        // Test null dates
        $project->setStartDate(null);
        $project->setEndDate(null);
        $this->assertNull($project->getStartDate());
        $this->assertNull($project->getEndDate());
    }

    public function testProjectStringRepresentation(): void
    {
        $project = new Project();
        $project->setName('Test Project');

        // Project doesn't implement Stringable, so we test the name getter instead
        $this->assertEquals('Test Project', $project->getName());
    }

    public function testProjectClientAssociation(): void
    {
        $project = new Project();
        $client  = new Client();

        $project->setClient($client);
        $this->assertSame($client, $project->getClient());

        $project->setClient(null);
        $this->assertNull($project->getClient());
    }
}
