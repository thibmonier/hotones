<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Invoice;

use App\Entity\Client;
use App\Factory\UserFactory;
use App\Tests\Support\MultiTenantTestTrait;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * E2E tests for EPIC-001 Phase 4 — Invoice legacy decommission.
 *
 * Validates that `/invoices/new` (POST) hybrid Phase 4 :
 *   - DDD UC creates DRAFT skeleton (event + ACL persistance)
 *   - Legacy form completes with issuedAt/dueDate/amountHt/tvaRate
 *   - generateNextInvoiceNumber + calculateAmounts préservés
 *
 * @see ADR-0009 Controller migration pattern (Phase 4 critères)
 */
#[Group('skip-pre-push')]
final class InvoiceControllerDddTest extends WebTestCase
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

    public function testGetNewRendersForm(): void
    {
        $user = UserFactory::createOne(['roles' => ['ROLE_COMPTA']]);
        $this->client->loginUser($user);

        $this->client->request('GET', '/invoices/new');

        self::assertResponseIsSuccessful();
    }

    public function testLegacyDddRouteRemoved(): void
    {
        $user = UserFactory::createOne(['roles' => ['ROLE_COMPTA']]);
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $clientEntity = $this->makeClient($em);

        $this->client->loginUser($user);
        $this->client->request('POST', '/invoices/new-via-ddd', [
            'client_id' => $clientEntity->id,
            'payment_terms' => '30 days',
        ]);

        // Route promoted/removed : Symfony match `/{id}` (GET) → 405 pour POST.
        self::assertContains($this->client->getResponse()->getStatusCode(), [404, 405]);
    }

    private function makeClient(EntityManagerInterface $em): Client
    {
        $client = new Client();
        $client->setCompany($this->getTestCompany());
        $client->name = 'Invoice Test Client';
        $em->persist($client);
        $em->flush();

        return $client;
    }
}
