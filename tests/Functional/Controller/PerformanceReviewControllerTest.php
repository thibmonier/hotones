<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Contributor;
use App\Entity\PerformanceReview;
use App\Entity\User;
use App\Repository\ContributorRepository;
use App\Repository\PerformanceReviewRepository;
use App\Repository\UserRepository;
use DateTime;
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

    public function testIndexRequiresAuthentication(): void
    {
        $this->client->request('GET', '/performance-review');

        $this->assertResponseRedirects('/login');
    }

    public function testIndexDisplaysReviews(): void
    {
        $user        = $this->createAuthenticatedUser('ROLE_MANAGER');
        $contributor = $this->createContributor();

        // Create a review
        $review = new PerformanceReview();
        $review->setContributor($contributor);
        $review->setReviewer($contributor);
        $review->setReviewDate(new DateTime());
        $review->setNextReviewDate(new DateTime('+6 months'));
        $review->setGeneralComments('Good performance');
        $review->setGoals('Continue growing');

        $em = static::getContainer()->get('doctrine')->getManager();
        $em->persist($review);
        $em->flush();

        $this->client->loginUser($user);
        $this->client->request('GET', '/performance-review');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h4', 'Évaluations de performance');
    }

    public function testCreateRequiresManagerRole(): void
    {
        $user = $this->createAuthenticatedUser('ROLE_INTERVENANT');
        $this->client->loginUser($user);

        $this->client->request('GET', '/performance-review/create');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateDisplaysForm(): void
    {
        $user = $this->createAuthenticatedUser('ROLE_MANAGER');
        $this->client->loginUser($user);

        $this->client->request('GET', '/performance-review/create');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('select[name="contributor_id"]');
        $this->assertSelectorExists('input[name="review_date"]');
        $this->assertSelectorExists('textarea[name="general_comments"]');
    }

    public function testCreateSubmitWithValidData(): void
    {
        $user        = $this->createAuthenticatedUser('ROLE_MANAGER');
        $contributor = $this->createContributor();

        $userContributor = new Contributor();
        $userContributor->setFirstName('Manager');
        $userContributor->setLastName('User');
        $user->setContributor($userContributor);

        $em = static::getContainer()->get('doctrine')->getManager();
        $em->persist($userContributor);
        $em->flush();

        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/performance-review/create');
        $form    = $crawler->selectButton('Créer l\'évaluation')->form([
            'contributor_id'   => (string) $contributor->getId(),
            'review_date'      => '2024-12-01',
            'next_review_date' => '2025-06-01',
            'general_comments' => 'Excellent work',
            'goals'            => 'Leadership development',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects();
        $this->client->followRedirect();

        // Check flash message
        $this->assertSelectorExists('.alert-success');

        // Verify review was created
        $review = $this->reviewRepository->findOneBy(['contributor' => $contributor]);
        $this->assertNotNull($review);
        $this->assertEquals('Excellent work', $review->getGeneralComments());
    }

    public function testShowRequiresAuthentication(): void
    {
        $contributor = $this->createContributor();
        $review      = new PerformanceReview();
        $review->setContributor($contributor);
        $review->setReviewer($contributor);
        $review->setReviewDate(new DateTime());
        $review->setNextReviewDate(new DateTime('+6 months'));

        $em = static::getContainer()->get('doctrine')->getManager();
        $em->persist($review);
        $em->flush();

        $this->client->request('GET', '/performance-review/'.$review->getId());

        $this->assertResponseRedirects('/login');
    }

    public function testShowDisplaysReviewDetails(): void
    {
        $user        = $this->createAuthenticatedUser('ROLE_MANAGER');
        $contributor = $this->createContributor();

        $review = new PerformanceReview();
        $review->setContributor($contributor);
        $review->setReviewer($contributor);
        $review->setReviewDate(new DateTime('2024-12-01'));
        $review->setNextReviewDate(new DateTime('2025-06-01'));
        $review->setGeneralComments('Strong performance');
        $review->setGoals('Team leadership');

        $em = static::getContainer()->get('doctrine')->getManager();
        $em->persist($review);
        $em->flush();

        $this->client->loginUser($user);
        $this->client->request('GET', '/performance-review/'.$review->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Strong performance');
        $this->assertSelectorTextContains('body', 'Team leadership');
    }

    public function testEditRequiresManagerRole(): void
    {
        $user        = $this->createAuthenticatedUser('ROLE_INTERVENANT');
        $contributor = $this->createContributor();

        $review = new PerformanceReview();
        $review->setContributor($contributor);
        $review->setReviewer($contributor);
        $review->setReviewDate(new DateTime());
        $review->setNextReviewDate(new DateTime('+6 months'));

        $em = static::getContainer()->get('doctrine')->getManager();
        $em->persist($review);
        $em->flush();

        $this->client->loginUser($user);
        $this->client->request('GET', '/performance-review/'.$review->getId().'/edit');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testEditSubmitUpdatesReview(): void
    {
        $user        = $this->createAuthenticatedUser('ROLE_MANAGER');
        $contributor = $this->createContributor();

        $review = new PerformanceReview();
        $review->setContributor($contributor);
        $review->setReviewer($contributor);
        $review->setReviewDate(new DateTime('2024-12-01'));
        $review->setNextReviewDate(new DateTime('2025-06-01'));
        $review->setGeneralComments('Original comments');
        $review->setGoals('Original goals');

        $em = static::getContainer()->get('doctrine')->getManager();
        $em->persist($review);
        $em->flush();

        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/performance-review/'.$review->getId().'/edit');
        $form    = $crawler->selectButton('Enregistrer')->form([
            'general_comments' => 'Updated comments',
            'goals'            => 'Updated goals',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects();

        // Verify changes
        $em->clear();
        $updatedReview = $this->reviewRepository->find($review->getId());
        $this->assertEquals('Updated comments', $updatedReview->getGeneralComments());
        $this->assertEquals('Updated goals', $updatedReview->getGoals());
    }

    public function testValidateRequiresManagerRole(): void
    {
        $user        = $this->createAuthenticatedUser('ROLE_INTERVENANT');
        $contributor = $this->createContributor();

        $review = new PerformanceReview();
        $review->setContributor($contributor);
        $review->setReviewer($contributor);
        $review->setReviewDate(new DateTime());
        $review->setNextReviewDate(new DateTime('+6 months'));
        $review->setStatus('pending');

        $em = static::getContainer()->get('doctrine')->getManager();
        $em->persist($review);
        $em->flush();

        $this->client->loginUser($user);
        $this->client->request('POST', '/performance-review/'.$review->getId().'/validate');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testValidateChangesStatus(): void
    {
        $user        = $this->createAuthenticatedUser('ROLE_MANAGER');
        $contributor = $this->createContributor();

        $review = new PerformanceReview();
        $review->setContributor($contributor);
        $review->setReviewer($contributor);
        $review->setReviewDate(new DateTime());
        $review->setNextReviewDate(new DateTime('+6 months'));
        $review->setStatus('pending');

        $em = static::getContainer()->get('doctrine')->getManager();
        $em->persist($review);
        $em->flush();

        $this->client->loginUser($user);

        // Get CSRF token
        $crawler = $this->client->request('GET', '/performance-review/'.$review->getId());
        $token   = $crawler->filter('input[name="_token"]')->attr('value');

        $this->client->request('POST', '/performance-review/'.$review->getId().'/validate', [
            '_token' => $token,
        ]);

        $this->assertResponseRedirects();

        // Verify status changed
        $em->clear();
        $validatedReview = $this->reviewRepository->find($review->getId());
        $this->assertEquals('validated', $validatedReview->getStatus());
    }

    public function testDeleteRequiresAdminRole(): void
    {
        $user        = $this->createAuthenticatedUser('ROLE_MANAGER');
        $contributor = $this->createContributor();

        $review = new PerformanceReview();
        $review->setContributor($contributor);
        $review->setReviewer($contributor);
        $review->setReviewDate(new DateTime());
        $review->setNextReviewDate(new DateTime('+6 months'));

        $em = static::getContainer()->get('doctrine')->getManager();
        $em->persist($review);
        $em->flush();

        $this->client->loginUser($user);
        $this->client->request('POST', '/performance-review/'.$review->getId().'/delete');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testDeleteRemovesReview(): void
    {
        $user        = $this->createAuthenticatedUser('ROLE_ADMIN');
        $contributor = $this->createContributor();

        $review = new PerformanceReview();
        $review->setContributor($contributor);
        $review->setReviewer($contributor);
        $review->setReviewDate(new DateTime());
        $review->setNextReviewDate(new DateTime('+6 months'));

        $em = static::getContainer()->get('doctrine')->getManager();
        $em->persist($review);
        $em->flush();

        $reviewId = $review->getId();

        $this->client->loginUser($user);

        // Get CSRF token
        $crawler = $this->client->request('GET', '/performance-review');
        $token   = $crawler->filter('form')->first()->filter('input[name="_token"]')->attr('value');

        $this->client->request('POST', '/performance-review/'.$reviewId.'/delete', [
            '_token' => $token,
        ]);

        $this->assertResponseRedirects();

        // Verify review was deleted
        $deletedReview = $this->reviewRepository->find($reviewId);
        $this->assertNull($deletedReview);
    }
}
