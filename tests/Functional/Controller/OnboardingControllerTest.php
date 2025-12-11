<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Contributor;
use App\Entity\Onboarding;
use App\Entity\OnboardingTask;
use App\Entity\User;
use App\Repository\ContributorRepository;
use App\Repository\OnboardingRepository;
use App\Repository\UserRepository;
use DateTime;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class OnboardingControllerTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    private KernelBrowser $client;
    private UserRepository $userRepository;
    private ContributorRepository $contributorRepository;
    private OnboardingRepository $onboardingRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container    = static::getContainer();

        $this->userRepository        = $container->get(UserRepository::class);
        $this->contributorRepository = $container->get(ContributorRepository::class);
        $this->onboardingRepository  = $container->get(OnboardingRepository::class);
    }

    private function createAuthenticatedUser(string $role = 'ROLE_INTERVENANT'): User
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('$2y$13$hashedpassword');
        $user->setRoles([$role]);
        $user->setFirstName('Test');
        $user->setLastName('User');

        $em = static::getContainer()->get('doctrine')->getManager();
        $em->persist($user);
        $em->flush();

        return $user;
    }

    private function createContributor(string $firstName = 'John', string $lastName = 'Doe'): Contributor
    {
        $contributor = new Contributor();
        $contributor->setFirstName($firstName);
        $contributor->setLastName($lastName);

        $em = static::getContainer()->get('doctrine')->getManager();
        $em->persist($contributor);
        $em->flush();

        return $contributor;
    }

    private function createOnboarding(Contributor $contributor): Onboarding
    {
        $onboarding = new Onboarding();
        $onboarding->setContributor($contributor);
        $onboarding->setStartDate(new DateTime());
        $onboarding->setStatus('in_progress');

        $em = static::getContainer()->get('doctrine')->getManager();
        $em->persist($onboarding);
        $em->flush();

        return $onboarding;
    }

    public function testIndexRequiresAuthentication(): void
    {
        $this->client->request('GET', '/onboarding');

        $this->assertResponseRedirects('/login');
    }

    public function testIndexDisplaysOnboardings(): void
    {
        $user        = $this->createAuthenticatedUser();
        $contributor = $this->createContributor();
        $user->setContributor($contributor);

        $em = static::getContainer()->get('doctrine')->getManager();
        $em->flush();

        $onboarding = $this->createOnboarding($contributor);

        $this->client->loginUser($user);
        $this->client->request('GET', '/onboarding');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h4', 'Mon onboarding');
    }

    public function testShowRequiresAuthentication(): void
    {
        $contributor = $this->createContributor();
        $onboarding  = $this->createOnboarding($contributor);

        $this->client->request('GET', '/onboarding/'.$onboarding->getId());

        $this->assertResponseRedirects('/login');
    }

    public function testShowDisplaysOnboardingDetails(): void
    {
        $user        = $this->createAuthenticatedUser();
        $contributor = $this->createContributor();
        $user->setContributor($contributor);

        $em = static::getContainer()->get('doctrine')->getManager();
        $em->flush();

        $onboarding = $this->createOnboarding($contributor);

        // Add a task
        $task = new OnboardingTask();
        $task->setOnboarding($onboarding);
        $task->setTitle('Welcome task');
        $task->setDescription('Complete your profile');
        $task->setType('action');
        $task->setAssignedTo('contributor');
        $task->setDaysAfterStart(0);
        $task->setDueDate(new DateTime());
        $task->setStatus('pending');
        $task->setOrder(0);

        $em->persist($task);
        $em->flush();

        $this->client->loginUser($user);
        $this->client->request('GET', '/onboarding/'.$onboarding->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Welcome task');
        $this->assertSelectorTextContains('body', 'Complete your profile');
    }

    public function testCompleteTaskRequiresAuthentication(): void
    {
        $contributor = $this->createContributor();
        $onboarding  = $this->createOnboarding($contributor);

        $task = new OnboardingTask();
        $task->setOnboarding($onboarding);
        $task->setTitle('Task');
        $task->setType('action');
        $task->setAssignedTo('contributor');
        $task->setDaysAfterStart(0);
        $task->setDueDate(new DateTime());
        $task->setStatus('pending');
        $task->setOrder(0);

        $em = static::getContainer()->get('doctrine')->getManager();
        $em->persist($task);
        $em->flush();

        $this->client->request('POST', '/onboarding/task/'.$task->getId().'/complete');

        $this->assertResponseRedirects('/login');
    }

    public function testCompleteTaskChangesStatus(): void
    {
        $user        = $this->createAuthenticatedUser();
        $contributor = $this->createContributor();
        $user->setContributor($contributor);

        $em = static::getContainer()->get('doctrine')->getManager();
        $em->flush();

        $onboarding = $this->createOnboarding($contributor);

        $task = new OnboardingTask();
        $task->setOnboarding($onboarding);
        $task->setTitle('Complete me');
        $task->setType('action');
        $task->setAssignedTo('contributor');
        $task->setDaysAfterStart(0);
        $task->setDueDate(new DateTime());
        $task->setStatus('pending');
        $task->setOrder(0);

        $em->persist($task);
        $em->flush();

        $taskId = $task->getId();

        $this->client->loginUser($user);

        // Get CSRF token
        $crawler = $this->client->request('GET', '/onboarding/'.$onboarding->getId());
        $token   = $crawler->filter('input[name="_token"]')->first()->attr('value');

        $this->client->request('POST', '/onboarding/task/'.$taskId.'/complete', [
            '_token' => $token,
        ]);

        $this->assertResponseRedirects();

        // Verify task is completed
        $em->clear();
        $completedTask = $em->getRepository(OnboardingTask::class)->find($taskId);
        $this->assertEquals('completed', $completedTask->getStatus());
        $this->assertNotNull($completedTask->getCompletedAt());
    }

    public function testTeamViewRequiresManagerRole(): void
    {
        $user = $this->createAuthenticatedUser('ROLE_INTERVENANT');
        $this->client->loginUser($user);

        $this->client->request('GET', '/onboarding/team');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testTeamViewDisplaysTeamOnboardings(): void
    {
        $user    = $this->createAuthenticatedUser('ROLE_MANAGER');
        $manager = $this->createContributor('Manager', 'Smith');
        $user->setContributor($manager);

        $em = static::getContainer()->get('doctrine')->getManager();
        $em->flush();

        // Create a team member
        $teamMember = $this->createContributor('Team', 'Member');
        $teamMember->setManager($manager);
        $em->persist($teamMember);
        $em->flush();

        // Create onboarding for team member
        $onboarding = $this->createOnboarding($teamMember);

        $this->client->loginUser($user);
        $this->client->request('GET', '/onboarding/team');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h4', 'Onboarding de l\'équipe');
        $this->assertSelectorTextContains('body', 'Team Member');
    }

    public function testUserCanOnlyAccessOwnOnboarding(): void
    {
        $user1        = $this->createAuthenticatedUser('ROLE_INTERVENANT');
        $contributor1 = $this->createContributor('User', 'One');
        $user1->setContributor($contributor1);

        $contributor2 = $this->createContributor('User', 'Two');

        $em = static::getContainer()->get('doctrine')->getManager();
        $em->flush();

        $onboarding2 = $this->createOnboarding($contributor2);

        $this->client->loginUser($user1);
        $this->client->request('GET', '/onboarding/'.$onboarding2->getId());

        // Should either be forbidden or redirected
        $this->assertResponseStatusCodeSame(403);
    }

    public function testOnboardingProgressCalculation(): void
    {
        $user        = $this->createAuthenticatedUser();
        $contributor = $this->createContributor();
        $user->setContributor($contributor);

        $em = static::getContainer()->get('doctrine')->getManager();
        $em->flush();

        $onboarding = $this->createOnboarding($contributor);

        // Add 3 tasks: 2 completed, 1 pending
        $task1 = new OnboardingTask();
        $task1->setOnboarding($onboarding);
        $task1->setTitle('Task 1');
        $task1->setType('action');
        $task1->setAssignedTo('contributor');
        $task1->setDaysAfterStart(0);
        $task1->setDueDate(new DateTime());
        $task1->setStatus('completed');
        $task1->setCompletedAt(new DateTime());
        $task1->setOrder(0);

        $task2 = new OnboardingTask();
        $task2->setOnboarding($onboarding);
        $task2->setTitle('Task 2');
        $task2->setType('action');
        $task2->setAssignedTo('contributor');
        $task2->setDaysAfterStart(1);
        $task2->setDueDate(new DateTime('+1 day'));
        $task2->setStatus('completed');
        $task2->setCompletedAt(new DateTime());
        $task2->setOrder(1);

        $task3 = new OnboardingTask();
        $task3->setOnboarding($onboarding);
        $task3->setTitle('Task 3');
        $task3->setType('action');
        $task3->setAssignedTo('contributor');
        $task3->setDaysAfterStart(2);
        $task3->setDueDate(new DateTime('+2 days'));
        $task3->setStatus('pending');
        $task3->setOrder(2);

        $em->persist($task1);
        $em->persist($task2);
        $em->persist($task3);
        $em->flush();

        $this->client->loginUser($user);
        $this->client->request('GET', '/onboarding/'.$onboarding->getId());

        $this->assertResponseIsSuccessful();

        // Check that progress is displayed (2/3 = 66%)
        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('66', $content); // Progress percentage
    }
}
