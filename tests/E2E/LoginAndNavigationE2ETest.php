<?php

namespace App\Tests\E2E;

use App\Factory\UserFactory;
use Symfony\Component\Panther\PantherTestCase; // extends WebTestCase with browser
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @group e2e
 */
class LoginAndNavigationE2ETest extends PantherTestCase
{
    use Factories;
    use ResetDatabase;

    public function testUserCanLoginAndSeeHomepage(): void
    {
        $client = static::createPantherClient();

        $user = UserFactory::createOne([
            'roles'    => ['ROLE_USER', 'ROLE_INTERVENANT'],
            'password' => 'password',
        ]);

        $crawler = $client->request('GET', '/login');
        $client->waitFor('form'); // Ensure page loaded in WebDriver mode

        $form = $crawler->filter('form')->form([
            '_username' => $user->getEmail(),
            '_password' => 'password',
        ]);
        $client->submit($form);

        // Wait for navigation after login (either to / or to 2FA page)
        $client->waitFor('body');
        sleep(2); // Give time for any redirects to complete

        $currentPath = parse_url($client->getCurrentURL(), PHP_URL_PATH);

        // After successful login, should be on homepage (/)
        // If 2FA is enabled, user would be on /2fa instead
        $this->assertStringEndsWith('/', $currentPath,
            sprintf('Expected to be on homepage (/), but currently on: %s', $currentPath),
        );
    }

    public function testNavigateToProjectsIndex(): void
    {
        $client = static::createPantherClient();

        $user = UserFactory::createOne([
            'roles'    => ['ROLE_USER', 'ROLE_INTERVENANT'],
            'password' => 'password',
        ]);

        // Login
        $crawler = $client->request('GET', '/login');
        $form    = $crawler->filter('form')->form([
            '_username' => $user->getEmail(),
            '_password' => 'password',
        ]);
        $client->submit($form);
        $client->waitForElementToContain('h4', '');

        // Go to projects index
        $client->request('GET', '/projects');
        $client->waitFor('h4');
        $this->assertSelectorTextContains('h4', 'Projets');
    }
}
