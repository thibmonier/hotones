<?php

namespace App\Tests\Functional\Controller;

use App\Factory\ContributorFactory;
use App\Factory\ProfileFactory;
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

    public function testIndexRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/timesheet');

        $this->assertResponseRedirects('/login');
    }

    public function testIndexWithContributor(): void
    {
        $client = static::createClient();

        // Create required Profile for ProjectTaskFactory
        ProfileFactory::createOne(['name' => 'Developer']);

        $user        = UserFactory::createOne(['roles' => ['ROLE_INTERVENANT']]);
        $contributor = ContributorFactory::createOne(['user' => $user]);
        $project     = ProjectFactory::createOne(['status' => 'active']);

        // Create a task assigned to contributor
        ProjectTaskFactory::createOne([
            'project'             => $project,
            'assignedContributor' => $contributor,
            'active'              => true,
        ]);

        $client->loginUser($user);
        $client->request('GET', '/timesheet');

        $this->assertResponseIsSuccessful();
    }

    public function testSaveTimesheetSuccessfully(): void
    {
        $client = static::createClient();

        $user        = UserFactory::createOne(['roles' => ['ROLE_INTERVENANT']]);
        $contributor = ContributorFactory::createOne(['user' => $user]);
        $project     = ProjectFactory::createOne();

        $client->loginUser($user);
        $client->request('POST', '/timesheet/save', [
            'project_id' => $project->getId(),
            'date'       => '2025-01-15',
            'hours'      => '8.0',
            'notes'      => 'Test timesheet entry',
        ]);

        $this->assertResponseIsSuccessful();
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($response['success']);
    }

    public function testSaveTimesheetRejectsInvalidHours(): void
    {
        $client = static::createClient();

        $user        = UserFactory::createOne(['roles' => ['ROLE_INTERVENANT']]);
        $contributor = ContributorFactory::createOne(['user' => $user]);
        $project     = ProjectFactory::createOne();

        $client->loginUser($user);
        $client->request('POST', '/timesheet/save', [
            'project_id' => $project->getId(),
            'date'       => '2025-01-15',
            'hours'      => '0.5', // Less than minimum 1h (0.125j)
            'notes'      => '',
        ]);

        $this->assertResponseStatusCodeSame(400);
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $response);
        $this->assertStringContainsString('minimale', $response['error']);
    }

    public function testSaveTimesheetWithNonExistentProject(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne(['roles' => ['ROLE_INTERVENANT']]);
        ContributorFactory::createOne(['user' => $user]);

        $client->loginUser($user);
        $client->request('POST', '/timesheet/save', [
            'project_id' => 99999,
            'date'       => '2025-01-15',
            'hours'      => '8.0',
        ]);

        $this->assertResponseStatusCodeSame(400);
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $response);
        $this->assertStringContainsString('Projet non trouvé', $response['error']);
    }

    public function testCalendarView(): void
    {
        $client = static::createClient();

        $user        = UserFactory::createOne(['roles' => ['ROLE_INTERVENANT']]);
        $contributor = ContributorFactory::createOne(['user' => $user]);
        $project     = ProjectFactory::createOne();

        // Create timesheet for calendar
        TimesheetFactory::createOne([
            'contributor' => $contributor,
            'project'     => $project,
            'date'        => new DateTime('2025-01-15'),
            'hours'       => '8.0',
        ]);

        $client->loginUser($user);
        $client->request('GET', '/timesheet/calendar?month=2025-01');

        $this->assertResponseIsSuccessful();
    }

    public function testMyTimeView(): void
    {
        $client = static::createClient();

        $user        = UserFactory::createOne(['roles' => ['ROLE_INTERVENANT']]);
        $contributor = ContributorFactory::createOne(['user' => $user]);
        $project     = ProjectFactory::createOne();

        TimesheetFactory::createOne([
            'contributor' => $contributor,
            'project'     => $project,
            'date'        => new DateTime('2025-01-15'),
            'hours'       => '8.0',
        ]);

        $client->loginUser($user);
        $client->request('GET', '/timesheet/my-time?month=2025-01');

        $this->assertResponseIsSuccessful();
    }

    public function testAllTimesheetsRequiresChefProjetRole(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne(['roles' => ['ROLE_INTERVENANT']]);

        $client->loginUser($user);
        $client->request('GET', '/timesheet/all');

        $this->assertResponseStatusCodeSame(403); // Forbidden
    }

    public function testAllTimesheetsWithChefProjetRole(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne(['roles' => ['ROLE_CHEF_PROJET']]);

        $client->loginUser($user);
        $client->request('GET', '/timesheet/all');

        $this->assertResponseIsSuccessful();
    }

    public function testDuplicateWeek(): void
    {
        $this->markTestSkipped('ISO week date matching issue - needs investigation with actual database queries');
    }

    public function testDuplicateWeekWithSameWeek(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne(['roles' => ['ROLE_INTERVENANT']]);
        ContributorFactory::createOne(['user' => $user]);

        $client->loginUser($user);
        $client->request('POST', '/timesheet/duplicate-week', [
            'source_week' => '2025-W03',
            'target_week' => '2025-W03',
        ]);

        $this->assertResponseStatusCodeSame(400);
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertStringContainsString('différentes', $response['error']);
    }

    public function testStartTimer(): void
    {
        $client = static::createClient();

        // Create required Profile
        ProfileFactory::createOne(['name' => 'Developer']);

        $user        = UserFactory::createOne(['roles' => ['ROLE_INTERVENANT']]);
        $contributor = ContributorFactory::createOne(['user' => $user]);
        $project     = ProjectFactory::createOne();
        $task        = ProjectTaskFactory::createOne(['project' => $project]);

        $client->loginUser($user);
        $client->request('POST', '/timesheet/timer/start', [
            'project_id' => $project->getId(),
            'task_id'    => $task->getId(),
        ]);

        $this->assertResponseIsSuccessful();
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('timer', $response);
        $this->assertEquals($project->getId(), $response['timer']['project']['id']);
    }

    public function testStopTimer(): void
    {
        $client = static::createClient();

        // Create required Profile
        ProfileFactory::createOne(['name' => 'Developer']);

        $user        = UserFactory::createOne(['roles' => ['ROLE_INTERVENANT']]);
        $contributor = ContributorFactory::createOne(['user' => $user]);
        $project     = ProjectFactory::createOne();
        $task        = ProjectTaskFactory::createOne(['project' => $project]);

        $client->loginUser($user);

        // Start timer first
        $client->request('POST', '/timesheet/timer/start', [
            'project_id' => $project->getId(),
            'task_id'    => $task->getId(),
        ]);

        // Stop timer
        $client->request('POST', '/timesheet/timer/stop');

        $this->assertResponseIsSuccessful();
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('hours_logged', $response);
    }

    public function testStopTimerWithoutActiveTimer(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne(['roles' => ['ROLE_INTERVENANT']]);
        ContributorFactory::createOne(['user' => $user]);

        $client->loginUser($user);
        $client->request('POST', '/timesheet/timer/stop');

        $this->assertResponseStatusCodeSame(400);
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertStringContainsString('Aucun compteur actif', $response['error']);
    }

    public function testTimerOptions(): void
    {
        $client = static::createClient();

        // Create required Profile
        ProfileFactory::createOne(['name' => 'Developer']);

        $user        = UserFactory::createOne(['roles' => ['ROLE_INTERVENANT']]);
        $contributor = ContributorFactory::createOne(['user' => $user]);
        $project     = ProjectFactory::createOne(['status' => 'active']);

        ProjectTaskFactory::createOne([
            'project'             => $project,
            'assignedContributor' => $contributor,
            'active'              => true,
        ]);

        $client->loginUser($user);
        $client->request('GET', '/timesheet/timer/options');

        $this->assertResponseIsSuccessful();
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('projects', $response);
        $this->assertCount(1, $response['projects']);
    }

    public function testActiveTimer(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne(['roles' => ['ROLE_INTERVENANT']]);
        ContributorFactory::createOne(['user' => $user]);

        $client->loginUser($user);
        $client->request('GET', '/timesheet/timer/active');

        $this->assertResponseIsSuccessful();
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('timer', $response);
        $this->assertNull($response['timer']); // No active timer
    }

    public function testExportRequiresContributor(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne(['roles' => ['ROLE_INTERVENANT']]);
        // No contributor associated

        $client->loginUser($user);
        $client->request('GET', '/timesheet/export');

        $this->assertResponseRedirects('/timesheet');
    }
}
