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
        static::assertNull($project->getId());
        static::assertSame('Test Project', $project->getName());
        static::assertNull($project->getClient());
        static::assertNull($project->getDescription());
        static::assertNull($project->getPurchasesAmount());
        static::assertNull($project->getPurchasesDescription());
        static::assertNull($project->getStartDate());
        static::assertNull($project->getEndDate());
        static::assertSame('active', $project->getStatus());
        static::assertFalse($project->isInternal());
        static::assertSame('forfait', $project->getProjectType());
        static::assertNull($project->getKeyAccountManager());
        static::assertNull($project->getProjectManager());
    }

    public function testProjectPropertiesSettersAndGetters(): void
    {
        $project = new Project();
        $client = new Client();
        $kam = new User();
        $pm = new User();

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

        static::assertSame('Test Project', $project->getName());
        static::assertSame($client, $project->getClient());
        static::assertSame('A test project description', $project->getDescription());
        static::assertSame('1000.00', $project->getPurchasesAmount());
        static::assertSame('External services', $project->getPurchasesDescription());
        static::assertEquals(new DateTime('2023-01-01'), $project->getStartDate());
        static::assertEquals(new DateTime('2023-12-31'), $project->getEndDate());
        static::assertSame('completed', $project->getStatus());
        static::assertTrue($project->isInternal());
        static::assertSame('regie', $project->getProjectType());
        static::assertSame($kam, $project->getKeyAccountManager());
        static::assertSame($pm, $project->getProjectManager());
    }

    public function testProjectStatusValidation(): void
    {
        $project = new Project();

        // Test valid statuses
        $validStatuses = ['active', 'completed', 'cancelled'];
        foreach ($validStatuses as $status) {
            $project->setStatus($status);
            static::assertEquals($status, $project->getStatus());
        }
    }

    public function testProjectTypeValidation(): void
    {
        $project = new Project();

        // Test valid project types
        $validTypes = ['forfait', 'regie'];
        foreach ($validTypes as $type) {
            $project->setProjectType($type);
            static::assertEquals($type, $project->getProjectType());
        }
    }

    public function testProjectDateManagement(): void
    {
        $project = new Project();
        $startDate = new DateTime('2023-01-15');
        $endDate = new DateTime('2023-06-15');

        $project->setStartDate($startDate);
        $project->setEndDate($endDate);

        static::assertSame($startDate, $project->getStartDate());
        static::assertSame($endDate, $project->getEndDate());

        // Test null dates
        $project->setStartDate(null);
        $project->setEndDate(null);
        static::assertNull($project->getStartDate());
        static::assertNull($project->getEndDate());
    }

    public function testProjectStringRepresentation(): void
    {
        $project = new Project();
        $project->setName('Test Project');

        // Project doesn't implement Stringable, so we test the name getter instead
        static::assertSame('Test Project', $project->getName());
    }

    public function testProjectClientAssociation(): void
    {
        $project = new Project();
        $client = new Client();

        $project->setClient($client);
        static::assertSame($client, $project->getClient());

        $project->setClient(null);
        static::assertNull($project->getClient());
    }
}
