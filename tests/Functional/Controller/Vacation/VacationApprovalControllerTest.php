<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Vacation;

use App\Application\Vacation\Command\RequestVacation\RequestVacationCommand;
use App\Application\Vacation\Command\RequestVacation\RequestVacationHandler;
use App\Domain\Vacation\Entity\Vacation;
use App\Domain\Vacation\Repository\VacationRepositoryInterface;
use App\Domain\Vacation\ValueObject\VacationStatus;
use App\Entity\Contributor;
use App\Entity\User;
use App\Tests\Support\MultiTenantTestTrait;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Functional tests for the VacationApprovalController routes (US-067).
 *
 *   GET  /manager/conges                     -> index of pending vacations for managed contributors
 *   GET  /manager/conges/{id}                -> show, only when the manager actually manages the contributor
 *   POST /manager/conges/{id}/approuver      -> approve a pending request
 *   POST /manager/conges/{id}/rejeter        -> reject a pending request
 *   GET  /manager/conges/api/pending-count   -> JSON badge feed for the header dropdown
 *
 * Each test boots the full Symfony kernel (WebTestCase). Two contributors are
 * provisioned: a ROLE_MANAGER who is the direct manager of the second
 * (ROLE_INTERVENANT) contributor. The intervenant submits a vacation through
 * the real RequestVacationHandler, then the manager exercises approve / reject.
 */
