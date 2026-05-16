<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Admin;

use App\Factory\UserFactory;
use App\Tests\Support\MultiTenantTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Functional tests for `/admin/business-dashboard`.
 *
 * - US-110 T-110-04 DSO widget
 * - US-111 T-111-04 billing lead time widget
 * - US-112 T-112-03 margin adoption widget
 *
 * Sprint-026 TEST-FUNCTIONAL-FIXES-003 : marker `skip-pre-push` retiré
 * — tous tests passent désormais en pre-push (passe en CI complète).
 */
final class BusinessDashboardControllerTest extends WebTestCase
{
    use Factories;
    use MultiTenantTestTrait;
    use ResetDatabase;

    public function testRedirectsAnonymousToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/business-dashboard');

        self::assertResponseRedirects();
    }

    public function testRendersDashboardWithBillingLeadTimeWidgetWhenAdminAuthenticated(): void
    {
        $client = static::createClient();
        $this->setUpMultiTenant();

        $admin = UserFactory::createOne([
            'company' => $this->getTestCompany(),
            'roles' => ['ROLE_ADMIN'],
        ]);
        $client->loginUser($admin);

        $client->request('GET', '/admin/business-dashboard');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Dashboard Business');
        self::assertSelectorTextContains('body', 'Temps de facturation');
        self::assertSelectorTextContains('body', '30 jours rolling');
        self::assertSelectorTextContains('body', '90 jours rolling');
        self::assertSelectorTextContains('body', '365 jours rolling');
        self::assertSelectorTextContains('body', 'médiane');
    }

    public function testRendersDashboardWithDsoWidgetWhenAdminAuthenticated(): void
    {
        $client = static::createClient();
        $this->setUpMultiTenant();

        $admin = UserFactory::createOne([
            'company' => $this->getTestCompany(),
            'roles' => ['ROLE_ADMIN'],
        ]);
        $client->loginUser($admin);

        $client->request('GET', '/admin/business-dashboard');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Dashboard Business');
        self::assertSelectorTextContains('body', 'DSO');
        self::assertSelectorTextContains('body', '30 jours rolling');
        self::assertSelectorTextContains('body', '90 jours rolling');
        self::assertSelectorTextContains('body', '365 jours rolling');
    }

    public function testRendersDashboardWithMarginAdoptionWidgetWhenAdminAuthenticated(): void
    {
        $client = static::createClient();
        $this->setUpMultiTenant();

        $admin = UserFactory::createOne([
            'company' => $this->getTestCompany(),
            'roles' => ['ROLE_ADMIN'],
        ]);
        $client->loginUser($admin);

        $client->request('GET', '/admin/business-dashboard');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Adoption marge');
        self::assertSelectorTextContains('body', 'Indicateur trigger abandon ADR-0013');
    }

    public function testForbidsNonAdminUser(): void
    {
        $client = static::createClient();
        $this->setUpMultiTenant();

        $user = UserFactory::createOne([
            'company' => $this->getTestCompany(),
            'roles' => ['ROLE_USER'],
        ]);
        $client->loginUser($user);

        $client->request('GET', '/admin/business-dashboard');

        self::assertResponseStatusCodeSame(403);
    }
}
