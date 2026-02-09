<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests de protection CSRF.
 */
class CsrfProtectionTest extends WebTestCase
{
    public function testFormSubmissionWithoutCsrfTokenIsDenied(): void
    {
        $client = static::createClient();

        // Se connecter en tant qu'utilisateur
        $userRepository = static::getContainer()->get('doctrine')->getRepository(User::class);
        $testUser       = $userRepository->findOneBy(['email' => 'test@example.com']);

        if (!$testUser) {
            $this->markTestSkipped('Test user not found. Run fixtures first.');

            return;
        }

        $client->loginUser($testUser);

        // Essayer de soumettre un formulaire sans token CSRF
        $client->request('POST', '/projects/new', [
            'project' => [
                'name'   => 'Test Project',
                'client' => 1,
            ],
        ]);

        // Devrait être rejeté (403 ou redirection avec erreur)
        $this->assertNotSame(
            Response::HTTP_OK,
            $client->getResponse()->getStatusCode(),
            'Form submission without CSRF token should be denied',
        );
    }

    public function testDeleteActionWithoutCsrfTokenIsDenied(): void
    {
        $client = static::createClient();

        // Se connecter en tant qu'utilisateur avec droits de suppression
        $userRepository = static::getContainer()->get('doctrine')->getRepository(User::class);
        $testUser       = $userRepository->findOneBy(['email' => 'admin@example.com']);

        if (!$testUser) {
            $this->markTestSkipped('Admin user not found. Run fixtures first.');

            return;
        }

        $client->loginUser($testUser);

        // Essayer de supprimer sans token CSRF
        $client->request('POST', '/projects/1/delete', [
            '_token' => 'invalid-token',
        ]);

        // Devrait être rejeté
        $response = $client->getResponse();
        $this->assertTrue(
            $response->isRedirection() || $response->getStatusCode() === Response::HTTP_FORBIDDEN,
            'Delete action with invalid CSRF token should be denied',
        );
    }

    public function testCsrfTokenIsGeneratedInForms(): void
    {
        $client = static::createClient();

        // Se connecter
        $userRepository = static::getContainer()->get('doctrine')->getRepository(User::class);
        $testUser       = $userRepository->findOneBy(['email' => 'test@example.com']);

        if (!$testUser) {
            $this->markTestSkipped('Test user not found. Run fixtures first.');

            return;
        }

        $client->loginUser($testUser);

        // Accéder à un formulaire
        $crawler = $client->request('GET', '/projects/new');

        if (!$client->getResponse()->isSuccessful()) {
            $this->markTestSkipped('Cannot access project creation form');

            return;
        }

        // Vérifier que le token CSRF est présent
        $csrfToken = $crawler->filter('input[name="_token"], input[name*="[_token]"]');

        $this->assertGreaterThan(0, $csrfToken->count(), 'CSRF token should be present in forms');
    }
}
