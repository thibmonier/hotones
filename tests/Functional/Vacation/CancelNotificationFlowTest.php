<?php

declare(strict_types=1);

namespace App\Tests\Functional\Vacation;

use App\Application\Vacation\Command\RequestVacation\RequestVacationCommand;
use App\Application\Vacation\Command\RequestVacation\RequestVacationHandler;
use App\Domain\Vacation\Repository\VacationRepositoryInterface;
use App\Domain\Vacation\ValueObject\VacationId;
use App\Domain\Vacation\ValueObject\VacationStatus;
use App\Entity\Contributor;
use App\Tests\Support\MultiTenantTestTrait;
use App\Tests\Support\VacationFunctionalTrait;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * TECH-DEBT-001 — end-to-end test of the cancel notification flow.
 *
 * 1. Manager cancels an APPROVED vacation -> intervenant receives an email
 *    rendered from `templates/emails/vacation_cancelled_by_manager.html.twig`.
 * 2. Intervenant cancels their own PENDING request -> manager receives a
 *    different email (`templates/emails/vacation_cancelled.html.twig`).
 *
 * The Symfony Mailer is exercised via MailerAssertionsTrait — VacationNotificationMessage
 * is dispatched on the sync transport in the test environment so the email lands
 * synchronously in the in-memory transport.
 */
final class CancelNotificationFlowTest extends WebTestCase
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

        $this->manager = $this->provisionVacationContributor('cancel-manager@test.com', 'Manon', 'Manager', ['ROLE_MANAGER']);
        $this->employee = $this->provisionVacationContributor('cancel-employee@test.com', 'Adrien', 'Employee', ['ROLE_INTERVENANT'], $this->manager);
    }

    public function testManagerCancelOfApprovedVacationEmailsTheContributor(): void
    {
        $vacationId = $this->submitAsEmployee();

        // Manager approves first.
        $this->loginAs($this->manager->getUser());
        $this->client->request('POST', '/manager/conges/'.$vacationId.'/approuver', [
            '_token' => $this->csrfTokenFromForm('approve', $vacationId, '/manager/conges/'.$vacationId),
        ]);

        // Reset emails captured during approve (out of scope here).
        $this->client->getProfile();

        // Manager cancels the approved vacation -> TECH-DEBT-001 path.
        $this->client->request('POST', '/manager/conges/'.$vacationId.'/annuler', [
            '_token' => $this->csrfTokenFromForm('cancel-manager', $vacationId, '/manager/conges/'.$vacationId),
        ]);

        self::assertResponseRedirects('/manager/conges');

        // Vacation final state
        /** @var VacationRepositoryInterface $repo */
        $repo = static::getContainer()->get(VacationRepositoryInterface::class);
        $found = $repo->findByIdOrNull(VacationId::fromString($vacationId));
        self::assertNotNull($found);
        self::assertSame(VacationStatus::CANCELLED, $found->getStatus());

        // assertEmailCount inspects only the *last* request — here the
        // cancel POST. The vacation_created and vacation_approved emails
        // were captured on prior requests and live in `getMailerMessages()`
        // (cumulative), but not in `getMailerEvents()` for this request.
        self::assertEmailCount(1);
        $email = $this->findMailerMessageWithSubject('Votre demande de conge a ete annulee par votre manager');
        self::assertNotNull($email, 'Expected the contributor to receive a cancellation email');
        self::assertSame('cancel-employee@test.com', $email->getTo()[0]->getAddress());
    }

    public function testContributorCancelOfPendingRequestEmailsTheManager(): void
    {
        $vacationId = $this->submitAsEmployee();

        // Intervenant cancels their own PENDING request.
        $this->loginAs($this->employee->getUser());
        $this->client->request('POST', '/mes-conges/'.$vacationId.'/annuler', [
            '_token' => $this->csrfTokenFromForm('cancel', $vacationId, '/mes-conges/'.$vacationId),
        ]);

        self::assertResponseRedirects('/mes-conges');

        // Manager receives the heads-up email
        $email = $this->findMailerMessageWithSubject('Demande de conge annulee par le collaborateur');
        self::assertNotNull($email, 'Expected the manager to be notified of the self-cancel');
        self::assertSame('cancel-manager@test.com', $email->getTo()[0]->getAddress());
    }

    private function submitAsEmployee(): string
    {
        $this->loginAs($this->employee->getUser());

        /** @var RequestVacationHandler $handler */
        $handler = static::getContainer()->get(RequestVacationHandler::class);
        $vacationId = ($handler)(new RequestVacationCommand(
            contributorId: $this->employee->getId(),
            startDate: new DateTimeImmutable('+5 days'),
            endDate: new DateTimeImmutable('+7 days'),
            type: 'conges_payes',
            dailyHours: '8',
            reason: 'Tech-debt-001 flow',
        ));

        return $vacationId->getValue();
    }
}
