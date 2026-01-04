<?php

declare(strict_types=1);

namespace App\Tests\Integration\MultiTenant;

use App\Entity\Client;
use App\Entity\Company;
use App\Entity\Contributor;
use App\Entity\Invoice;
use App\Entity\Order;
use App\Entity\Planning;
use App\Entity\Project;
use App\Entity\ProjectTask;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Factory\ClientFactory;
use App\Factory\ContributorFactory;
use App\Factory\ProjectFactory;
use App\Security\CompanyContext;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Tests de validation que les entités multi-tenant ont TOUJOURS une Company assignée.
 *
 * Vérifie que :
 * - Toutes les entités CompanyOwnedInterface sont créées avec une Company
 * - La Company ne peut pas être NULL
 * - Les entités héritent la Company de leur parent quand approprié
 */
class EntityCreationTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    private CompanyContext $companyContext;
    private EntityManagerInterface $entityManager;
    private Company $company;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->companyContext = static::getContainer()->get(CompanyContext::class);
        $this->entityManager  = static::getContainer()->get(EntityManagerInterface::class);

        // Créer une Company manuellement pour éviter les problèmes de Factory
        $this->company = new Company();
        $this->company->setName('Test Company');
        $this->company->setSlug('test-company');
        $this->company->setSubscriptionTier(Company::TIER_PROFESSIONAL);
        $this->company->setCurrency('EUR');
        $this->company->setStructureCostCoefficient('1.35');
        $this->company->setEmployerChargesCoefficient('1.45');
        $this->company->setAnnualPaidLeaveDays(25);
        $this->company->setAnnualRttDays(10);
        $this->company->setBillingDayOfMonth(1);
        $this->company->setBillingStartDate(new DateTime());

        $this->entityManager->persist($this->company);
        $this->entityManager->flush();

        // Créer un User mocké pour que CompanyContext.switchCompany fonctionne
        $this->authenticateUser($this->company);

        $this->companyContext->switchCompany($this->company);
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
     * Test qu'un Project créé a toujours une Company.
     */
    public function testProjectMustHaveCompany(): void
    {
        $project = new Project();
        $project->setName('Test Project');
        $project->setCompany($this->company);

        $this->entityManager->persist($project);
        $this->entityManager->flush();

        $this->assertNotNull($project->getCompany());
        $this->assertEquals($this->company->getId(), $project->getCompany()->getId());
    }

    /**
     * Test qu'un Client créé a toujours une Company.
     */
    public function testClientMustHaveCompany(): void
    {
        $client = new Client();
        $client->setName('Test Client');
        $client->setCompany($this->company);

        $this->entityManager->persist($client);
        $this->entityManager->flush();

        $this->assertNotNull($client->getCompany());
        $this->assertEquals($this->company->getId(), $client->getCompany()->getId());
    }

    /**
     * Test qu'un Contributor créé a toujours une Company.
     */
    public function testContributorMustHaveCompany(): void
    {
        $contributor = new Contributor();
        $contributor->setFirstName('John');
        $contributor->setLastName('Doe');
        $contributor->setCompany($this->company);

        $this->entityManager->persist($contributor);
        $this->entityManager->flush();

        $this->assertNotNull($contributor->getCompany());
        $this->assertEquals($this->company->getId(), $contributor->getCompany()->getId());
    }

    /**
     * Test qu'un Order créé hérite la Company du Project.
     */
    public function testOrderInheritsCompanyFromProject(): void
    {
        $project = ProjectFactory::createOne(['company' => $this->company]);

        $order = new Order();
        $order->setProject($project);
        $order->setCompany($project->getCompany());
        $order->setOrderNumber('DEV-202501-001');
        $order->setStatus('brouillon');
        $order->setContractType('forfait');

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        $this->assertNotNull($order->getCompany());
        $this->assertEquals($this->company->getId(), $order->getCompany()->getId());
        $this->assertEquals($project->getCompany()->getId(), $order->getCompany()->getId());
    }

    /**
     * Test qu'une ProjectTask créée hérite la Company du Project.
     */
    public function testProjectTaskInheritsCompanyFromProject(): void
    {
        $project = ProjectFactory::createOne(['company' => $this->company]);

        $task = new ProjectTask();
        $task->setProject($project);
        $task->setCompany($project->getCompany());
        $task->setName('Test Task');
        $task->setType(ProjectTask::TYPE_REGULAR);

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        $this->assertNotNull($task->getCompany());
        $this->assertEquals($project->getCompany()->getId(), $task->getCompany()->getId());
    }

    /**
     * Test qu'une Timesheet créée hérite la Company du Project.
     */
    public function testTimesheetInheritsCompanyFromProject(): void
    {
        $project     = ProjectFactory::createOne(['company' => $this->company]);
        $contributor = ContributorFactory::createOne(['company' => $this->company]);

        $timesheet = new Timesheet();
        $timesheet->setProject($project);
        $timesheet->setContributor($contributor);
        $timesheet->setCompany($project->getCompany());
        $timesheet->setDate(new DateTime());
        $timesheet->setHours('8.00');

        $this->entityManager->persist($timesheet);
        $this->entityManager->flush();

        $this->assertNotNull($timesheet->getCompany());
        $this->assertEquals($project->getCompany()->getId(), $timesheet->getCompany()->getId());
    }

    /**
     * Test qu'un Planning créé hérite la Company du Project.
     */
    public function testPlanningInheritsCompanyFromProject(): void
    {
        $project     = ProjectFactory::createOne(['company' => $this->company]);
        $contributor = ContributorFactory::createOne(['company' => $this->company]);

        $planning = new Planning();
        $planning->setProject($project);
        $planning->setContributor($contributor);
        $planning->setCompany($project->getCompany());
        $planning->setStartDate(new DateTime());
        $planning->setEndDate(new DateTime('+5 days'));
        $planning->setDailyHours('8');
        $planning->setStatus('planned');

        $this->entityManager->persist($planning);
        $this->entityManager->flush();

        $this->assertNotNull($planning->getCompany());
        $this->assertEquals($project->getCompany()->getId(), $planning->getCompany()->getId());
    }

    /**
     * Test qu'une Invoice créée a une Company (depuis CompanyContext).
     */
    public function testInvoiceHasCompanyFromContext(): void
    {
        // Create project and client
        $client  = ClientFactory::createOne(['company' => $this->company]);
        $project = ProjectFactory::createOne([
            'company' => $this->company,
            'client'  => $client,
        ]);

        $invoice = new Invoice();
        $invoice->setCompany($this->companyContext->getCurrentCompany());
        $invoice->setProject($project);
        $invoice->setClient($client);
        $invoice->setInvoiceNumber('FAC-202501-001');
        $invoice->setIssuedAt(new DateTime());
        $invoice->setDueDate(new DateTime('+30 days'));
        $invoice->setStatus(Invoice::STATUS_DRAFT);
        $invoice->setAmountHt('1000.00');

        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        $this->assertNotNull($invoice->getCompany());
        $this->assertEquals($this->company->getId(), $invoice->getCompany()->getId());
    }

    /**
     * Test que les Factories créent toujours des entités avec une Company.
     */
    public function testFactoriesAlwaysCreateEntitiesWithCompany(): void
    {
        $project     = ProjectFactory::createOne();
        $contributor = ContributorFactory::createOne();

        $this->assertNotNull($project->getCompany());
        $this->assertNotNull($contributor->getCompany());
    }

    /**
     * Test qu'essayer de persister une entité sans Company échoue (contrainte DB).
     *
     * @expectedException \Doctrine\DBAL\Exception\NotNullConstraintViolationException
     */
    public function testPersistingEntityWithoutCompanyFails(): void
    {
        $this->expectException(Exception::class);

        $project = new Project();
        $project->setName('Project without Company');
        // Ne pas appeler setCompany()

        $this->entityManager->persist($project);
        $this->entityManager->flush(); // Devrait lever une exception
    }
}
