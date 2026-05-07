<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Order;

use App\Entity\Client;
use App\Entity\Order;
use App\Factory\UserFactory;
use App\Tests\Support\MultiTenantTestTrait;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * E2E tests for EPIC-001 Phase 4 — Order legacy decommission.
 *
 * Validates that `/orders/new` (POST) is now backed by the DDD use case
 * stack (CreateOrderQuoteUseCase → OrderDddToFlatTranslator → flat
 * persistence) AFTER promotion from `/orders/new-via-ddd`.
 *
 * Side-effect verified : auto-generation orderNumber préservée si non fourni.
 *
 * @see ADR-0009 Controller migration pattern (Phase 4 critères)
 */
#[Group('skip-pre-push')]
final class OrderControllerDddTest extends WebTestCase
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

    public function testCreateOrderViaPromotedRoute(): void
    {
        $user = UserFactory::createOne(['roles' => ['ROLE_CHEF_PROJET']]);
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $clientEntity = $this->makeClient($em);

        $this->client->loginUser($user);
        $this->client->request('POST', '/orders/new', [
            'name' => 'Quote Q1',
            'client_id' => $clientEntity->id,
            'reference' => 'D-TEST-001',
            'contract_type' => 'forfait',
            'amount' => '5000',
            'description' => 'Phase 4 promoted route',
        ]);

        self::assertResponseRedirects();

        $order = $em->getRepository(Order::class)->findOneBy(['orderNumber' => 'D-TEST-001']);
        self::assertNotNull($order);
        self::assertSame('Quote Q1', $order->name);
    }

    public function testCreateOrderAutoGeneratesReferenceIfMissing(): void
    {
        $user = UserFactory::createOne(['roles' => ['ROLE_CHEF_PROJET']]);
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $clientEntity = $this->makeClient($em);

        $this->client->loginUser($user);
        $this->client->request('POST', '/orders/new', [
            'name' => 'Auto Ref Order',
            'client_id' => $clientEntity->id,
            // pas de reference — doit être auto-générée
            'contract_type' => 'forfait',
            'amount' => '1000',
        ]);

        self::assertResponseRedirects();

        $order = $em->getRepository(Order::class)->findOneBy(['name' => 'Auto Ref Order']);
        self::assertNotNull($order);
        self::assertNotEmpty($order->orderNumber);
    }

    public function testCreateOrderRejectsInvalidContractType(): void
    {
        $user = UserFactory::createOne(['roles' => ['ROLE_CHEF_PROJET']]);
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $clientEntity = $this->makeClient($em);

        $this->client->loginUser($user);
        $this->client->request('POST', '/orders/new', [
            'name' => 'Bad CT',
            'client_id' => $clientEntity->id,
            'reference' => 'D-BAD-CT',
            'contract_type' => 'inexistant',
            'amount' => '1000',
        ]);

        self::assertResponseRedirects('/orders');

        $order = $em->getRepository(Order::class)->findOneBy(['orderNumber' => 'D-BAD-CT']);
        self::assertNull($order);
    }

    public function testGetNewRendersForm(): void
    {
        $user = UserFactory::createOne(['roles' => ['ROLE_CHEF_PROJET']]);
        $this->client->loginUser($user);

        $this->client->request('GET', '/orders/new');

        self::assertResponseIsSuccessful();
    }

    public function testLegacyDddRouteRemoved(): void
    {
        $user = UserFactory::createOne(['roles' => ['ROLE_CHEF_PROJET']]);
        $this->client->loginUser($user);

        $this->client->request('POST', '/orders/new-via-ddd', [
            'name' => 'No DDD route',
            'reference' => 'D-NO-DDD',
            'contract_type' => 'forfait',
            'amount' => '100',
        ]);

        // Route promoted/removed : Symfony match `/{id}` (GET) → 405 pour POST.
        self::assertContains($this->client->getResponse()->getStatusCode(), [404, 405]);
    }

    private function makeClient(EntityManagerInterface $em): Client
    {
        $client = new Client();
        $client->setCompany($this->getTestCompany());
        $client->name = 'Order Test Client';
        $em->persist($client);
        $em->flush();

        return $client;
    }
}
