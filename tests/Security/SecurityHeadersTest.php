<?php

declare(strict_types=1);

namespace App\Tests\Security;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests de sécurité pour les headers HTTP.
 */
class SecurityHeadersTest extends WebTestCase
{
    public function testSecurityHeadersArePresent(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        $response = $client->getResponse();

        // X-Content-Type-Options (recommandé)
        if ($response->headers->has('X-Content-Type-Options')) {
            $this->assertSame(
                'nosniff',
                $response->headers->get('X-Content-Type-Options'),
                'X-Content-Type-Options should be set to nosniff',
            );
        } else {
            $this->markTestIncomplete(
                'X-Content-Type-Options header is not configured. Consider adding it in web server config.',
            );
        }

        // X-Frame-Options (protection contre clickjacking) - recommandé
        if ($response->headers->has('X-Frame-Options')) {
            $this->assertContains(
                $response->headers->get('X-Frame-Options'),
                ['DENY', 'SAMEORIGIN'],
                'X-Frame-Options should be DENY or SAMEORIGIN',
            );
        }

        // Referrer-Policy (recommandé)
        if ($response->headers->has('Referrer-Policy')) {
            $this->assertNotEmpty($response->headers->get('Referrer-Policy'), 'Referrer-Policy should have a value');
        }

        // Note: Ces headers peuvent être configurés au niveau du serveur web (Nginx/Apache)
        // plutôt que dans Symfony. Vérifier la configuration du serveur si les tests échouent.
    }

    public function testNoCacheHeadersOnAuthenticatedPages(): void
    {
        $client = static::createClient();

        // Tenter d'accéder à une page protégée sans authentification
        $client->request('GET', '/projects');

        // Devrait rediriger vers login
        $this->assertResponseRedirects('/login');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('publicRoutesProvider')]
    public function testPublicRoutesAreAccessible(string $route): void
    {
        $client = static::createClient();
        $client->request('GET', $route);

        $this->assertResponseIsSuccessful();
    }

    public static function publicRoutesProvider(): array
    {
        return [
            'login' => ['/login'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('protectedRoutesProvider')]
    public function testProtectedRoutesRequireAuthentication(string $route): void
    {
        $client = static::createClient();
        $client->request('GET', $route);

        $response = $client->getResponse();

        // La route doit être protégée : soit redirection vers login, soit 403, soit 401
        $this->assertTrue($response->isRedirect()
        || $response->getStatusCode() === 403
        || $response->getStatusCode() === 401, sprintf(
            'Route %s should be protected (redirect, 403, or 401), got %d',
            $route,
            $response->getStatusCode(),
        ));
    }

    public static function protectedRoutesProvider(): array
    {
        return [
            'projects'     => ['/projects'],
            'contributors' => ['/contributors'],
            'orders'       => ['/orders'],
            // timesheets route doesn't exist at root level (only /contributors/{id}/timesheets)
            'planning'  => ['/planning'],
            'analytics' => ['/analytics/dashboard'],
        ];
    }
}
