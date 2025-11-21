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
        sleep(1); // Give time for any redirects to complete

        $currentPath = parse_url($client->getCurrentURL(), PHP_URL_PATH);

        // After successful login, should be on homepage (/) or 2FA page
        // Check that we're not still on /login (which would indicate failed auth)
        $this->assertStringNotContainsString('/login', $currentPath,
            sprintf('Should not be redirected back to login page, currently on: %s', $currentPath),
        );

        // If not on 2FA, verify we can see homepage content
        if (!str_contains($currentPath, '/2fa')) {
            $client->waitFor('.page-title-box, .card, h4');
        }
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
        $client->waitFor('body');
        sleep(1); // Wait for login redirect

        // Go to projects index
        $client->request('GET', '/projects');
        $client->waitFor('.page-title-box, h4, .card');
        $this->assertSelectorTextContains('.page-title-box, h1, h2, h4', 'Projets');
    }
}
