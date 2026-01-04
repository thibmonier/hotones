<?php

declare(strict_types=1);

namespace App\Tests\Integration\MultiTenant;

use App\Entity\Company;
use App\Entity\User;
use App\Factory\ClientFactory;
use App\Factory\ContributorFactory;
use App\Factory\ProjectFactory;
use App\Factory\TimesheetFactory;
use App\Repository\ClientRepository;
use App\Repository\ContributorRepository;
use App\Repository\ProjectRepository;
use App\Repository\TimesheetRepository;
use App\Security\CompanyContext;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Tests de l'isolation multi-tenant entre Companies.
 *
 * Vérifie que :
 * - Les données d'une Company ne sont jamais visibles par une autre Company
 * - Les repositories filtrent automatiquement par Company via CompanyAwareRepositoryTrait
 * - Le CompanyContext fonctionne correctement
 */
class CompanyIsolationTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    private CompanyContext $companyContext;
    private EntityManagerInterface $entityManager;
    private Company $company1;
    private Company $company2;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->companyContext = static::getContainer()->get(CompanyContext::class);
        $this->entityManager  = static::getContainer()->get(EntityManagerInterface::class);

        // Créer 2 Companies manuellement pour éviter les problèmes de Factory
        $this->company1 = new Company();
        $this->company1->setName('Company Alpha');
        $this->company1->setSlug('company-alpha');
        $this->company1->setSubscriptionTier(Company::TIER_PROFESSIONAL);
        $this->company1->setCurrency('EUR');
        $this->company1->setStructureCostCoefficient('1.35');
        $this->company1->setEmployerChargesCoefficient('1.45');
        $this->company1->setAnnualPaidLeaveDays(25);
        $this->company1->setAnnualRttDays(10);
        $this->company1->setBillingDayOfMonth(1);
        $this->company1->setBillingStartDate(new DateTime());

        $this->company2 = new Company();
        $this->company2->setName('Company Beta');
        $this->company2->setSlug('company-beta');
        $this->company2->setSubscriptionTier(Company::TIER_PROFESSIONAL);
        $this->company2->setCurrency('EUR');
        $this->company2->setStructureCostCoefficient('1.35');
        $this->company2->setEmployerChargesCoefficient('1.45');
        $this->company2->setAnnualPaidLeaveDays(25);
        $this->company2->setAnnualRttDays(10);
        $this->company2->setBillingDayOfMonth(1);
        $this->company2->setBillingStartDate(new DateTime());

        $this->entityManager->persist($this->company1);
        $this->entityManager->persist($this->company2);
        $this->entityManager->flush();

        // Créer un User mocké pour que CompanyContext.switchCompany fonctionne
        $this->authenticateUser($this->company1);
    }

    /**
     * Helper pour authentifier un utilisateur mocké (pour switchCompany).
     */
    private function authenticateUser(Company $company): void
    {
        $user = new User();
        $user->setCompany($company);
        $user->setEmail('test@test.com');
        $user->setPassword('password');
        $user->firstName = 'Test';
        $user->lastName  = 'User';
        $user->setRoles(['ROLE_USER', 'ROLE_SUPERADMIN']); // SUPERADMIN required for switchCompany()

        // Persist user so it can be used as ProjectEvent.actor
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $token        = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $tokenStorage = static::getContainer()->get(TokenStorageInterface::class);
        $tokenStorage->setToken($token);
    }

    /**
     * Test que les Projects d'une Company ne sont pas visibles par une autre.
     */
    public function testProjectsAreIsolatedBetweenCompanies(): void
    {
        /** @var ProjectRepository $projectRepo */
        $projectRepo = static::getContainer()->get(ProjectRepository::class);

        // Créer des projets pour chaque Company
        ProjectFactory::createOne([
            'name'    => 'Project Alpha 1',
            'company' => $this->company1,
        ]);
        ProjectFactory::createOne([
            'name'    => 'Project Alpha 2',
            'company' => $this->company1,
        ]);
        ProjectFactory::createOne([
            'name'    => 'Project Beta 1',
            'company' => $this->company2,
        ]);

        // Simuler le context de Company 1
        $this->companyContext->switchCompany($this->company1);

        $projects = $projectRepo->findAllForCurrentCompany();

        // Company 1 ne doit voir QUE ses 2 projets
        $this->assertCount(2, $projects);
        foreach ($projects as $project) {
            $this->assertEquals($this->company1->getId(), $project->getCompany()->getId());
        }

        // Simuler le context de Company 2
        $this->companyContext->switchCompany($this->company2);

        $projects = $projectRepo->findAllForCurrentCompany();

        // Company 2 ne doit voir QUE son projet
        $this->assertCount(1, $projects);
        $this->assertEquals('Project Beta 1', $projects[0]->getName());
        $this->assertEquals($this->company2->getId(), $projects[0]->getCompany()->getId());
    }

    /**
     * Test que les Clients sont isolés par Company.
     */
    public function testClientsAreIsolatedBetweenCompanies(): void
    {
        /** @var ClientRepository $clientRepo */
        $clientRepo = static::getContainer()->get(ClientRepository::class);

        // Créer des clients pour chaque Company
        ClientFactory::createOne([
            'name'    => 'Client Alpha',
            'company' => $this->company1,
        ]);
        ClientFactory::createOne([
            'name'    => 'Client Beta',
            'company' => $this->company2,
        ]);

        // Context Company 1
        $this->companyContext->switchCompany($this->company1);
        $clients = $clientRepo->findAllForCurrentCompany();

        $this->assertCount(1, $clients);
        $this->assertEquals('Client Alpha', $clients[0]->getName());

        // Context Company 2
        $this->companyContext->switchCompany($this->company2);
        $clients = $clientRepo->findAllForCurrentCompany();

        $this->assertCount(1, $clients);
        $this->assertEquals('Client Beta', $clients[0]->getName());
    }

    /**
     * Test que les Contributors sont isolés par Company.
     */
    public function testContributorsAreIsolatedBetweenCompanies(): void
    {
        /** @var ContributorRepository $contributorRepo */
        $contributorRepo = static::getContainer()->get(ContributorRepository::class);

        // Créer des contributeurs pour chaque Company
        ContributorFactory::createOne([
            'firstName' => 'Alice',
            'lastName'  => 'Alpha',
            'company'   => $this->company1,
        ]);
        ContributorFactory::createOne([
            'firstName' => 'Bob',
            'lastName'  => 'Beta',
            'company'   => $this->company2,
        ]);

        // Context Company 1
        $this->companyContext->switchCompany($this->company1);
        $contributors = $contributorRepo->findAllForCurrentCompany();

        $this->assertCount(1, $contributors);
        $this->assertEquals('Alice', $contributors[0]->getFirstName());

        // Context Company 2
        $this->companyContext->switchCompany($this->company2);
        $contributors = $contributorRepo->findAllForCurrentCompany();

        $this->assertCount(1, $contributors);
        $this->assertEquals('Bob', $contributors[0]->getFirstName());
    }

    /**
     * Test que les Timesheets sont isolés par Company.
     */
    public function testTimesheetsAreIsolatedBetweenCompanies(): void
    {
        /** @var TimesheetRepository $timesheetRepo */
        $timesheetRepo = static::getContainer()->get(TimesheetRepository::class);

        // Créer des timesheets pour chaque Company
        $project1 = ProjectFactory::createOne(['company' => $this->company1]);
        $project2 = ProjectFactory::createOne(['company' => $this->company2]);

        $contributor1 = ContributorFactory::createOne(['company' => $this->company1]);
        $contributor2 = ContributorFactory::createOne(['company' => $this->company2]);

        TimesheetFactory::createMany(3, [
            'company'     => $this->company1,
            'project'     => $project1,
            'contributor' => $contributor1,
        ]);

        TimesheetFactory::createMany(2, [
            'company'     => $this->company2,
            'project'     => $project2,
            'contributor' => $contributor2,
        ]);

        // Context Company 1
        $this->companyContext->switchCompany($this->company1);
        $timesheets = $timesheetRepo->findAllForCurrentCompany();

        $this->assertCount(3, $timesheets);
        foreach ($timesheets as $timesheet) {
            $this->assertEquals($this->company1->getId(), $timesheet->getCompany()->getId());
        }

        // Context Company 2
        $this->companyContext->switchCompany($this->company2);
        $timesheets = $timesheetRepo->findAllForCurrentCompany();

        $this->assertCount(2, $timesheets);
        foreach ($timesheets as $timesheet) {
            $this->assertEquals($this->company2->getId(), $timesheet->getCompany()->getId());
        }
    }

    /**
     * Test que findById ne retourne PAS une entité d'une autre Company.
     */
    public function testFindByIdRespectsCompanyIsolation(): void
    {
        /** @var ProjectRepository $projectRepo */
        $projectRepo = static::getContainer()->get(ProjectRepository::class);

        // Créer un projet pour Company 2
        $project2 = ProjectFactory::createOne([
            'name'    => 'Project Beta Secret',
            'company' => $this->company2,
        ]);

        // Context Company 1 essaie d'accéder au projet de Company 2
        $this->companyContext->switchCompany($this->company1);

        $result = $projectRepo->findOneByIdForCompany($project2->getId());

        // Company 1 ne doit PAS voir le projet de Company 2
        $this->assertNull($result);
    }

    /**
     * Test que les méthodes de recherche respectent l'isolation.
     */
    public function testSearchRespectsCompanyIsolation(): void
    {
        /** @var ProjectRepository $projectRepo */
        $projectRepo = static::getContainer()->get(ProjectRepository::class);

        // Créer des projets avec des noms similaires
        ProjectFactory::createOne([
            'name'    => 'E-commerce Platform',
            'company' => $this->company1,
        ]);
        ProjectFactory::createOne([
            'name'    => 'E-commerce Shop',
            'company' => $this->company2,
        ]);

        // Context Company 1
        $this->companyContext->switchCompany($this->company1);
        $results = $projectRepo->searchProjects('e-commerce');

        $this->assertCount(1, $results);
        $this->assertEquals('E-commerce Platform', $results[0]->getName());

        // Context Company 2
        $this->companyContext->switchCompany($this->company2);
        $results = $projectRepo->searchProjects('e-commerce');

        $this->assertCount(1, $results);
        $this->assertEquals('E-commerce Shop', $results[0]->getName());
    }

    /**
     * Test que count() respecte l'isolation.
     */
    public function testCountRespectsCompanyIsolation(): void
    {
        /** @var ProjectRepository $projectRepo */
        $projectRepo = static::getContainer()->get(ProjectRepository::class);

        // Créer 5 projets pour Company 1, 3 pour Company 2
        ProjectFactory::createMany(5, ['company' => $this->company1]);
        ProjectFactory::createMany(3, ['company' => $this->company2]);

        // Context Company 1
        $this->companyContext->switchCompany($this->company1);
        $count = $projectRepo->countForCurrentCompany();

        $this->assertEquals(5, $count);

        // Context Company 2
        $this->companyContext->switchCompany($this->company2);
        $count = $projectRepo->countForCurrentCompany();

        $this->assertEquals(3, $count);
    }

    /**
     * Test que les statistiques/métriques sont isolées par Company.
     */
    public function testMetricsAreIsolatedBetweenCompanies(): void
    {
        /** @var ProjectRepository $projectRepo */
        $projectRepo = static::getContainer()->get(ProjectRepository::class);

        // Créer des projets actifs pour chaque Company
        ProjectFactory::createMany(4, [
            'company' => $this->company1,
            'status'  => 'active',
        ]);
        ProjectFactory::createMany(2, [
            'company' => $this->company2,
            'status'  => 'active',
        ]);

        // Context Company 1
        $this->companyContext->switchCompany($this->company1);
        $activeCount = $projectRepo->countActiveProjects();

        $this->assertEquals(4, $activeCount);

        // Context Company 2
        $this->companyContext->switchCompany($this->company2);
        $activeCount = $projectRepo->countActiveProjects();

        $this->assertEquals(2, $activeCount);
    }

    /**
     * Test que CompanyContext peut changer de Company dynamiquement.
     */
    public function testCompanyContextCanSwitchBetweenCompanies(): void
    {
        /** @var ProjectRepository $projectRepo */
        $projectRepo = static::getContainer()->get(ProjectRepository::class);

        ProjectFactory::createMany(2, ['company' => $this->company1]);
        ProjectFactory::createMany(3, ['company' => $this->company2]);

        // Switch vers Company 1
        $this->companyContext->switchCompany($this->company1);
        $this->assertEquals(2, $projectRepo->countForCurrentCompany());

        // Switch vers Company 2
        $this->companyContext->switchCompany($this->company2);
        $this->assertEquals(3, $projectRepo->countForCurrentCompany());

        // Re-switch vers Company 1
        $this->companyContext->switchCompany($this->company1);
        $this->assertEquals(2, $projectRepo->countForCurrentCompany());
    }
}
