<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Client;

use App\Entity\Client;
use App\Factory\UserFactory;
use App\Tests\Support\MultiTenantTestTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * E2E tests for the new EPIC-001 Phase 3 DDD-based Client routes:
 *  - POST /clients/new-via-ddd
 *  - GET  /clients/{id}/edit-via-ddd
 *  - POST /clients/{id}/edit-via-ddd
 *  - POST /clients/{id}/delete-via-ddd
 *
 * Validates the full stack:
 *   Controller → UseCase → DddRepository → Translator → flat persistence
 *
 * Sprint-026 TEST-FUNCTIONAL-FIXES-003 : marker `skip-pre-push` retiré
 * — tests passent désormais en pre-push.
 *
 * @see ADR-0008 ACL pattern
 * @see ADR-0009 Controller migration pattern
 */
final class ClientControllerDddTest extends WebTestCase
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

    public function testCreateClientViaDddRoute(): void
    {
        $admin = UserFactory::createOne(['roles' => ['ROLE_CHEF_PROJET']]);
        $this->client->loginUser($admin);

        $this->client->request('POST', '/clients/new', [
            'name' => 'Acme via DDD',
            'service_level' => 'standard',
            'description' => 'Created via DDD use case',
        ]);

        self::assertResponseRedirects();

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $client = $em->getRepository(Client::class)->findOneBy(['name' => 'Acme via DDD']);
        static::assertNotNull($client);
        static::assertSame('Acme via DDD', $client->name);
    }

    public function testCreateClientViaDddRejectsShortName(): void
    {
        $admin = UserFactory::createOne(['roles' => ['ROLE_CHEF_PROJET']]);
        $this->client->loginUser($admin);

        $this->client->request('POST', '/clients/new', [
            'name' => 'A', // Too short — CompanyName VO requires min 2 chars
            'service_level' => 'standard',
        ]);

        // Redirects with flash danger (validation rejected, no client created)
        self::assertResponseRedirects();

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $client = $em->getRepository(Client::class)->findOneBy(['name' => 'A']);
        static::assertNull($client);
    }

    public function testEditViaDddRoute(): void
    {
        $admin = UserFactory::createOne(['roles' => ['ROLE_CHEF_PROJET']]);
        $em = static::getContainer()->get(EntityManagerInterface::class);

        // Seed a client
        $existing = new Client();
        $existing->setCompany($this->getTestCompany());
        $existing->name = 'Original Name';
        $existing->serviceLevel = 'low';
        $em->persist($existing);
        $em->flush();
        $clientId = $existing->id;

        $this->client->loginUser($admin);
        $this->client->request('POST', '/clients/'.$clientId.'/edit-via-ddd', [
            'name' => 'Updated Name',
            'service_level' => 'enterprise',
            'description' => 'New description',
        ]);

        self::assertResponseRedirects();

        $em->clear();
        $reloaded = $em->getRepository(Client::class)->find($clientId);
        static::assertSame('Updated Name', $reloaded->name);
        // ENTERPRISE → vip via translator (ADR-0005 lossy mapping)
        static::assertSame('vip', $reloaded->serviceLevel);
    }

    public function testDeleteViaDddRoute(): void
    {
        $admin = UserFactory::createOne(['roles' => ['ROLE_ADMIN']]);
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $existing = new Client();
        $existing->setCompany($this->getTestCompany());
        $existing->name = 'Doomed Client';
        $em->persist($existing);
        $em->flush();
        $clientId = $existing->id;

        $this->client->loginUser($admin);
        $this->client->request('POST', '/clients/'.$clientId.'/delete-via-ddd');

        self::assertResponseRedirects('/clients');

        $em->clear();
        static::assertNull($em->getRepository(Client::class)->find($clientId));
    }
}
