<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Factory\ContributorFactory;
use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Sprint-023 sub-epic B BUFFER tests Integration sprint-021 suite —
 * rattrapage WeeklyTimesheetController Functional (US-102 sprint-021).
 *
 * Vérifie endpoint `/timesheet/week/{week}` (US-102) + JSON save endpoint.
 *
 * Sprint-026 TEST-FUNCTIONAL-FIXES-003 : marker `skip-pre-push` retiré
 * (test passe désormais en pre-push, env brittleness historique résorbée).
 */
final class WeeklyTimesheetControllerTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    public function testIndexRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/timesheet/week');

        // ROLE_INTERVENANT required → redirect login
        $this->assertResponseRedirects('/login');
    }

    public function testIndexAcceptsCurrentWeekDefault(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_INTERVENANT']]);
        ContributorFactory::createOne(['user' => $user, 'company' => $user->getCompany()]);
        $client->loginUser($user);

        $client->request('GET', '/timesheet/week');

        // 200 OK = grille rendue OR redirect (depending on multitenant resolution)
        static::assertContains($client->getResponse()->getStatusCode(), [200, 302]);
    }

    public function testIndexAcceptsExplicitIsoWeek(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_INTERVENANT']]);
        ContributorFactory::createOne(['user' => $user, 'company' => $user->getCompany()]);
        $client->loginUser($user);

        $client->request('GET', '/timesheet/week/2026-W19');

        static::assertContains($client->getResponse()->getStatusCode(), [200, 302]);
    }

    public function testIndexRejectsInvalidIsoWeekFormat(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_INTERVENANT']]);
        ContributorFactory::createOne(['user' => $user, 'company' => $user->getCompany()]);
        $client->loginUser($user);

        // Route requirements regex \d{4}-W\d{2} — "not-a-week" ne matche pas
        $client->request('GET', '/timesheet/week/not-a-week');

        // 404 — route requirement échoue
        $this->assertResponseStatusCodeSame(404);
    }

    public function testSaveEndpointRequiresPost(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_INTERVENANT']]);
        ContributorFactory::createOne(['user' => $user, 'company' => $user->getCompany()]);
        $client->loginUser($user);

        $client->request('GET', '/timesheet/week/save');

        // 405 Method Not Allowed (route declared POST only)
        $this->assertResponseStatusCodeSame(405);
    }

    public function testSaveEndpointRejectsInvalidJson(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_INTERVENANT']]);
        ContributorFactory::createOne(['user' => $user, 'company' => $user->getCompany()]);
        $client->loginUser($user);

        $client->request(
            'POST',
            '/timesheet/week/save',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: 'not-valid-json',
        );

        $this->assertResponseStatusCodeSame(400);
    }

    public function testSaveEndpointRejectsMissingRequiredFields(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_INTERVENANT']]);
        ContributorFactory::createOne(['user' => $user, 'company' => $user->getCompany()]);
        $client->loginUser($user);

        $client->request(
            'POST',
            '/timesheet/week/save',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['onlyOneField' => 'value']),
        );

        // 422 Unprocessable Entity — required fields manquants
        $this->assertResponseStatusCodeSame(422);
    }
}
