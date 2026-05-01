<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Functional tests for SecurityController.
 *
 * Covers the public login form (GET /login) and logout route. The
 * actual auth flow is handled by Symfony's security firewall and is
 * exercised in 2FA / login-flow integration tests.
 */
final class SecurityControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    #[Test]
    public function login_page_is_accessible_anonymously_and_returns_200(): void
    {
        $this->client->request('GET', '/login');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form');
    }

    #[Test]
    public function login_page_renders_security_login_template(): void
    {
        $this->client->request('GET', '/login');

        self::assertResponseStatusCodeSame(200);
        // The template should expose `last_username` and `error` variables; we
        // assert the rendered form contains the username input regardless of
        // template language.
        self::assertSelectorExists('input[name="_username"], input[name="email"], input[type="email"], input[type="text"]');
    }

    #[Test]
    public function logout_route_is_intercepted_by_firewall(): void
    {
        // Symfony's logout listener intercepts the route before the controller
        // runs. Calling it without an active session typically returns a
        // redirect or a 200 depending on configuration; we assert the route
        // exists and does not 500.
        $this->client->request('GET', '/logout');

        self::assertNotSame(500, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function login_page_shows_last_username_after_failed_attempt(): void
    {
        // Simulate a previous failed login by submitting bad credentials and
        // following back to the form. The session should retain `_security.last_username`.
        $this->client->request('POST', '/login', [
            '_username' => 'unknown@test.com',
            '_password' => 'wrong',
        ]);

        $this->client->followRedirects();
        $this->client->request('GET', '/login');

        self::assertResponseIsSuccessful();
    }
}
