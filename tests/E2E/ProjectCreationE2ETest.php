<?php

declare(strict_types=1);

namespace App\Tests\E2E;

use App\Factory\UserFactory;
use Symfony\Component\Panther\PantherTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @group e2e
 */
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
        $form    = $crawler
            ->filter('form')
            ->form([
                '_username' => $user->getEmail(),
                '_password' => 'password',
            ]);
        $client->submit($form);
        $client->waitForElementToContain('body', '');

        // Navigate to project creation page
        $crawler = $client->request('GET', '/projects/new');
        $client->waitFor('form');

        // Minimal form submission: only name (others optional)
        $form = $crawler
            ->filter('form')
            ->form([
                'project[name]'        => 'E2E Demo Project',
                'project[status]'      => 'active',
                'project[projectType]' => 'forfait',
            ]);
        $client->submit($form);

        // Expect redirect to show page and project name visible
        $client->waitForElementToContain('h5, h1, h2, .page-title-box h4', 'E2E Demo Project');
        $this->assertStringContainsString(
            '/projects/',
            (string) parse_url((string) $client->getCurrentURL(), PHP_URL_PATH),
        );
    }
}
