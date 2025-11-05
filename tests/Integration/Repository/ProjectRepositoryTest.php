<?php

namespace App\Tests\Integration\Repository;

use App\Factory\ProjectFactory;
use App\Repository\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class ProjectRepositoryTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    public function testCountActiveProjectsAndStatusStats(): void
    {
        self::bootKernel();
        $repo = static::getContainer()->get(ProjectRepository::class);

        // Seed
        ProjectFactory::createMany(3, ['status' => 'active']);
        ProjectFactory::createMany(2, ['status' => 'completed']);
        ProjectFactory::createOne(['status' => 'cancelled']);

        $this->assertSame(3, (int) $repo->countActiveProjects());

        $stats = $repo->getProjectsByStatus();
        $this->assertSame(3, (int) ($stats['active'] ?? 0));
        $this->assertSame(2, (int) ($stats['completed'] ?? 0));
        $this->assertSame(1, (int) ($stats['cancelled'] ?? 0));
    }
}
