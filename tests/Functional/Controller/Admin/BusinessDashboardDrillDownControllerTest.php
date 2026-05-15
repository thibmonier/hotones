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
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Functional tests for `/admin/business-dashboard/drill-down/{kpi}` (US-116 T-116-04).
 */
#[Group('skip-pre-push')]
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

        // Route requirement kpi=dso|lead-time → 404
        self::assertResponseStatusCodeSame(404);
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
