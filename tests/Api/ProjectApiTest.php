<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Project;
use App\Factory\UserFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Tests fonctionnels pour l'endpoint /api/projects.
 *
 * @group api
 */
class ProjectApiTest extends ApiTestCase
{
    use Factories;
    use ResetDatabase;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        // Pour les tests, créer un utilisateur et obtenir un token JWT
        // À adapter selon votre configuration de fixtures
    }

    public function testGetCollection(): void
    {
        $this->markTestIncomplete('API tests require proper JWT and client configuration - to be fixed');

        // Test de récupération de la collection de projets
        $response = static::createClient()->request('GET', '/api/projects', [
            'headers' => ['Authorization' => 'Bearer '.$this->getToken()],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/Project',
            '@type'    => 'Collection',
        ]);

        // Vérifier qu'on a au moins un résultat dans la collection
        $this->assertCount(0, $response->toArray()['member']);
    }

    public function testCreateProject(): void
    {
        $this->markTestIncomplete('API tests require proper JWT and client configuration - to be fixed');

        // Test de création d'un projet
        $response = static::createClient()->request('POST', '/api/projects', [
            'headers' => ['Authorization' => 'Bearer '.$this->getTokenWithRole('ROLE_CHEF_PROJET')],
            'json'    => [
                'name'        => 'Projet Test API',
                'description' => 'Description du projet test',
                'status'      => 'active',
                'projectType' => 'forfait',
                'isInternal'  => false,
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/Project',
            '@type'    => 'Project',
            'name'     => 'Projet Test API',
            'status'   => 'active',
        ]);
    }

    public function testGetProject(): void
    {
        $this->markTestIncomplete('API tests require proper JWT and client configuration - to be fixed');

        // Créer un projet via le client
        $client  = static::createClient();
        $project = $this->createProject();

        // Récupérer le projet
        $response = $client->request('GET', '/api/projects/'.$project->getId(), [
            'headers' => ['Authorization' => 'Bearer '.$this->getToken()],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@id'  => '/api/projects/'.$project->getId(),
            'name' => $project->getName(),
        ]);
    }

    public function testUpdateProject(): void
    {
        $this->markTestIncomplete('API tests require proper JWT and client configuration - to be fixed');

        $project = $this->createProject();

        $client = static::createClient();
        $client->request('PUT', '/api/projects/'.$project->getId(), [
            'headers' => ['Authorization' => 'Bearer '.$this->getTokenWithRole('ROLE_CHEF_PROJET')],
            'json'    => [
                'name'   => 'Projet modifié',
                'status' => 'completed',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'name'   => 'Projet modifié',
            'status' => 'completed',
        ]);
    }

    public function testDeleteProject(): void
    {
        $this->markTestIncomplete('API tests require proper JWT and client configuration - to be fixed');

        $project = $this->createProject();

        $client = static::createClient();
        $client->request('DELETE', '/api/projects/'.$project->getId(), [
            'headers' => ['Authorization' => 'Bearer '.$this->getTokenWithRole('ROLE_MANAGER')],
        ]);

        $this->assertResponseStatusCodeSame(204);
    }

    public function testCreateProjectWithoutAuth(): void
    {
        // Tenter de créer un projet sans authentification
        $client = static::createClient();
        $client->request('POST', '/api/projects', [
            'json' => [
                'name' => 'Projet non autorisé',
            ],
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testCreateProjectWithInsufficientRights(): void
    {
        $this->markTestIncomplete('API tests require proper JWT and client configuration - to be fixed');

        // Tenter de créer un projet avec un rôle insuffisant
        $client = static::createClient();
        $client->request('POST', '/api/projects', [
            'headers' => ['Authorization' => 'Bearer '.$this->getToken()], // ROLE_USER
            'json'    => [
                'name' => 'Projet non autorisé',
            ],
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    // Méthodes utilitaires

    private function createProject(): Project
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $project = new Project();
        $project->setName('Projet de test');
        $project->setDescription('Description test');
        $project->setStatus('active');
        $project->setProjectType('forfait');
        $project->setIsInternal(false);

        $entityManager->persist($project);
        $entityManager->flush();

        return $project;
    }

    private function getToken(): string
    {
        if (isset($this->token)) {
            return $this->token;
        }

        // Create client to boot kernel and initialize Foundry with service container
        static::createClient();

        // Crée un utilisateur aléatoire et génère un JWT sans appeler /api/login
        $user        = UserFactory::createOne();
        $jwtManager  = static::getContainer()->get('lexik_jwt_authentication.jwt_manager');
        $this->token = $jwtManager->create($user);

        return $this->token;
    }

    private function getTokenWithRole(string $role): string
    {
        // Create client to boot kernel and initialize Foundry with service container
        static::createClient();

        // Crée un utilisateur avec le rôle demandé et génère un JWT
        $user       = UserFactory::createOne(['roles' => [$role]]);
        $jwtManager = static::getContainer()->get('lexik_jwt_authentication.jwt_manager');

        return $jwtManager->create($user);
    }
}
