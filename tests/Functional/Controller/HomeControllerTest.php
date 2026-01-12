<?php

namespace App\Tests\Functional\Controller;

use App\Factory\ContributorFactory;
use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class HomeControllerTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    public function testHomepageLoadsForAnonymousUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'sérénité'); // Check for key content (updated after marketing rewrite)
    }

    public function testHomepageLoadsForAuthenticatedUser(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne();
        // Link a Contributor to the user for the controller's optional section
        ContributorFactory::createOne(['user' => $user]);

        $client->loginUser($user);

        $client->request('GET', '/app');
        $this->assertResponseIsSuccessful();
        $this->assertPageTitleContains(''); // Smoke test: page rendered
    }
}
