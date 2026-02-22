<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Project;
use App\Tests\Support\MultiTenantTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class ProjectControllerFilterTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;
    use MultiTenantTestTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->testCompany = $this->createTestCompany();
        $this->testUser    = $this->authenticateTestUser($this->testCompany, ['ROLE_INTERVENANT']);
    }

    private function createProject(string $name, string $status = 'active', string $type = 'forfait'): Project
    {
        $project = new Project();
        $project->setName($name);
        $project->setCompany($this->getTestCompany());
        $project->setStatus($status);
        $project->setProjectType($type);
        $project->setStartDate(new \DateTime('2026-01-15'));
        $project->setEndDate(new \DateTime('2026-06-30'));

        $em = $this->getEntityManager();
        $em->persist($project);
        $em->flush();

        return $project;
    }

    public function testIndexReturnsSuccessful(): void
    {
        $this->client->loginUser($this->getTestUser());
        $this->client->request('GET', '/projects');

        $this->assertResponseIsSuccessful();
    }

    public function testFilterByStatus(): void
    {
        $this->createProject('Active Project', 'active');
        $this->createProject('Completed Project', 'completed');

        $this->client->loginUser($this->getTestUser());
        $this->client->request('GET', '/projects', ['status' => 'active']);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Active Project');
    }

    public function testFilterByProjectType(): void
    {
        $this->createProject('Forfait Project', 'active', 'forfait');
        $this->createProject('Regie Project', 'active', 'regie');

        $this->client->loginUser($this->getTestUser());
        $this->client->request('GET', '/projects', ['project_type' => 'forfait']);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Forfait Project');
    }

    public function testCombineMultipleFilters(): void
    {
        $this->createProject('Active Forfait', 'active', 'forfait');
        $this->createProject('Active Regie', 'active', 'regie');
        $this->createProject('Completed Forfait', 'completed', 'forfait');

        $this->client->loginUser($this->getTestUser());
        $this->client->request('GET', '/projects', [
            'status'       => 'active',
            'project_type' => 'forfait',
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Active Forfait');
    }

    public function testSearchFilter(): void
    {
        $this->createProject('Alpha Project');
        $this->createProject('Beta Project');

        $this->client->loginUser($this->getTestUser());
        $this->client->request('GET', '/projects', ['search' => 'Alpha']);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Alpha Project');
    }

    public function testResetFilters(): void
    {
        $this->client->loginUser($this->getTestUser());
        $this->client->request('GET', '/projects', ['reset' => '1']);

        $this->assertResponseRedirects('/projects');
    }

    public function testFiltersInUrlAreBookmarkable(): void
    {
        $this->createProject('Test Project', 'active');

        $this->client->loginUser($this->getTestUser());
        $this->client->request('GET', '/projects?status=active&project_type=forfait&search=Test');

        $this->assertResponseIsSuccessful();
    }

    public function testNoResultsDisplaysEmptyState(): void
    {
        $this->client->loginUser($this->getTestUser());
        $this->client->request('GET', '/projects', ['search' => 'nonexistent_xyz_123']);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Aucun projet');
    }

    public function testPagination(): void
    {
        for ($i = 0; $i < 15; ++$i) {
            $this->createProject('Project '.$i);
        }

        $this->client->loginUser($this->getTestUser());
        $this->client->request('GET', '/projects', ['per_page' => '10']);

        $this->assertResponseIsSuccessful();
    }

    public function testSorting(): void
    {
        $this->createProject('AAA Project');
        $this->createProject('ZZZ Project');

        $this->client->loginUser($this->getTestUser());
        $this->client->request('GET', '/projects', ['sort' => 'name', 'dir' => 'DESC']);

        $this->assertResponseIsSuccessful();
    }

    public function testRequiresAuthentication(): void
    {
        $this->client->request('GET', '/projects');

        $this->assertResponseRedirects('/login');
    }
}
