<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Vacation;

use App\Domain\Vacation\Repository\VacationRepositoryInterface;
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
    use VacationFunctionalTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->testCompany = $this->createTestCompany();
        $this->testUser = $this->authenticateTestUser($this->testCompany, ['ROLE_INTERVENANT']);

        // The controller looks up Contributor by user; create one bound to the test user.
        $em = $this->getEntityManager();
        $contributor = new Contributor();
        $contributor->setCompany($this->testCompany);
        $contributor->setUser($this->testUser);
        $contributor->setFirstName('Adrien');
        $contributor->setLastName('Test');
        $contributor->setActive(true);
        $em->persist($contributor);
        $em->flush();

        // authenticateTestUser() only sets TokenStorage; the session firewall
        // needs a session cookie too, so re-login through the KernelBrowser.
        $this->client->loginUser($this->testUser);
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

        $tomorrow = (new DateTimeImmutable('+1 day'))->format('Y-m-d');
        $dayAfter = (new DateTimeImmutable('+2 days'))->format('Y-m-d');
        $form = $crawler->selectButton('Soumettre la demande')->form();

        $form['vacation_request[type]'] = 'conges_payes';

        $form['vacation_request[startDate]'] = $tomorrow;
        $form['vacation_request[endDate]'] = $dayAfter;
        $form['vacation_request[dailyHours]'] = '8';
        $form['vacation_request[reason]'] = 'Vacances de printemps';

        $this->client->submit($form);

        self::assertResponseRedirects('/mes-conges');
        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert-success, .alert', 'enregistree');

        // Vacation was persisted via the handler.
        /** @var VacationRepositoryInterface $repo */
        $repo = static::getContainer()->get(VacationRepositoryInterface::class);
        $found = $repo->findByContributor($this->loadContributor());

        self::assertCount(1, $found);
        self::assertSame(VacationStatus::PENDING, $found[0]->getStatus());
    }

    public function testCancelOnPendingVacationFlashesSuccess(): void
    {
        $vacation = $this->createPendingVacationFor($this->loadContributor());
        $id = $vacation->getId()->getValue();

        $this->client->request('POST', '/mes-conges/'.$id.'/annuler', [
            '_token' => $this->csrfTokenFromForm('cancel', $id, '/mes-conges/'.$id),
        ]);

        self::assertResponseRedirects('/mes-conges');
        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert-success, .alert', 'annulee');
    }

    public function testShowIsForbiddenForVacationOwnedByAnotherContributor(): void
    {
        // Provision a foreign contributor in another company. The trait helper
        // takes care of disambiguating the email so we don't collide with the
        // already-provisioned `test@test.com` from setUp.
        $otherCompany = $this->createTestCompany('Other Co');
        $previousCompany = $this->testCompany;
        $this->testCompany = $otherCompany;
        $otherContributor = $this->provisionVacationContributor(
            'mallory@test.com',
            'Mallory',
            'Adversary',
            ['ROLE_INTERVENANT'],
        );
        $this->testCompany = $previousCompany;

        $foreignVacation = $this->createPendingVacationFor($otherContributor);

        // The test client is still logged in as Adrien (setUp), so the
        // controller's ownership check should refuse access to the foreign
        // vacation.
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

}
