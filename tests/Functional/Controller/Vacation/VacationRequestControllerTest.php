<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Vacation;

use App\Domain\Vacation\Entity\Vacation;
use App\Domain\Vacation\Repository\VacationRepositoryInterface;
use App\Domain\Vacation\ValueObject\VacationStatus;
use App\Entity\Contributor;
use App\Tests\Support\MultiTenantTestTrait;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Functional tests for the VacationRequestController routes (US-066).
 *
 *   GET  /mes-conges                        -> index of the contributor's vacations
 *   GET  /mes-conges/nouvelle-demande       -> empty form
 *   POST /mes-conges/nouvelle-demande       -> dispatches RequestVacationCommand
 *   GET  /mes-conges/{id}                   -> show, with ownership check
 *   POST /mes-conges/{id}/annuler           -> cancels a pending request
 *
 * Each test boots the full Symfony kernel (WebTestCase) and authenticates a
 * ROLE_INTERVENANT user bound to a Contributor in the test company so the
 * controller can resolve `$this->getUser()` -> Contributor.
 */
final class VacationRequestControllerTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;
    use MultiTenantTestTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client      = static::createClient();
        $this->testCompany = $this->createTestCompany();
        $this->testUser    = $this->authenticateTestUser($this->testCompany, ['ROLE_INTERVENANT']);

        // The controller looks up Contributor by user; create one bound to the test user.
        $em          = $this->getEntityManager();
        $contributor = new Contributor();
        $contributor->setCompany($this->testCompany);
        $contributor->setUser($this->testUser);
        $contributor->setFirstName('Adrien');
        $contributor->setLastName('Test');
        $contributor->setActive(true);
        $em->persist($contributor);
        $em->flush();
    }

    public function testIndexRendersEmptyStateWhenContributorHasNoVacation(): void
    {
        $this->client->request('GET', '/mes-conges');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h4', 'Mes demandes de conges');
        self::assertSelectorExists('a[href$="/mes-conges/nouvelle-demande"]');
    }

    public function testNewRendersForm(): void
    {
        $this->client->request('GET', '/mes-conges/nouvelle-demande');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form[name="vacation_request"]');
        self::assertSelectorExists('select[name="vacation_request[type]"]');
        self::assertSelectorExists('input[name="vacation_request[startDate]"]');
        self::assertSelectorExists('input[name="vacation_request[endDate]"]');
    }

    public function testNewDispatchesCommandAndRedirectsToIndex(): void
    {
        $crawler = $this->client->request('GET', '/mes-conges/nouvelle-demande');
        self::assertResponseIsSuccessful();

        $tomorrow                             = (new DateTimeImmutable('+1 day'))->format('Y-m-d');
        $dayAfter                             = (new DateTimeImmutable('+2 days'))->format('Y-m-d');
        $form                                 = $crawler->selectButton('Soumettre la demande')->form();
        $form['vacation_request[type]']       = 'conges_payes';
        $form['vacation_request[startDate]']  = $tomorrow;
        $form['vacation_request[endDate]']    = $dayAfter;
        $form['vacation_request[dailyHours]'] = '8';
        $form['vacation_request[reason]']     = 'Vacances de printemps';

        $this->client->submit($form);

        self::assertResponseRedirects('/mes-conges');
        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert-success, .alert', 'enregistree');

        // Vacation was persisted via the handler.
        /** @var VacationRepositoryInterface $repo */
        $repo  = static::getContainer()->get(VacationRepositoryInterface::class);
        $found = $repo->findByContributor($this->loadContributor());

        self::assertCount(1, $found);
        self::assertSame(VacationStatus::PENDING, $found[0]->getStatus());
    }

    public function testCancelOnPendingVacationFlashesSuccess(): void
    {
        $vacation = $this->createPendingVacation();

        $this->client->request('POST', '/mes-conges/'.$vacation->getId()->getValue().'/annuler', [
            '_token' => $this->generateCsrfToken('cancel'.$vacation->getId()->getValue()),
        ]);

        self::assertResponseRedirects('/mes-conges');
        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert-success, .alert', 'annulee');
    }

    public function testShowIsForbiddenForVacationOwnedByAnotherContributor(): void
    {
        $em               = $this->getEntityManager();
        $otherCompany     = $this->createTestCompany('Other Co');
        $otherUser        = $this->authenticateTestUser($otherCompany, ['ROLE_INTERVENANT']);
        $otherContributor = new Contributor();
        $otherContributor->setCompany($otherCompany);
        $otherContributor->setUser($otherUser);
        $otherContributor->setFirstName('Mallory');
        $otherContributor->setLastName('Adversary');
        $otherContributor->setActive(true);
        $em->persist($otherContributor);
        $em->flush();

        $foreignVacation = $this->createPendingVacation($otherContributor);

        // Re-authenticate as the original Adrien (ROLE_INTERVENANT in testCompany)
        $this->testUser = $this->authenticateTestUser($this->testCompany, ['ROLE_INTERVENANT']);

        $this->client->request('GET', '/mes-conges/'.$foreignVacation->getId()->getValue());

        self::assertResponseStatusCodeSame(403);
    }

    private function loadContributor(): Contributor
    {
        $em = $this->getEntityManager();
        /** @var Contributor $contributor */
        $contributor = $em->getRepository(Contributor::class)->findOneBy(['user' => $this->testUser]);

        return $contributor;
    }

    private function createPendingVacation(?Contributor $contributor = null): Vacation
    {
        $contributor ??= $this->loadContributor();

        /** @var \App\Application\Vacation\Command\RequestVacation\RequestVacationHandler $handler */
        $handler = static::getContainer()->get(\App\Application\Vacation\Command\RequestVacation\RequestVacationHandler::class);

        ($handler)(new \App\Application\Vacation\Command\RequestVacation\RequestVacationCommand(
            contributorId: $contributor->getId(),
            startDate: new DateTimeImmutable('+1 day'),
            endDate: new DateTimeImmutable('+2 days'),
            type: 'conges_payes',
            dailyHours: '8',
            reason: 'Test pending',
        ));

        /** @var VacationRepositoryInterface $repo */
        $repo = static::getContainer()->get(VacationRepositoryInterface::class);

        return $repo->findByContributor($contributor)[0];
    }

    private function generateCsrfToken(string $id): string
    {
        return static::getContainer()->get('security.csrf.token_manager')->getToken($id)->getValue();
    }
}
