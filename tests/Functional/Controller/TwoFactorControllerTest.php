<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Functional tests for TwoFactorController.
 *
 * Both routes are gated by `IsGranted('IS_AUTHENTICATED_2FA_IN_PROGRESS')`.
 * An anonymous user should be redirected to login (or denied) rather than
 * reaching the controller. The actual 2FA flow (TOTP verification) is
 * handled by scheb/2fa-bundle and is not unit-tested here.
 */
final class TwoFactorControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    #[Test]
    public function formRouteIsNotAccessibleToAnonymousUser(): void
    {
        $this->client->request('GET', '/2fa');

        $status = $this->client->getResponse()->getStatusCode();

        // The 2FA bundle either redirects (302) anonymous users to /login or
        // returns 403 / 401 depending on the firewall config. Anything other
        // than 200 confirms the access guard fired.
        self::assertNotSame(200, $status, 'Expected /2fa to deny anonymous access; got 200.');
        self::assertContains($status, [301, 302, 401, 403], sprintf('Unexpected status %d on /2fa', $status));
    }

    #[Test]
    public function checkRouteIsNotAccessibleToAnonymousUser(): void
    {
        $this->client->request('POST', '/2fa_check');

        $status = $this->client->getResponse()->getStatusCode();
        self::assertNotSame(200, $status, 'Expected /2fa_check to deny anonymous access; got 200.');
        self::assertNotSame(204, $status, 'Anonymous user should not reach controller body returning 204.');
    }

    #[Test]
    public function formRouteDoesNot500(): void
    {
        // Sanity check: route is wired and reaches the firewall layer cleanly.
        $this->client->request('GET', '/2fa');

        self::assertNotSame(500, $this->client->getResponse()->getStatusCode());
    }
}
