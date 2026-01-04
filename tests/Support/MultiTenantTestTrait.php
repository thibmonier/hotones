<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Entity\Company;
use App\Entity\User;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Trait pour les tests nécessitant un contexte multi-tenant.
 *
 * Fournit les méthodes helpers pour :
 * - Créer une Company
 * - Authentifier un User avec cette Company
 * - Fournir un CompanyContext valide pour les tests
 */
trait MultiTenantTestTrait
{
    private ?Company $testCompany = null;
    private ?User $testUser       = null;

    /**
     * Setup multi-tenant : crée une Company et authentifie un User.
     * À appeler dans setUp() des tests nécessitant CompanyContext.
     */
    protected function setUpMultiTenant(): void
    {
        $this->testCompany = $this->createTestCompany();
        $this->testUser    = $this->authenticateTestUser($this->testCompany);
    }

    /**
     * Crée une Company de test avec des valeurs par défaut valides.
     */
    protected function createTestCompany(string $name = 'Test Company'): Company
    {
        $company = new Company();
        $company->setName($name);
        $company->setSlug(strtolower(str_replace(' ', '-', $name)));
        $company->setSubscriptionTier(Company::TIER_PROFESSIONAL);
        $company->setCurrency('EUR');
        $company->setStructureCostCoefficient('1.35');
        $company->setEmployerChargesCoefficient('1.45');
        $company->setAnnualPaidLeaveDays(25);
        $company->setAnnualRttDays(10);
        $company->setBillingDayOfMonth(1);
        $company->setBillingStartDate(new DateTime());

        $em = $this->getEntityManager();
        $em->persist($company);
        $em->flush();

        return $company;
    }

    /**
     * Authentifie un User mocké pour fournir CompanyContext.
     */
    protected function authenticateTestUser(Company $company, array $roles = ['ROLE_USER']): User
    {
        $user = new User();
        $user->setCompany($company);
        $user->setEmail('test@test.com');
        $user->setPassword('password');
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setRoles($roles);

        $em = $this->getEntityManager();
        $em->persist($user);
        $em->flush();

        $token        = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $tokenStorage = $this->getTokenStorage();
        $tokenStorage->setToken($token);

        return $user;
    }

    /**
     * Récupère l'EntityManager depuis le container.
     */
    protected function getEntityManager(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    /**
     * Récupère le TokenStorage depuis le container.
     */
    protected function getTokenStorage(): TokenStorageInterface
    {
        return static::getContainer()->get(TokenStorageInterface::class);
    }

    /**
     * Getter pour accéder à la Company de test.
     */
    protected function getTestCompany(): Company
    {
        if ($this->testCompany === null) {
            throw new RuntimeException('Vous devez appeler setUpMultiTenant() dans setUp() avant d\'utiliser getTestCompany()');
        }

        return $this->testCompany;
    }

    /**
     * Getter pour accéder au User de test.
     */
    protected function getTestUser(): User
    {
        if ($this->testUser === null) {
            throw new RuntimeException('Vous devez appeler setUpMultiTenant() dans setUp() avant d\'utiliser getTestUser()');
        }

        return $this->testUser;
    }
}
