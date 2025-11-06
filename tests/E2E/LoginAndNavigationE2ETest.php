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
            'roles'    => ['ROLE_INTERVENANT'],
            'password' => 'password',
        ]);

        $crawler = $client->request('GET', '/login');
        $client->waitFor('form'); // Ensure page loaded in WebDriver mode

        $form = $crawler->filter('form')->form([
            '_username' => $user->getEmail(),
            '_password' => 'password',
        ]);
        $client->submit($form);

        $client->waitForInvisibility('#password-addon'); // rough wait after submit

        // After successful login, default target is '/'
        $this->assertStringEndsWith('/', parse_url($client->getCurrentURL(), PHP_URL_PATH));
    }

    public function testNavigateToProjectsIndex(): void
    {
        $client = static::createPantherClient();

        $user = UserFactory::createOne([
            'roles'    => ['ROLE_INTERVENANT'],
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
