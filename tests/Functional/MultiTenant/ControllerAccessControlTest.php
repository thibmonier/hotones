<?php

declare(strict_types=1);

namespace App\Tests\Functional\MultiTenant;

use App\Entity\Company;
use App\Entity\Contributor;
use App\Entity\User;
use App\Factory\ClientFactory;
use App\Factory\ProjectFactory;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Tests de contrôle d'accès multi-tenant au niveau des controllers.
 *
 * Vérifie que :
 * - Un utilisateur de Company A ne peut PAS accéder aux données de Company B
 * - Les routes respectent l'isolation multi-tenant
 * - Les tentatives d'accès cross-company retournent 404 ou 403
 */
class ControllerAccessControlTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Test qu'un utilisateur ne peut pas voir les projets d'une autre Company.
     */
    public function testUserCannotAccessOtherCompanyProjects(): void
    {
        // Créer 2 Companies avec utilisateurs
        ['company' => $company1, 'user' => $user1] = $this->createCompanyWithUser('Company Alpha', 'user1@alpha.com');
        ['company' => $company2]                   = $this->createCompanyWithUser('Company Beta', 'user2@beta.com');

        // Créer des projets pour chaque Company
        $project1 = ProjectFactory::createOne([
            'name'    => 'Project Alpha',
            'company' => $company1,
        ]);
        $project2 = ProjectFactory::createOne([
            'name'    => 'Project Beta',
            'company' => $company2,
        ]);

        // Login en tant qu'utilisateur de Company 1
        $this->loginAs($user1);

        // Accès au projet de Company 1 : OK
        $this->client->request('GET', '/projects/'.$project1->getId());
        $this->assertResponseIsSuccessful();

        // Accès au projet de Company 2 : REFUSÉ (404 ou 403)
        $this->client->request('GET', '/projects/'.$project2->getId());
        $this->assertResponseStatusCodeSame(404); // Ou 403 selon l'implémentation
    }

    /**
     * Test qu'un utilisateur ne peut pas voir les clients d'une autre Company.
     */
    public function testUserCannotAccessOtherCompanyClients(): void
    {
        // Créer 2 Companies avec utilisateurs
        ['company' => $company1, 'user' => $user1] = $this->createCompanyWithUser('Company Alpha', 'user1@alpha.com');
        ['company' => $company2]                   = $this->createCompanyWithUser('Company Beta', 'user2@beta.com');

        // Créer des clients pour chaque Company
        $client1 = ClientFactory::createOne([
            'name'    => 'Client Alpha',
            'company' => $company1,
        ]);
        $client2 = ClientFactory::createOne([
            'name'    => 'Client Beta',
            'company' => $company2,
        ]);

        // Login en tant qu'utilisateur de Company 1
        $this->loginAs($user1);

        // Accès au client de Company 1 : OK
        $this->client->request('GET', '/clients/'.$client1->getId());
        $this->assertResponseIsSuccessful();

        // Accès au client de Company 2 : REFUSÉ
        $this->client->request('GET', '/clients/'.$client2->getId());
        $this->assertResponseStatusCodeSame(404);
    }

    /**
     * Test que la liste des projets ne montre QUE les projets de la Company de l'utilisateur.
     */
    public function testProjectListOnlyShowsCurrentCompanyProjects(): void
    {
        // Créer 2 Companies avec utilisateurs
        ['company' => $company1, 'user' => $user1] = $this->createCompanyWithUser('Company Alpha', 'user1@alpha.com');
        ['company' => $company2]                   = $this->createCompanyWithUser('Company Beta', 'user2@beta.com');

        // Créer des projets pour chaque Company
        ProjectFactory::createMany(3, [
            'company' => $company1,
            'name'    => 'Project Alpha',
            'status'  => 'active',
        ]);
        ProjectFactory::createMany(2, [
            'company' => $company2,
            'name'    => 'Project Beta',
            'status'  => 'active',
        ]);

        // Login en tant qu'utilisateur de Company 1
        $this->loginAs($user1);

        // Accès à la liste des projets
        $crawler = $this->client->request('GET', '/projects');
        $this->assertResponseIsSuccessful();

        // Vérifier que seuls les projets de Company 1 sont affichés
        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Project Alpha', $content);
        $this->assertStringNotContainsString('Project Beta', $content);
    }

    /**
     * Test que la création d'un projet assigne automatiquement la Company de l'utilisateur.
     */
    public function testProjectCreationAssignsCurrentUserCompany(): void
    {
        // Créer Company avec utilisateur
        ['company' => $company, 'user' => $user] = $this->createCompanyWithUser('Company Test', 'user@test.com', ['ROLE_CHEF_PROJET']);

        $this->loginAs($user);

        // Accès au formulaire de création
        $crawler = $this->client->request('GET', '/projects/new');
        $this->assertResponseIsSuccessful();

        // Soumettre le formulaire (simplifié - ajustez selon vos champs réels)
        $form = $crawler->selectButton('Créer')->form([
            'project[name]'        => 'New Project',
            'project[projectType]' => 'forfait',
            'project[status]'      => 'active',
        ]);

        $this->client->submit($form);

        // Vérifier que le projet a bien été créé avec la Company de l'utilisateur
        $project = static::getContainer()
            ->get('doctrine')
            ->getRepository(\App\Entity\Project::class)
            ->findOneBy(['name' => 'New Project']);

        $this->assertNotNull($project);
        $this->assertEquals($company->getId(), $project->getCompany()->getId());
    }

    /**
     * Test que 2 utilisateurs de Companies différentes voient des données différentes.
     */
    public function testDifferentUsersSeeDifferentCompanyData(): void
    {
        // Créer 2 Companies avec utilisateurs
        ['company' => $company1, 'user' => $user1] = $this->createCompanyWithUser('Company Alpha', 'user1@alpha.com');
        ['company' => $company2, 'user' => $user2] = $this->createCompanyWithUser('Company Beta', 'user2@beta.com');

        // Créer des projets
        ProjectFactory::createMany(5, ['company' => $company1, 'status' => 'active']);
        ProjectFactory::createMany(3, ['company' => $company2, 'status' => 'active']);

        // User 1 voit 5 projets
        $this->loginAs($user1);
        $this->client->request('GET', '/projects');
        $content1 = $this->client->getResponse()->getContent();

        // User 2 voit 3 projets
        $this->logout();
        $this->loginAs($user2);
        $this->client->request('GET', '/projects');
        $content2 = $this->client->getResponse()->getContent();

        // Les contenus doivent être différents
        $this->assertNotEquals($content1, $content2);
    }

    /**
     * Helper pour créer une Company + Contributor + User.
     */
    private function createCompanyWithUser(string $companyName, string $email, array $roles = ['ROLE_USER', 'ROLE_INTERVENANT']): array
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        // Créer Company
        $company = new Company();
        $company->setName($companyName);
        $company->setSlug(strtolower(str_replace(' ', '-', $companyName)));
        $company->setSubscriptionTier(Company::TIER_PROFESSIONAL);
        $company->setCurrency('EUR');
        $company->setStructureCostCoefficient('1.35');
        $company->setEmployerChargesCoefficient('1.45');
        $company->setAnnualPaidLeaveDays(25);
        $company->setAnnualRttDays(10);
        $company->setBillingDayOfMonth(1);
        $company->setBillingStartDate(new DateTime());
        $em->persist($company);
        $em->flush();

        // Créer Contributor manuellement
        $contributor = new Contributor();
        $contributor->setCompany($company);
        $contributor->setFirstName('Test');
        $contributor->setLastName('Contributor');
        $contributor->setActive(true);
        $em->persist($contributor);
        $em->flush();

        // Créer User
        $user = new User();
        $user->setCompany($company);
        $user->setEmail($email);
        $user->setPassword($hasher->hashPassword($user, 'password'));
        $user->firstName = 'Test';
        $user->lastName  = 'User';
        $user->setRoles($roles);
        $em->persist($user);

        // Lier Contributor et User
        $contributor->setUser($user);
        $em->persist($contributor);
        $em->flush();

        return ['company' => $company, 'contributor' => $contributor, 'user' => $user];
    }

    /**
     * Helper pour logger un utilisateur.
     */
    private function loginAs(User $user): void
    {
        $this->client->loginUser($user);
    }

    /**
     * Helper pour délogger.
     */
    private function logout(): void
    {
        $this->client->request('GET', '/logout');
    }
}
