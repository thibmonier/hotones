<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Project;

use App\Entity\Project;
use App\Entity\ProjectTask;
use App\Factory\UserFactory;
use App\Tests\Support\MultiTenantTestTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * E2E tests for EPIC-001 Phase 4 — Project legacy decommission.
 *
 * Validates that `/projects/new` (POST) is now backed by the DDD use case
 * stack (CreateProjectUseCase → DddProjectRepository → Translator → flat
 * persistence) AFTER promotion from `/projects/new-via-ddd`.
 *
 * Side-effect verified : ProjectTask::createDefaultTasks (AVV + Non-vendu)
 * persisted post-UC.
 *
 * @see ADR-0009 Controller migration pattern (Phase 4 critères)
 * @see ADR-0008 ACL pattern
 *
 * Sprint-026 TEST-FUNCTIONAL-FIXES-003 : marker `skip-pre-push` retiré.
 */
final class ProjectControllerDddTest extends WebTestCase
{
    use Factories;
    use MultiTenantTestTrait;
    use ResetDatabase;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->setUpMultiTenant();
    }

    public function testCreateInternalProjectViaPromoted(): void
    {
        $user = UserFactory::createOne(['roles' => ['ROLE_CHEF_PROJET']]);
        $this->client->loginUser($user);

        $this->client->request('POST', '/projects/new', [
            'name' => 'Internal R&D',
            'project_type' => 'forfait',
            'is_internal' => '1',
            'description' => 'Created via promoted DDD route',
        ]);

        self::assertResponseRedirects();

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $project = $em->getRepository(Project::class)->findOneBy(['name' => 'Internal R&D']);
        static::assertNotNull($project);
        static::assertSame('Internal R&D', $project->name);
        static::assertSame('Created via promoted DDD route', $project->description);
    }

    public function testCreateProjectCreatesDefaultTasks(): void
    {
        $user = UserFactory::createOne(['roles' => ['ROLE_CHEF_PROJET']]);
        $this->client->loginUser($user);

        $this->client->request('POST', '/projects/new', [
            'name' => 'Project With Tasks',
            'project_type' => 'forfait',
            'is_internal' => '1',
        ]);

        self::assertResponseRedirects();

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $project = $em->getRepository(Project::class)->findOneBy(['name' => 'Project With Tasks']);
        static::assertNotNull($project);

        // Side-effect : tâches par défaut créées post-UC
        $tasks = $em->getRepository(ProjectTask::class)->findBy(['project' => $project]);
        static::assertGreaterThanOrEqual(2, count($tasks), 'Default tasks (AVV + Non-vendu) must be created');
    }

    public function testCreateProjectRejectsInvalidProjectType(): void
    {
        $user = UserFactory::createOne(['roles' => ['ROLE_CHEF_PROJET']]);
        $this->client->loginUser($user);

        $this->client->request('POST', '/projects/new', [
            'name' => 'Bad Type Project',
            'project_type' => 'inexistant',
            'is_internal' => '1',
        ]);

        // Redirects to index (validation flash danger)
        self::assertResponseRedirects('/projects');

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $project = $em->getRepository(Project::class)->findOneBy(['name' => 'Bad Type Project']);
        static::assertNull($project);
    }

    public function testGetNewRendersForm(): void
    {
        $user = UserFactory::createOne(['roles' => ['ROLE_CHEF_PROJET']]);
        $this->client->loginUser($user);

        $this->client->request('GET', '/projects/new');

        self::assertResponseIsSuccessful();
    }

    public function testLegacyDddRouteRemoved(): void
    {
        $user = UserFactory::createOne(['roles' => ['ROLE_CHEF_PROJET']]);
        $this->client->loginUser($user);

        $this->client->request('POST', '/projects/new-via-ddd', [
            'name' => 'Should not reach DDD route',
            'project_type' => 'forfait',
            'is_internal' => '1',
        ]);

        // Route promoted/removed : Symfony match `/{id}` (GET) → 405 pour POST.
        static::assertContains($this->client->getResponse()->getStatusCode(), [404, 405]);
    }
}
