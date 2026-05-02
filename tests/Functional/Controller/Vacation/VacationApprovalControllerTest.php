<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Vacation;

use App\Domain\Vacation\Repository\VacationRepositoryInterface;
use App\Domain\Vacation\ValueObject\VacationStatus;
use App\Entity\Contributor;
use App\Tests\Support\MultiTenantTestTrait;
use App\Tests\Support\VacationFunctionalTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
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
    use VacationFunctionalTrait;

    private KernelBrowser $client;
    private Contributor $manager;
    private Contributor $employee;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->testCompany = $this->createTestCompany();

        $this->manager = $this->provisionVacationContributor('manager@test.com', 'Manon', 'Manager', ['ROLE_MANAGER']);
        $this->employee = $this->provisionVacationContributor('employee@test.com', 'Adrien', 'Test', ['ROLE_INTERVENANT'], $this->manager);

        // Authenticate as the manager so the controller resolves $this->getUser() to a managing contributor.
        $this->loginAs($this->manager->getUser());
    }

    public function testIndexShowsPendingVacationsOfManagedContributors(): void
    {
        $this->createPendingVacationFor($this->employee);

        $this->client->request('GET', '/manager/conges');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h4', 'Validation des demandes');
        self::assertSelectorTextContains('table', 'Adrien Test');
    }

    public function testShowIsForbiddenWhenManagerDoesNotManageContributor(): void
    {
        $unrelatedManager = $this->provisionVacationContributor('other@test.com', 'Other', 'Manager', ['ROLE_MANAGER']);
        $unrelatedEmployee = $this->provisionVacationContributor('other-emp@test.com', 'Lone', 'Wolf', ['ROLE_INTERVENANT'], $unrelatedManager);
        $foreignVacation = $this->createPendingVacationFor($unrelatedEmployee);

        $this->loginAs($this->manager->getUser());

        $this->client->request('GET', '/manager/conges/'.$foreignVacation->getId()->getValue());

        self::assertResponseStatusCodeSame(403);
    }

    public function testApproveTransitionsVacationToApproved(): void
    {
        $vacation = $this->createPendingVacationFor($this->employee);
        $id = $vacation->getId()->getValue();

        $this->client->request('POST', '/manager/conges/'.$id.'/approuver', [
            '_token' => $this->csrfTokenFromForm('approve', $id, '/manager/conges/'.$id),
        ]);

        self::assertResponseRedirects('/manager/conges');

        /** @var VacationRepositoryInterface $repo */
        $repo = static::getContainer()->get(VacationRepositoryInterface::class);
        self::assertSame(VacationStatus::APPROVED, $repo->findById($vacation->getId())->getStatus());
    }

    public function testRejectTransitionsVacationToRejected(): void
    {
        $vacation = $this->createPendingVacationFor($this->employee);
        $id = $vacation->getId()->getValue();

        $this->client->request('POST', '/manager/conges/'.$id.'/rejeter', [
            '_token' => $this->csrfTokenFromForm('reject', $id, '/manager/conges/'.$id),
        ]);

        self::assertResponseRedirects('/manager/conges');

        /** @var VacationRepositoryInterface $repo */
        $repo = static::getContainer()->get(VacationRepositoryInterface::class);
        self::assertSame(VacationStatus::REJECTED, $repo->findById($vacation->getId())->getStatus());
    }

    public function testRejectPersistsRejectionReasonWhenSupplied(): void
    {
        $vacation = $this->createPendingVacationFor($this->employee);
        $id = $vacation->getId()->getValue();

        $this->client->request('POST', '/manager/conges/'.$id.'/rejeter', [
            '_token' => $this->csrfTokenFromForm('reject', $id, '/manager/conges/'.$id),
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
        $vacation = $this->createPendingVacationFor($this->employee);
        $id = $vacation->getId()->getValue();

        $this->client->request('POST', '/manager/conges/'.$id.'/rejeter', [
            '_token' => $this->csrfTokenFromForm('reject', $id, '/manager/conges/'.$id),
        ]);

        /** @var VacationRepositoryInterface $repo */
        $repo = static::getContainer()->get(VacationRepositoryInterface::class);
        self::assertNull($repo->findById($vacation->getId())->getRejectionReason());
    }

    public function testManagerCancelTransitionsApprovedVacationToCancelled(): void
    {
        // US-069: an approved vacation can be cancelled by the manager.
        $vacation = $this->createPendingVacationFor($this->employee);
        $id = $vacation->getId()->getValue();

        // Approve first (the show page renders the approve form pre-approval).
        $this->client->request('POST', '/manager/conges/'.$id.'/approuver', [
            '_token' => $this->csrfTokenFromForm('approve', $id, '/manager/conges/'.$id),
        ]);

        // Manager-cancel the now-approved vacation. The show page now renders
        // the cancel form (the approve form is gone) — same /annuler suffix.
        $this->client->request('POST', '/manager/conges/'.$id.'/annuler', [
            '_token' => $this->csrfTokenFromForm('cancel-manager', $id, '/manager/conges/'.$id),
        ]);

        self::assertResponseRedirects('/manager/conges');

        /** @var VacationRepositoryInterface $repo */
        $repo = static::getContainer()->get(VacationRepositoryInterface::class);
        self::assertSame(VacationStatus::CANCELLED, $repo->findById($vacation->getId())->getStatus());
    }

    public function testManagerCancelIsForbiddenForUnmanagedContributor(): void
    {
        $unrelatedManager = $this->provisionVacationContributor('rogue@test.com', 'Rogue', 'Manager', ['ROLE_MANAGER']);
        $unrelatedEmployee = $this->provisionVacationContributor('rogue-emp@test.com', 'Stranger', 'Wolf', ['ROLE_INTERVENANT'], $unrelatedManager);
        $foreignVacation = $this->createPendingVacationFor($unrelatedEmployee);
        $id = $foreignVacation->getId()->getValue();

        $this->loginAs($this->manager->getUser());

        // The current manager cannot fetch the foreign vacation's show page,
        // so we cannot retrieve the form-bound CSRF token. Submit a deliberately
        // bogus token: the controller's authorization check fires before the
        // CSRF check, so a 403 is the expected outcome regardless.
        $this->client->request('POST', '/manager/conges/'.$id.'/annuler', [
            '_token' => 'forbidden-marker',
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function testPendingCountApiReturnsJsonForManagedContributors(): void
    {
        $this->createPendingVacationFor($this->employee);
        $this->createPendingVacationFor($this->employee);

        $this->client->request('GET', '/manager/conges/api/pending-count');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/json');
        $payload = json_decode($this->client->getResponse()->getContent(), true);
        self::assertSame(2, $payload['count']);
        self::assertCount(2, $payload['vacations']);
    }

}