final class VacationApprovalControllerTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;
    use MultiTenantTestTrait;

    private KernelBrowser $client;
    private Contributor $manager;
    private Contributor $employee;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->testCompany = $this->createTestCompany();

        $this->manager = $this->provisionContributor('manager@test.com', 'Manon', 'Manager', ['ROLE_MANAGER']);
        $this->employee = $this->provisionContributor('employee@test.com', 'Adrien', 'Test', ['ROLE_INTERVENANT'], $this->manager);

        // Authenticate as the manager so the controller resolves $this->getUser() to a managing contributor.
        $this->loginAs($this->manager->getUser());
    }

    public function testIndexShowsPendingVacationsOfManagedContributors(): void
    {
        $this->createPendingVacation($this->employee);

        $this->client->request('GET', '/manager/conges');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h4', 'Validation des demandes');
        self::assertSelectorTextContains('table', 'Adrien Test');
    }

    public function testShowIsForbiddenWhenManagerDoesNotManageContributor(): void
    {
        $unrelatedManager = $this->provisionContributor('other@test.com', 'Other', 'Manager', ['ROLE_MANAGER']);
        $unrelatedEmployee = $this->provisionContributor('other-emp@test.com', 'Lone', 'Wolf', ['ROLE_INTERVENANT'], $unrelatedManager);
        $foreignVacation = $this->createPendingVacation($unrelatedEmployee);

        $this->loginAs($this->manager->getUser());

        $this->client->request('GET', '/manager/conges/'.$foreignVacation->getId()->getValue());

        self::assertResponseStatusCodeSame(403);
    }

    public function testApproveTransitionsVacationToApproved(): void
    {
        $vacation = $this->createPendingVacation($this->employee);

        $this->client->request('POST', '/manager/conges/'.$vacation->getId()->getValue().'/approuver', [
            '_token' => $this->generateCsrfToken('approve'.$vacation->getId()->getValue()),
        ]);

        self::assertResponseRedirects('/manager/conges');

        /** @var VacationRepositoryInterface $repo */
        $repo = static::getContainer()->get(VacationRepositoryInterface::class);
        self::assertSame(VacationStatus::APPROVED, $repo->findById($vacation->getId())->getStatus());
    }

    public function testRejectTransitionsVacationToRejected(): void
    {
        $vacation = $this->createPendingVacation($this->employee);

        $this->client->request('POST', '/manager/conges/'.$vacation->getId()->getValue().'/rejeter', [
            '_token' => $this->generateCsrfToken('reject'.$vacation->getId()->getValue()),
        ]);

        self::assertResponseRedirects('/manager/conges');

        /** @var VacationRepositoryInterface $repo */
        $repo = static::getContainer()->get(VacationRepositoryInterface::class);
        self::assertSame(VacationStatus::REJECTED, $repo->findById($vacation->getId())->getStatus());
    }

    public function testRejectPersistsRejectionReasonWhenSupplied(): void
    {
        $vacation = $this->createPendingVacation($this->employee);

        $this->client->request('POST', '/manager/conges/'.$vacation->getId()->getValue().'/rejeter', [
            '_token' => $this->generateCsrfToken('reject'.$vacation->getId()->getValue()),
            'rejection_reason' => 'Planning sature sur la periode',
        ]);

        self::assertResponseRedirects('/manager/conges');

        /** @var VacationRepositoryInterface $repo */
        $repo = static::getContainer()->get(VacationRepositoryInterface::class);
        $rejected = $repo->findById($vacation->getId());
        self::assertSame(VacationStatus::REJECTED, $rejected->getStatus());
        self::assertSame('Planning sature sur la periode', $rejected->getRejectionReason());
    }

    public function testRejectKeepsNullReasonWhenFieldOmitted(): void
    {
        $vacation = $this->createPendingVacation($this->employee);

        $this->client->request('POST', '/manager/conges/'.$vacation->getId()->getValue().'/rejeter', [
            '_token' => $this->generateCsrfToken('reject'.$vacation->getId()->getValue()),
        ]);

        /** @var VacationRepositoryInterface $repo */
        $repo = static::getContainer()->get(VacationRepositoryInterface::class);
        self::assertNull($repo->findById($vacation->getId())->getRejectionReason());
    }

    public function testManagerCancelTransitionsApprovedVacationToCancelled(): void
    {
        // US-069: an approved vacation can be cancelled by the manager.
        $vacation = $this->createPendingVacation($this->employee);

        // Approve first
        $this->client->request('POST', '/manager/conges/'.$vacation->getId()->getValue().'/approuver', [
            '_token' => $this->generateCsrfToken('approve'.$vacation->getId()->getValue()),
        ]);

        // Manager-cancel the approved vacation
        $this->client->request('POST', '/manager/conges/'.$vacation->getId()->getValue().'/annuler', [
            '_token' => $this->generateCsrfToken('cancel-manager'.$vacation->getId()->getValue()),
        ]);

        self::assertResponseRedirects('/manager/conges');

        /** @var VacationRepositoryInterface $repo */
        $repo = static::getContainer()->get(VacationRepositoryInterface::class);
        self::assertSame(VacationStatus::CANCELLED, $repo->findById($vacation->getId())->getStatus());
    }

    public function testManagerCancelIsForbiddenForUnmanagedContributor(): void
    {
        $unrelatedManager = $this->provisionContributor('rogue@test.com', 'Rogue', 'Manager', ['ROLE_MANAGER']);
        $unrelatedEmployee = $this->provisionContributor('rogue-emp@test.com', 'Stranger', 'Wolf', ['ROLE_INTERVENANT'], $unrelatedManager);
        $foreignVacation = $this->createPendingVacation($unrelatedEmployee);

        $this->loginAs($this->manager->getUser());

        $this->client->request('POST', '/manager/conges/'.$foreignVacation->getId()->getValue().'/annuler', [
            '_token' => $this->generateCsrfToken('cancel-manager'.$foreignVacation->getId()->getValue()),
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function testPendingCountApiReturnsJsonForManagedContributors(): void
    {
        $this->createPendingVacation($this->employee);
        $this->createPendingVacation($this->employee);

        $this->client->request('GET', '/manager/conges/api/pending-count');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/json');
        $payload = json_decode($this->client->getResponse()->getContent(), true);
        self::assertSame(2, $payload['count']);
        self::assertCount(2, $payload['vacations']);
    }

    /**
     * @param array<string> $roles
     */
    private function provisionContributor(
        string $email,
        string $firstName,
        string $lastName,
        array $roles,
        ?Contributor $manager = null,
    ): Contributor {
        $em = $this->getEntityManager();

        $user = new User();
        $user->setCompany($this->testCompany);
        $user->setEmail($email);
        $user->setPassword('password');
        $user->firstName = $firstName;
        $user->lastName = $lastName;
        $user->setRoles($roles);
        $em->persist($user);

        $contributor = new Contributor();
        $contributor->setCompany($this->testCompany);
        $contributor->setUser($user);
        $contributor->setFirstName($firstName);
        $contributor->setLastName($lastName);
        $contributor->setActive(true);
        if ($manager !== null) {
            $contributor->setManager($manager);
        }
        $em->persist($contributor);
        $em->flush();

        return $contributor;
    }

    private function createPendingVacation(Contributor $contributor): Vacation
    {
        /** @var RequestVacationHandler $handler */
        $handler = static::getContainer()->get(RequestVacationHandler::class);

        ($handler)(new RequestVacationCommand(
            contributorId: $contributor->getId(),
            startDate: new DateTimeImmutable('+1 day'),
            endDate: new DateTimeImmutable('+2 days'),
            type: 'conges_payes',
            dailyHours: '8',
            reason: 'Manager test pending',
        ));

        /** @var VacationRepositoryInterface $repo */
        $repo = static::getContainer()->get(VacationRepositoryInterface::class);
        $vacations = $repo->findByContributor($contributor);

        return end($vacations);
    }

    private function generateCsrfToken(string $id): string
    {
        return static::getContainer()->get('security.csrf.token_manager')->getToken($id)->getValue();
    }

    private function loginAs(User $user): void
    {
        $tokenStorage = static::getContainer()->get('security.token_storage');
        $tokenStorage->setToken(new UsernamePasswordToken($user, 'main', $user->getRoles()));
    }
}
