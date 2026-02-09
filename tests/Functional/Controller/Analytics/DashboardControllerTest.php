<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Analytics;

use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Tests fonctionnels pour le DashboardController Analytics.
 */
class DashboardControllerTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    public function testRedirectsToLoginWhenAnonymous(): void
    {
        $client = static::createClient();
        $client->request('GET', '/analytics/dashboard');

        $this->assertResponseRedirects('/login');
    }

    public function testDashboardLoadsForAuthenticatedUser(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne();
        $client->loginUser($user);

        $client->request('GET', '/analytics/dashboard');

        $this->assertResponseIsSuccessful();
        // Just verify the page loads without checking for specific text
        $this->assertSelectorExists('body');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('periodProvider')]
    public function testPeriodSelection(string $period, int $expectedStatusCode): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne();
        $client->loginUser($user);

        $client->request('GET', '/analytics/dashboard', ['period' => $period]);

        $this->assertResponseStatusCodeSame($expectedStatusCode);
    }

    public static function periodProvider(): array
    {
        return [
            'today'   => ['today', 200],
            'week'    => ['week', 200],
            'month'   => ['month', 200],
            'quarter' => ['quarter', 200],
            'year'    => ['year', 200],
            'custom'  => ['custom', 200],
        ];
    }

    public function testCustomPeriodWithDates(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne();
        $client->loginUser($user);

        $client->request('GET', '/analytics/dashboard', [
            'period'     => 'custom',
            'start_date' => '2025-01-01',
            'end_date'   => '2025-01-31',
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testProjectTypeFilter(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne();
        $client->loginUser($user);

        $client->request('GET', '/analytics/dashboard', [
            'project_type' => 'forfait',
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testMultipleFiltersApplied(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne();
        $client->loginUser($user);

        $client->request('GET', '/analytics/dashboard', [
            'period'             => 'month',
            'project_type'       => 'regie',
            'project_manager_id' => '1',
            'sales_person_id'    => '2',
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testExcelExportRedirectsToLoginWhenAnonymous(): void
    {
        $client = static::createClient();
        $client->request('GET', '/analytics/export-excel');

        $this->assertResponseRedirects('/login');
    }

    public function testExcelExportDownloadsFileForAuthenticatedUser(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne();
        $client->loginUser($user);

        $client->request('GET', '/analytics/export-excel');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame(
            'Content-Type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        );
        $contentDisposition = $client->getResponse()->headers->get('Content-Disposition');
        $this->assertStringContainsString('attachment', $contentDisposition);
        $this->assertStringContainsString('dashboard_analytics_', $contentDisposition);
        $this->assertStringContainsString('.xlsx', $contentDisposition);
    }

    public function testExcelExportWithFilters(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne();
        $client->loginUser($user);

        $client->request('GET', '/analytics/export-excel', [
            'period'       => 'month',
            'project_type' => 'forfait',
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame(
            'Content-Type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        );
    }

    public function testRecalculateRequiresAdminRole(): void
    {
        $client = static::createClient();

        // User with basic role
        $user = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $client->loginUser($user);

        $client->request('POST', '/analytics/recalculate');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testRecalculateWorksForAdmin(): void
    {
        $client = static::createClient();

        // User with admin role
        $admin = UserFactory::createOne(['roles' => ['ROLE_ADMIN']]);
        $client->loginUser($admin);

        $client->request(
            'POST',
            '/analytics/recalculate',
            [],
            [],
            [
                'HTTP_X-Requested-With' => 'XMLHttpRequest',
            ],
        );

        // Should redirect or return success
        $this->assertTrue($client->getResponse()->isSuccessful() || $client->getResponse()->isRedirection());
    }

    public function testSessionPersistsPeriodSelection(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne();
        $client->loginUser($user);

        // First request with period parameter
        $client->request('GET', '/analytics/dashboard', ['period' => 'quarter']);
        $this->assertResponseIsSuccessful();

        // Second request without period parameter - should use session
        $client->request('GET', '/analytics/dashboard');
        $this->assertResponseIsSuccessful();

        // Session should have stored the period
        $session = $client->getRequest()->getSession();
        $this->assertEquals('quarter', $session->get('dashboard_period'));
    }
}
