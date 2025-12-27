<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Contributor;
use App\Entity\OnboardingTask;
use App\Factory\UserFactory;
use App\Repository\ContributorRepository;
use App\Repository\OnboardingTaskRepository;
use App\Repository\UserRepository;
use DateTimeImmutable;
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
    private OnboardingTaskRepository $taskRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container    = static::getContainer();

        $this->userRepository        = $container->get(UserRepository::class);
        $this->contributorRepository = $container->get(ContributorRepository::class);
        $this->taskRepository        = $container->get(OnboardingTaskRepository::class);
    }

    private function createContributor($user, string $firstName = 'John', string $lastName = 'Doe'): Contributor
    {
        $contributor = new Contributor();
        $contributor->setFirstName($firstName);
        $contributor->setLastName($lastName);
        $contributor->setUser($user);

        $em = static::getContainer()->get('doctrine')->getManager();
        $em->persist($contributor);
        $em->flush();

        return $contributor;
    }

    private function createTask(Contributor $contributor, string $title = 'Test Task', string $status = 'a_faire'): OnboardingTask
    {
        $task = new OnboardingTask();
        $task->setContributor($contributor);
        $task->setTitle($title);
        $task->setDescription('Test description');
        $task->setStatus($status);
        $task->setType('action');
        $task->setAssignedTo('contributor');
        $task->setDaysAfterStart(0);
        $task->setDueDate(new DateTimeImmutable('+7 days'));

        $em = static::getContainer()->get('doctrine')->getManager();
        $em->persist($task);
        $em->flush();

        return $task;
    }

    public function testShowRequiresAuthentication(): void
    {
        $user        = UserFactory::createOne();
        $contributor = $this->createContributor($user);

        $this->client->request('GET', '/onboarding/contributor/'.$contributor->getId());

        $this->assertResponseRedirects('/login');
    }

    public function testShowDisplaysOnboardingDetails(): void
    {
        $user        = UserFactory::createOne();
        $contributor = $this->createContributor($user);
        $this->createTask($contributor, 'Setup workstation');

        $this->client->loginUser($user);
        $this->client->request('GET', '/onboarding/contributor/'.$contributor->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Setup workstation');
    }

    public function testShowDeniesAccessToOtherContributors(): void
    {
        $user1        = UserFactory::createOne();
        $user2        = UserFactory::createOne(['roles' => ['ROLE_INTERVENANT']]);
        $contributor1 = $this->createContributor($user1);

        $this->client->loginUser($user2);
        $this->client->request('GET', '/onboarding/contributor/'.$contributor1->getId());

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCompleteTaskRequiresAuthentication(): void
    {
        $user        = UserFactory::createOne();
        $contributor = $this->createContributor($user);
        $task        = $this->createTask($contributor);

        $this->client->request('POST', '/onboarding/task/'.$task->getId().'/complete');

        $this->assertResponseRedirects('/login');
    }

    public function testCompleteTaskChangesStatus(): void
    {
        $user        = UserFactory::createOne();
        $contributor = $this->createContributor($user);
        $task        = $this->createTask($contributor);

        $this->client->loginUser($user);

        // Initialize session by making a GET request first
        $crawler = $this->client->request('GET', '/onboarding/contributor/'.$contributor->getId());

        // Extract token
        $token = $crawler->filter('a[onclick*="completeTask('.$task->getId().'"]')->attr('data-token');

        $this->client->request('POST', '/onboarding/task/'.$task->getId().'/complete', [
            '_token'   => $token,
            'comments' => 'Task completed successfully',
        ]);

        $this->assertResponseRedirects('/onboarding/contributor/'.$contributor->getId());

        // Verify task is completed
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->clear();
        $updatedTask = $this->taskRepository->find($task->getId());
        $this->assertEquals('termine', $updatedTask->getStatus());
    }

    public function testTeamViewRequiresManagerRole(): void
    {
        $user = UserFactory::createOne(['roles' => ['ROLE_INTERVENANT']]);
        $this->client->loginUser($user);

        $this->client->request('GET', '/onboarding/team');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testTeamViewDisplaysTeamOnboardings(): void
    {
        $manager     = UserFactory::createOne(['roles' => ['ROLE_MANAGER']]);
        $user        = UserFactory::createOne(['roles' => ['ROLE_INTERVENANT']]);
        $contributor = $this->createContributor($user);
        $this->createTask($contributor);

        $this->client->loginUser($manager);
        $this->client->request('GET', '/onboarding/team');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('body');
    }

    public function testUpdateTaskStatusRequiresAuthentication(): void
    {
        $user        = UserFactory::createOne();
        $contributor = $this->createContributor($user);
        $task        = $this->createTask($contributor);

        $this->client->request('POST', '/onboarding/task/'.$task->getId().'/update-status');

        $this->assertResponseRedirects('/login');
    }

    public function testUpdateTaskStatusChangesStatus(): void
    {
        $user        = UserFactory::createOne();
        $contributor = $this->createContributor($user);
        $task        = $this->createTask($contributor);

        $this->client->loginUser($user);

        // Initialize session by making a GET request first
        $crawler = $this->client->request('GET', '/onboarding/contributor/'.$contributor->getId());

        // Extract token
        $token = $crawler->filter('a[onclick*="updateTaskStatus('.$task->getId().'"]')->attr('data-token');

        $this->client->request('POST', '/onboarding/task/'.$task->getId().'/update-status', [
            '_token' => $token,
            'status' => 'en_cours',
        ]);

        $this->assertResponseIsSuccessful();

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($response['success']);
        $this->assertEquals('en_cours', $response['status']);
    }
}
