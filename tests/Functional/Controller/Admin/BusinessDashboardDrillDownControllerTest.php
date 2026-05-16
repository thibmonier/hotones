<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Admin;

use App\Factory\ClientFactory;
use App\Factory\InvoiceFactory;
use App\Factory\OrderFactory;
use App\Factory\ProjectFactory;
use App\Factory\UserFactory;
use App\Tests\Support\MultiTenantTestTrait;
use DateTime;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Functional tests for `/admin/business-dashboard/drill-down/{kpi}` (US-116 T-116-04).
 *
 * Sprint-026 TEST-FUNCTIONAL-FIXES-003 : marker `skip-pre-push` retiré.
 */
final class BusinessDashboardDrillDownControllerTest extends WebTestCase
{
    use Factories;
    use MultiTenantTestTrait;
    use ResetDatabase;

    public function testRedirectsAnonymousToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/business-dashboard/drill-down/dso');

        self::assertResponseRedirects();
    }

    public function testRendersDsoDrillDownWithClientList(): void
    {
        $browser = static::createClient();
        $this->setUpMultiTenant();
        $admin = UserFactory::createOne([
            'company' => $this->getTestCompany(),
            'roles' => ['ROLE_ADMIN'],
        ]);
        $browser->loginUser($admin);

        $now = new DateTimeImmutable();
        $clientAcme = ClientFactory::createOne(['company' => $this->getTestCompany(), 'name' => 'Acme']);
        $clientBeta = ClientFactory::createOne(['company' => $this->getTestCompany(), 'name' => 'Beta']);
        $this->createPaidInvoice($clientAcme, daysAgoPaid: 5, amountTtc: '1000.00', delayDays: 40);
        $this->createPaidInvoice($clientBeta, daysAgoPaid: 5, amountTtc: '500.00', delayDays: 10);

        $browser->request('GET', '/admin/business-dashboard/drill-down/dso?window=90');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'DSO');
        self::assertSelectorTextContains('body', 'Acme');
        self::assertSelectorTextContains('body', 'Beta');
        // Tri valeur décroissante : Acme (lent) avant Beta
        $content = $browser->getResponse()->getContent();
        self::assertIsString($content);
        self::assertLessThan(
            strpos($content, 'Beta'),
            strpos($content, 'Acme'),
            'Acme (DSO 40j) doit apparaître avant Beta (DSO 10j) — tri décroissant',
        );
    }

    public function testRendersEmptyStateWhenNoData(): void
    {
        $browser = static::createClient();
        $this->setUpMultiTenant();
        $admin = UserFactory::createOne([
            'company' => $this->getTestCompany(),
            'roles' => ['ROLE_ADMIN'],
        ]);
        $browser->loginUser($admin);

        $browser->request('GET', '/admin/business-dashboard/drill-down/dso?window=30');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Aucune donnée disponible');
    }

    public function testExportCsvReturnsCorrectHeadersAndContent(): void
    {
        $browser = static::createClient();
        $this->setUpMultiTenant();
        $admin = UserFactory::createOne([
            'company' => $this->getTestCompany(),
            'roles' => ['ROLE_ADMIN'],
        ]);
        $browser->loginUser($admin);

        $clientAcme = ClientFactory::createOne(['company' => $this->getTestCompany(), 'name' => 'Acme']);
        $this->createPaidInvoice($clientAcme, daysAgoPaid: 5, amountTtc: '1000.00', delayDays: 40);

        $browser->request('GET', '/admin/business-dashboard/drill-down/dso/export?window=90');

        self::assertResponseIsSuccessful();
        $response = $browser->getResponse();
        self::assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        self::assertStringContainsString('attachment', (string) $response->headers->get('Content-Disposition'));
        self::assertStringContainsString('kpi-drill-down-dso-window-90j', (string) $response->headers->get('Content-Disposition'));

        $csv = (string) $response->getContent();
        self::assertStringContainsString('client_name,valeur_kpi,sample_count,window', $csv);
        self::assertStringContainsString('Acme,40.0,1,90j', $csv);
    }

    public function testRejectsInvalidKpiParam(): void
    {
        $browser = static::createClient();
        $this->setUpMultiTenant();
        $admin = UserFactory::createOne([
            'company' => $this->getTestCompany(),
            'roles' => ['ROLE_ADMIN'],
        ]);
        $browser->loginUser($admin);

        $browser->request('GET', '/admin/business-dashboard/drill-down/foo');

        // Route requirement kpi=dso|lead-time|conversion|margin → 404
        self::assertResponseStatusCodeSame(404);
    }

    public function testRendersConversionDrillDownWithClientList(): void
    {
        $browser = static::createClient();
        $this->setUpMultiTenant();
        $admin = UserFactory::createOne([
            'company' => $this->getTestCompany(),
            'roles' => ['ROLE_ADMIN'],
        ]);
        $browser->loginUser($admin);

        $clientAcme = ClientFactory::createOne(['company' => $this->getTestCompany(), 'name' => 'Acme']);
        $clientBeta = ClientFactory::createOne(['company' => $this->getTestCompany(), 'name' => 'Beta']);
        $this->createOrderForClient($clientAcme, status: 'signe', daysAgo: 5);
        $this->createOrderForClient($clientAcme, status: 'gagne', daysAgo: 10);
        $this->createOrderForClient($clientBeta, status: 'perdu', daysAgo: 5);

        $browser->request('GET', '/admin/business-dashboard/drill-down/conversion?window=30');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Taux de conversion');
        self::assertSelectorTextContains('body', 'Acme');
        self::assertSelectorTextContains('body', 'Beta');
        // Acme 100 % > Beta 0 % → Acme avant Beta (tri décroissant)
        $content = (string) $browser->getResponse()->getContent();
        self::assertLessThan(
            strpos($content, 'Beta'),
            strpos($content, 'Acme'),
            'Acme (100 %) doit apparaître avant Beta (0 %) — tri décroissant',
        );
    }

    public function testExportCsvForConversionAggregates(): void
    {
        $browser = static::createClient();
        $this->setUpMultiTenant();
        $admin = UserFactory::createOne([
            'company' => $this->getTestCompany(),
            'roles' => ['ROLE_ADMIN'],
        ]);
        $browser->loginUser($admin);

        $clientAcme = ClientFactory::createOne(['company' => $this->getTestCompany(), 'name' => 'Acme']);
        $this->createOrderForClient($clientAcme, status: 'signe', daysAgo: 5);
        $this->createOrderForClient($clientAcme, status: 'gagne', daysAgo: 10);

        $browser->request('GET', '/admin/business-dashboard/drill-down/conversion/export?window=30');

        self::assertResponseIsSuccessful();
        $response = $browser->getResponse();
        self::assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        self::assertStringContainsString('kpi-drill-down-conversion-window-30j', (string) $response->headers->get('Content-Disposition'));

        $csv = (string) $response->getContent();
        self::assertStringContainsString('client_name,valeur_kpi,sample_count,window', $csv);
        self::assertStringContainsString('Acme,100.0,2,30j', $csv);
    }

    public function testRendersMarginDrillDownWithClientList(): void
    {
        $browser = static::createClient();
        $this->setUpMultiTenant();
        $admin = UserFactory::createOne([
            'company' => $this->getTestCompany(),
            'roles' => ['ROLE_ADMIN'],
        ]);
        $browser->loginUser($admin);

        $clientAcme = ClientFactory::createOne(['company' => $this->getTestCompany(), 'name' => 'Acme']);
        $clientBeta = ClientFactory::createOne(['company' => $this->getTestCompany(), 'name' => 'Beta']);
        // Acme : 1 fresh (2 j) → 100 % adoption
        $this->createActiveProjectForClient($clientAcme, marginAgeDays: 2);
        // Beta : 1 stale critical (60 j) → 0 % adoption
        $this->createActiveProjectForClient($clientBeta, marginAgeDays: 60);

        $browser->request('GET', '/admin/business-dashboard/drill-down/margin');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Adoption marge');
        self::assertSelectorTextContains('body', 'Acme');
        self::assertSelectorTextContains('body', 'Beta');
        // Tri croissant : Beta (0 %) avant Acme (100 %) — clients en retard en tête
        $content = (string) $browser->getResponse()->getContent();
        self::assertLessThan(
            strpos($content, 'Acme'),
            strpos($content, 'Beta'),
            'Beta (0 %) doit apparaître avant Acme (100 %) — tri croissant retard en tête',
        );
    }

    public function testExportCsvForMarginAggregates(): void
    {
        $browser = static::createClient();
        $this->setUpMultiTenant();
        $admin = UserFactory::createOne([
            'company' => $this->getTestCompany(),
            'roles' => ['ROLE_ADMIN'],
        ]);
        $browser->loginUser($admin);

        $clientAcme = ClientFactory::createOne(['company' => $this->getTestCompany(), 'name' => 'Acme']);
        $this->createActiveProjectForClient($clientAcme, marginAgeDays: 2);

        $browser->request('GET', '/admin/business-dashboard/drill-down/margin/export?window=30');

        self::assertResponseIsSuccessful();
        $response = $browser->getResponse();
        self::assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        self::assertStringContainsString('kpi-drill-down-margin-window-30j', (string) $response->headers->get('Content-Disposition'));

        $csv = (string) $response->getContent();
        self::assertStringContainsString('client_name,valeur_kpi,sample_count,window', $csv);
        self::assertStringContainsString('Acme,100.0,1,30j', $csv);
    }

    private function createOrderForClient(object $client, string $status, int $daysAgo): void
    {
        $project = ProjectFactory::createOne([
            'company' => $this->getTestCompany(),
            'client' => $client,
        ]);

        $order = OrderFactory::createOne([
            'company' => $this->getTestCompany(),
            'project' => $project,
            'status' => $status,
        ]);

        $em = static::getContainer()->get('doctrine')->getManager();
        $em->getConnection()->executeStatement(
            'UPDATE orders SET created_at = :createdAt WHERE id = :id',
            [
                'createdAt' => (new DateTimeImmutable())->modify(sprintf('-%d days', $daysAgo))->format('Y-m-d H:i:s'),
                'id' => $order->id,
            ],
        );
    }

    private function createActiveProjectForClient(object $client, int $marginAgeDays): void
    {
        $project = ProjectFactory::createOne([
            'company' => $this->getTestCompany(),
            'client' => $client,
            'status' => 'active',
        ]);

        $em = static::getContainer()->get('doctrine')->getManager();
        $em->getConnection()->executeStatement(
            'UPDATE projects SET marge_calculated_at = :calculatedAt WHERE id = :id',
            [
                'calculatedAt' => (new DateTimeImmutable())->modify(sprintf('-%d days', $marginAgeDays))->format('Y-m-d H:i:s'),
                'id' => $project->id,
            ],
        );
    }

    private function createPaidInvoice(object $client, int $daysAgoPaid, string $amountTtc, int $delayDays): void
    {
        $now = new DateTimeImmutable();
        $paidAt = $now->modify(sprintf('-%d days', $daysAgoPaid));
        $issuedAt = $paidAt->modify(sprintf('-%d days', $delayDays));

        ProjectFactory::createOne(['company' => $this->getTestCompany()]);
        $order = OrderFactory::createOne([
            'company' => $this->getTestCompany(),
            'project' => null,
            'validatedAt' => DateTime::createFromImmutable($issuedAt),
        ]);

        InvoiceFactory::createOne([
            'company' => $this->getTestCompany(),
            'client' => $client,
            'order' => $order,
            'status' => \App\Entity\Invoice::STATUS_PAID,
            'issuedAt' => DateTime::createFromImmutable($issuedAt),
            'dueDate' => DateTime::createFromImmutable($issuedAt->modify('+30 days')),
            'paidAt' => DateTime::createFromImmutable($paidAt),
            'amountHt' => $amountTtc,
            'amountTva' => '0.00',
            'tvaRate' => '0.00',
            'amountTtc' => $amountTtc,
        ]);
    }
}
