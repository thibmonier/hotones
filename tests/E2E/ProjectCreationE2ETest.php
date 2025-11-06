<?php

namespace App\Tests\E2E;

use App\Factory\UserFactory;
use Symfony\Component\Panther\PantherTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class ProjectCreationE2ETest extends PantherTestCase
{
    use Factories;
    use ResetDatabase;

    public function testProjectCreationFlow(): void
    {
        $client = static::createPantherClient();

        $user = UserFactory::createOne([
            'roles'    => ['ROLE_CHEF_PROJET', 'ROLE_INTERVENANT'],
            'password' => 'password',
        ]);

        // Login
        $crawler = $client->request('GET', '/login');
        $form    = $crawler->filter('form')->form([
            '_username' => $user->getEmail(),
            '_password' => 'password',
        ]);
        $client->submit($form);
        $client->waitForElementToContain('body', '');

        // Navigate to project creation page
        $crawler = $client->request('GET', '/projects/new');
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        // Minimal form submission: only name (others optional)
        $form = $crawler->filter('form')->form([
            'name'         => 'E2E Demo Project',
            'status'       => 'active',
            'project_type' => 'forfait',
        ]);
        $client->submit($form);

        // Expect redirect to show page and project name visible
        $client->waitForElementToContain('h5, h1, h2, .page-title-box h4', 'E2E Demo Project');
        $this->assertStringContainsString('/projects/', (string) parse_url($client->getCurrentURL(), PHP_URL_PATH));
    }
}
