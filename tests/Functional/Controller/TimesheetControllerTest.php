<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Factory\ContributorFactory;
use App\Factory\ProjectFactory;
use App\Factory\ProjectTaskFactory;
use App\Factory\TimesheetFactory;
use App\Factory\UserFactory;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class TimesheetControllerTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    public function testTimesheetIndexPageLoadsForAuthenticatedContributor(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne([
            'roles' => ['ROLE_USER', 'ROLE_INTERVENANT'],
        ]);
        $contributor = ContributorFactory::createOne(['user' => $user]);

        $client->loginUser($user);

        $client->request('GET', '/timesheet');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h4', 'Saisie des temps');
    }

    public function testValidationMax24Hours(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne([
            'roles' => ['ROLE_USER', 'ROLE_INTERVENANT'],
        ]);
        $contributor = ContributorFactory::createOne(['user' => $user]);
        $project1    = ProjectFactory::createOne();
        $project2    = ProjectFactory::createOne();

        $client->loginUser($user);

        $today = new DateTime();
        $today->setTime(0, 0, 0);

        // Saisir d'abord 10h sur le projet1
        $client->request('POST', '/timesheet/save', [
            'project_id' => $project1->getId(),
            'date'       => $today->format('Y-m-d'),
            'hours'      => '10.0',
        ]);
        $this->assertResponseIsSuccessful();

        // Saisir ensuite 12h sur le projet2 (total = 22h, OK)
        $client->request('POST', '/timesheet/save', [
            'project_id' => $project2->getId(),
            'date'       => $today->format('Y-m-d'),
            'hours'      => '12.0',
        ]);
        $this->assertResponseIsSuccessful();

        // Créer un projet3 pour tester le dépassement
        $project3 = ProjectFactory::createOne();

        // Tenter d'ajouter 3h supplémentaires (total = 25h > 24h)
        $client->request('POST', '/timesheet/save', [
            'project_id' => $project3->getId(),
            'date'       => $today->format('Y-m-d'),
            'hours'      => '3.0',
        ]);

        $this->assertResponseStatusCodeSame(400);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsString('Dépassement du total quotidien', $data['error']);
    }

    public function testValidationMinimum1Hour(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne([
            'roles' => ['ROLE_USER', 'ROLE_INTERVENANT'],
        ]);
        $contributor = ContributorFactory::createOne(['user' => $user]);
        $project     = ProjectFactory::createOne();

        $client->loginUser($user);

        $today = new DateTime();

        // Tenter de saisir 0.5h (< 1h minimum)
        $client->request('POST', '/timesheet/save', [
            'project_id' => $project->getId(),
            'date'       => $today->format('Y-m-d'),
            'hours'      => '0.5',
        ]);

        $this->assertResponseStatusCodeSame(400);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsString('saisie minimale est de 1 heure', $data['error']);
    }

    public function testSaveTimesheetSuccessfully(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne([
            'roles' => ['ROLE_USER', 'ROLE_INTERVENANT'],
        ]);
        $contributor = ContributorFactory::createOne(['user' => $user]);
        $project     = ProjectFactory::createOne();

        $client->loginUser($user);

        $today = new DateTime();

        // Saisir 7.5h (valide)
        $client->request('POST', '/timesheet/save', [
            'project_id' => $project->getId(),
            'date'       => $today->format('Y-m-d'),
            'hours'      => '7.5',
            'notes'      => 'Test de saisie de temps',
        ]);

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);
    }

    public function testDuplicateWeekSuccessfully(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne([
            'roles' => ['ROLE_USER', 'ROLE_INTERVENANT'],
        ]);
        $contributor = ContributorFactory::createOne(['user' => $user]);
        $project     = ProjectFactory::createOne();
        $task        = ProjectTaskFactory::createOne([
            'project'             => $project,
            'requiredProfile'     => null,
            'assignedContributor' => null,
        ]);

        $client->loginUser($user);

        // Créer des temps pour la semaine 2025-W10 (lundi à vendredi)
        $sourceStart = new DateTime();
        $sourceStart->setISODate(2025, 10, 1); // Lundi de la semaine 10 de 2025

        for ($i = 0; $i < 5; ++$i) {
            $date = clone $sourceStart;
            $date->modify("+{$i} days");

            TimesheetFactory::createOne([
                'contributor' => $contributor,
                'project'     => $project,
                'task'        => $task,
                'subTask'     => null,
                'date'        => $date,
                'hours'       => '7.5',
                'notes'       => null,
            ]);
        }

        // Dupliquer vers la semaine 2025-W11
        $client->request('POST', '/timesheet/duplicate-week', [
            'source_week' => '2025-W10',
            'target_week' => '2025-W11',
        ]);

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals(5, $data['duplicated_count']);
        $this->assertStringContainsString('5 entrée(s) dupliquée(s)', $data['message']);
    }

    public function testDuplicateWeekWithSameSourceAndTarget(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne([
            'roles' => ['ROLE_USER', 'ROLE_INTERVENANT'],
        ]);
        ContributorFactory::createOne(['user' => $user]);

        $client->loginUser($user);

        // Tenter de dupliquer la même semaine sur elle-même
        $client->request('POST', '/timesheet/duplicate-week', [
            'source_week' => '2025-W01',
            'target_week' => '2025-W01',
        ]);

        $this->assertResponseStatusCodeSame(400);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsString('doivent être différentes', $data['error']);
    }

    public function testDuplicateWeekWithNoTimesheets(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne([
            'roles' => ['ROLE_USER', 'ROLE_INTERVENANT'],
        ]);
        ContributorFactory::createOne(['user' => $user]);

        $client->loginUser($user);

        // Tenter de dupliquer une semaine vide
        $client->request('POST', '/timesheet/duplicate-week', [
            'source_week' => '2025-W10',
            'target_week' => '2025-W11',
        ]);

        $this->assertResponseStatusCodeSame(400);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsString('Aucun temps à dupliquer', $data['error']);
    }

    public function testCalendarViewLoadsSuccessfully(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne([
            'roles' => ['ROLE_USER', 'ROLE_INTERVENANT'],
        ]);
        $contributor = ContributorFactory::createOne(['user' => $user]);
        $project     = ProjectFactory::createOne();

        // Créer quelques temps pour le mois en cours
        $today = new DateTime();
        for ($i = 0; $i < 5; ++$i) {
            $date = clone $today;
            $date->modify("-{$i} days");

            TimesheetFactory::createOne([
                'contributor' => $contributor,
                'project'     => $project,
                'date'        => $date,
                'hours'       => '7.5',
            ]);
        }

        $client->loginUser($user);

        $client->request('GET', '/timesheet/calendar');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h4', 'Calendrier des temps');
        $this->assertSelectorExists('#calendar');
    }

    public function testTimesheetIndexRedirectsForNonContributor(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne([
            'roles' => ['ROLE_USER', 'ROLE_INTERVENANT'],
        ]);
        // Ne pas créer de contributeur associé

        $client->loginUser($user);

        $client->request('GET', '/timesheet');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.alert-warning');
        $this->assertSelectorTextContains('.alert-warning', 'Aucun contributeur associé');
    }
}
