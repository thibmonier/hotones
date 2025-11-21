<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests de protection contre les injections SQL.
 */
class SqlInjectionTest extends WebTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('sqlInjectionPayloadsProvider')]
    public function testSearchFieldsAreProtectedAgainstSqlInjection(string $payload): void
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get('doctrine')->getRepository(User::class);
        $testUser       = $userRepository->findOneBy(['email' => 'test@example.com']);

        if (!$testUser) {
            $this->markTestSkipped('Test user not found');

            return;
        }

        $client->loginUser($testUser);

        // Essayer d'injecter du SQL dans les champs de recherche
        $client->request('GET', '/projects', ['search' => $payload]);

        // L'application ne devrait pas crasher
        $response = $client->getResponse();
        $this->assertNotSame(
            500,
            $response->getStatusCode(),
            sprintf('Application should handle SQL injection attempt: %s', $payload),
        );

        // Vérifier qu'aucune erreur SQL n'est exposée dans la réponse
        $content = $response->getContent();
        $this->assertIsString($content);
        $this->assertStringNotContainsStringIgnoringCase(
            'SQL',
            $content,
            'SQL errors should not be exposed to users',
        );
        $this->assertStringNotContainsStringIgnoringCase(
            'syntax error',
            $content,
            'SQL syntax errors should not be exposed to users',
        );
    }

    /**
     * Payloads courants d'injection SQL.
     */
    public static function sqlInjectionPayloadsProvider(): array
    {
        return [
            'simple quote'    => ["' OR '1'='1"],
            'union select'    => ["' UNION SELECT NULL--"],
            'comment'         => ["admin'--"],
            'stacked queries' => ["'; DROP TABLE users--"],
            'boolean based'   => ["1' AND '1'='1"],
            'time based'      => ["' OR SLEEP(5)--"],
        ];
    }

    public function testRepositoryMethodsUsePreparedStatements(): void
    {
        $container = static::getContainer();

        // Vérifier que les repositories utilisent le QueryBuilder ou des requêtes préparées
        $projectRepository = $container->get('doctrine')->getRepository(\App\Entity\Project::class);

        // Tester avec une entrée malveillante
        $maliciousInput = "' OR '1'='1";
        $projects       = $projectRepository->findBy(['name' => $maliciousInput]);

        // Devrait retourner un tableau vide (aucun projet avec ce nom exact)
        $this->assertIsArray($projects);
        $this->assertCount(
            0,
            $projects,
            'Query should not be vulnerable to SQL injection',
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('xssPayloadsProvider')]
    public function testUserInputIsEscapedInOutput(string $payload): void
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get('doctrine')->getRepository(User::class);
        $testUser       = $userRepository->findOneBy(['email' => 'test@example.com']);

        if (!$testUser) {
            $this->markTestSkipped('Test user not found');

            return;
        }

        $client->loginUser($testUser);

        // Soumettre le payload dans un champ de recherche
        $client->request('GET', '/projects', ['search' => $payload]);

        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);

        // Le payload ne devrait pas être exécuté (script tags doivent être échappés)
        $this->assertStringNotContainsString(
            '<script>',
            $content,
            'Script tags should be escaped in output',
        );
    }

    /**
     * Payloads XSS courants pour tester l'échappement.
     */
    public static function xssPayloadsProvider(): array
    {
        return [
            'basic script'  => ['<script>alert(1)</script>'],
            'img onerror'   => ['<img src=x onerror=alert(1)>'],
            'event handler' => ['<div onload=alert(1)>'],
        ];
    }
}
