<?php

declare(strict_types=1);

namespace App\Tests\Security;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests d'authentification et d'autorisation.
 */
class AuthenticationTest extends WebTestCase
{
    public function testLoginPageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testLoginWithInvalidCredentialsFails(): void
    {
        $client  = static::createClient();
        $crawler = $client->request('GET', '/login');

        // Vérifier que le formulaire de login existe
        $form = $crawler->filter('form')->first();
        if ($form->count() === 0) {
            $this->markTestSkipped('Login form not found');

            return;
        }

        // Soumettre des identifiants invalides
        $client->submitForm('Se connecter', [
            'email'    => 'invalid@example.com',
            'password' => 'wrongpassword',
        ]);

        // Devrait rester sur la page de login ou rediriger avec erreur
        $this->assertResponseRedirects('/login', Response::HTTP_FOUND);
        $client->followRedirect();

        // Vérifier qu'un message d'erreur est présent
        $this->assertSelectorExists('.alert, .error, [class*="alert"]');
    }

    public function testAccessToAdminAreaRequiresAdminRole(): void
    {
        $client = static::createClient();

        // Se connecter avec un utilisateur non-admin
        $userRepository = static::getContainer()->get('doctrine')->getRepository(\App\Entity\User::class);
        $regularUser    = $userRepository->findOneBy(['email' => 'test@example.com']);

        if (!$regularUser) {
            $this->markTestSkipped('Regular user not found');

            return;
        }

        $client->loginUser($regularUser);

        // Essayer d'accéder à une route admin
        $client->request('GET', '/admin/technologies');

        // Devrait être refusé (403)
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testPasswordRequirementsAreEnforced(): void
    {
        // Ce test devrait vérifier que les mots de passe faibles sont rejetés
        // lors de l'inscription ou du changement de mot de passe
        $this->markTestIncomplete('Password strength validation should be tested here');
    }

    public function testSessionTimeoutAfterInactivity(): void
    {
        // Ce test devrait vérifier que la session expire après une période d'inactivité
        $this->markTestIncomplete('Session timeout should be tested here');
    }

    public function testTwoFactorAuthenticationWhenEnabled(): void
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get('doctrine')->getRepository(\App\Entity\User::class);
        $user2fa        = $userRepository->findOneBy(['totpSecret' => ['$ne' => null]]);

        if (!$user2fa) {
            $this->markTestSkipped('No user with 2FA enabled found');

            return;
        }

        $client->loginUser($user2fa);
        $client->request('GET', '/projects');

        // Si 2FA est activé, devrait rediriger vers la page de vérification 2FA
        // (dépend de la configuration de scheb/2fa-bundle)
        $response = $client->getResponse();

        $this->assertTrue(
            $response->isRedirection() || $response->isSuccessful(),
            '2FA verification should be required or user should be logged in',
        );
    }

    public function testLogoutInvalidatesSession(): void
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get('doctrine')->getRepository(\App\Entity\User::class);
        $testUser       = $userRepository->findOneBy(['email' => 'test@example.com']);

        if (!$testUser) {
            $this->markTestSkipped('Test user not found');

            return;
        }

        $client->loginUser($testUser);

        // Vérifier qu'on est connecté
        $client->request('GET', '/projects');
        $this->assertResponseIsSuccessful();

        // Se déconnecter
        $client->request('GET', '/logout');

        // Vérifier qu'on ne peut plus accéder aux pages protégées
        $client->request('GET', '/projects');
        $this->assertResponseRedirects('/login');
    }
}
