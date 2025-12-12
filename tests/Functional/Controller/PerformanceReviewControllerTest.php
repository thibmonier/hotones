<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Contributor;
use App\Entity\PerformanceReview;
use App\Entity\User;
use App\Repository\ContributorRepository;
use App\Repository\PerformanceReviewRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class PerformanceReviewControllerTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    private KernelBrowser $client;
    private UserRepository $userRepository;
    private ContributorRepository $contributorRepository;
    private PerformanceReviewRepository $reviewRepository;
    private static int $userCounter = 0;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container    = static::getContainer();

        $this->userRepository        = $container->get(UserRepository::class);
        $this->contributorRepository = $container->get(ContributorRepository::class);
        $this->reviewRepository      = $container->get(PerformanceReviewRepository::class);
    }

    private function createAuthenticatedUser(string $role = 'ROLE_MANAGER'): User
    {
        ++self::$userCounter;
        $user = new User();
        $user->setEmail('test'.self::$userCounter.'@example.com');
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

    private function createPerformanceReview(Contributor $contributor, User $manager, int $year = 2024): PerformanceReview
    {
        $review = new PerformanceReview();
        $review->setYear($year);
        $review->setContributor($contributor);
        $review->setManager($manager);

        return $review;
    }

    public function testIndexRequiresAuthentication(): void
    {
        $this->client->request('GET', '/performance-reviews');

        $this->assertResponseRedirects('/login');
    }

    public function testIndexDisplaysReviews(): void
    {
        $user        = $this->createAuthenticatedUser('ROLE_MANAGER');
        $contributor = $this->createContributor();

        // Create a review
        $review = $this->createPerformanceReview($contributor, $user, 2024);

        $em = static::getContainer()->get('doctrine')->getManager();
        $em->persist($review);
        $em->flush();

        $this->client->loginUser($user);
        $this->client->request('GET', '/performance-reviews');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h4', 'Évaluations annuelles');
    }

    public function testCreateRequiresManagerRole(): void
    {
        $user = $this->createAuthenticatedUser('ROLE_INTERVENANT');
        $this->client->loginUser($user);

        $this->client->request('GET', '/performance-reviews/campaign/create');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateDisplaysForm(): void
    {
        $user = $this->createAuthenticatedUser('ROLE_ADMIN');
        $this->client->loginUser($user);

        $this->client->request('GET', '/performance-reviews/campaign/create');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('select[name="year"]');
    }

    public function testCreateSubmitWithValidData(): void
    {
        $user = $this->createAuthenticatedUser('ROLE_ADMIN');
        $this->createContributor();

        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/performance-reviews/campaign/create');
        $form    = $crawler->selectButton('Créer la campagne')->form([
            'year' => '2024',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects();
        $this->client->followRedirect();

        // Check flash message
        $this->assertSelectorExists('.alert-success');
    }

    public function testShowRequiresAuthentication(): void
    {
        $user        = $this->createAuthenticatedUser('ROLE_MANAGER');
        $contributor = $this->createContributor();
        $review      = $this->createPerformanceReview($contributor, $user, 2024);

        $em = static::getContainer()->get('doctrine')->getManager();
        $em->persist($review);
        $em->flush();

        $this->client->request('GET', '/performance-reviews/'.$review->getId());

        $this->assertResponseRedirects('/login');
    }

    public function testShowDisplaysReviewDetails(): void
    {
        $user        = $this->createAuthenticatedUser('ROLE_MANAGER');
        $contributor = $this->createContributor();

        $review = $this->createPerformanceReview($contributor, $user, 2024);
        $review->setComments('Strong performance');
        $review->setObjectives([
            ['title' => 'Team leadership', 'description' => 'Lead team initiatives'],
        ]);

        $em = static::getContainer()->get('doctrine')->getManager();
        $em->persist($review);
        $em->flush();

        $this->client->loginUser($user);
        $this->client->request('GET', '/performance-reviews/'.$review->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', '2024');
    }

    // TODO: Implement edit route in PerformanceReviewController
    public function testEditRequiresManagerRole(): void
    {
        $this->markTestSkipped('Edit route not yet implemented in PerformanceReviewController');
    }

    // TODO: Implement edit route in PerformanceReviewController
    public function testEditSubmitUpdatesReview(): void
    {
        $this->markTestSkipped('Edit route not yet implemented in PerformanceReviewController');
    }

    public function testValidateRequiresManagerRole(): void
    {
        $user        = $this->createAuthenticatedUser('ROLE_INTERVENANT');
        $manager     = $this->createAuthenticatedUser('ROLE_MANAGER');
        $contributor = $this->createContributor();

        $review = $this->createPerformanceReview($contributor, $manager, 2024);
        $review->setStatus('en_attente');

        $em = static::getContainer()->get('doctrine')->getManager();
        $em->persist($review);
        $em->flush();

        $this->client->loginUser($user);
        $this->client->request('POST', '/performance-reviews/'.$review->getId().'/validate');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testValidateChangesStatus(): void
    {
        $user        = $this->createAuthenticatedUser('ROLE_MANAGER');
        $contributor = $this->createContributor();

        $review = $this->createPerformanceReview($contributor, $user, 2024);
        $review->setStatus('eval_manager_faite');
        $review->setSelfEvaluation([
            'achievements' => 'Did great things',
            'strengths'    => 'Coding',
            'improvements' => 'Testing',
        ]);
        $review->setManagerEvaluation([
            'achievements' => 'Observed great things',
            'strengths'    => 'Coding',
            'improvements' => 'Testing',
            'feedback'     => 'Good job',
        ]);

        $em = static::getContainer()->get('doctrine')->getManager();
        $em->persist($review);
        $em->flush();

        $this->client->loginUser($user);

        // Get CSRF token from validate page
        $crawler = $this->client->request('GET', '/performance-reviews/'.$review->getId().'/validate');
        $this->assertResponseIsSuccessful();

        $token = $crawler->filter('input[name="_token"]')->attr('value');

        $this->client->request('POST', '/performance-reviews/'.$review->getId().'/validate', [
            '_token' => $token,
        ]);

        $this->assertResponseRedirects();

        // Verify status changed
        $em->clear();
        $validatedReview = $this->reviewRepository->find($review->getId());
        $this->assertEquals('validee', $validatedReview->getStatus());
    }

    public function testDeleteRequiresAdminRole(): void
    {
        $this->markTestIncomplete('Delete route not yet implemented');
    }

    public function testDeleteRemovesReview(): void
    {
        $this->markTestIncomplete('Delete route not yet implemented');
    }
}
