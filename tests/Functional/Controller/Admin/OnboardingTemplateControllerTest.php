<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Admin;

use App\Entity\OnboardingTemplate;
use App\Entity\Profile;
use App\Factory\UserFactory;
use App\Repository\OnboardingTemplateRepository;
use App\Repository\ProfileRepository;
use App\Tests\Support\MultiTenantTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class OnboardingTemplateControllerTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;
    use MultiTenantTestTrait;

    private KernelBrowser $client;
    private OnboardingTemplateRepository $templateRepository;
    private ProfileRepository $profileRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container    = static::getContainer();

        $this->templateRepository = $container->get(OnboardingTemplateRepository::class);
        $this->profileRepository  = $container->get(ProfileRepository::class);
        $this->setUpMultiTenant();
    }

    private function createTemplate(string $name = 'Test Template'): OnboardingTemplate
    {
        $template = new OnboardingTemplate();
        $template->setCompany($this->getTestCompany());
        $template->setName($name);
        $template->setDescription('Test description');
        $template->setActive(true);
        $template->setTasks([
            [
                'title'            => 'Task 1',
                'description'      => 'Description 1',
                'type'             => 'action',
                'assigned_to'      => 'contributor',
                'days_after_start' => 0,
                'order'            => 0,
            ],
        ]);

        $em = static::getContainer()->get('doctrine')->getManager();
        $em->persist($template);
        $em->flush();

        return $template;
    }

    public function testIndexRequiresAdminRole(): void
    {
        $user = UserFactory::createOne(['roles' => ['ROLE_MANAGER']]);
        $this->client->loginUser($user);

        $this->client->request('GET', '/admin/onboarding-templates');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testIndexDisplaysTemplates(): void
    {
        $user     = UserFactory::createOne(['roles' => ['ROLE_ADMIN']]);
        $template = $this->createTemplate('Developer Onboarding');

        $this->client->loginUser($user);
        $this->client->request('GET', '/admin/onboarding-templates');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h4', 'Templates d\'onboarding');
        $this->assertSelectorTextContains('body', 'Developer Onboarding');
    }

    public function testIndexShowsEmptyStateWhenNoTemplates(): void
    {
        $user = UserFactory::createOne(['roles' => ['ROLE_ADMIN']]);

        $this->client->loginUser($user);
        $this->client->request('GET', '/admin/onboarding-templates');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.alert-info');
        $this->assertSelectorTextContains('.alert-info', 'Aucun template');
    }

    public function testCreateRequiresAdminRole(): void
    {
        $user = UserFactory::createOne(['roles' => ['ROLE_MANAGER']]);
        $this->client->loginUser($user);

        $this->client->request('GET', '/admin/onboarding-templates/create');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateDisplaysForm(): void
    {
        $user = UserFactory::createOne(['roles' => ['ROLE_ADMIN']]);

        $this->client->loginUser($user);
        $this->client->request('GET', '/admin/onboarding-templates/create');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[name="name"]');
        $this->assertSelectorExists('textarea[name="description"]');
        $this->assertSelectorExists('select[name="profile_id"]');
    }

    public function testCreateSubmitWithValidData(): void
    {
        $user = UserFactory::createOne(['roles' => ['ROLE_ADMIN']]);

        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/admin/onboarding-templates/create');
        $token   = $crawler->filter('input[name="_token"]')->attr('value');

        $this->client->request('POST', '/admin/onboarding-templates/create', [
            '_token'      => $token,
            'name'        => 'New Template',
            'description' => 'A new onboarding template',
            'tasks'       => [
                [
                    'title'            => 'Welcome',
                    'description'      => 'Welcome to the team',
                    'type'             => 'action',
                    'assigned_to'      => 'contributor',
                    'days_after_start' => 0,
                ],
            ],
        ]);

        $this->assertResponseRedirects();

        // Verify template was created
        $template = $this->templateRepository->findOneBy(['name' => 'New Template']);
        $this->assertNotNull($template);
        $this->assertEquals('A new onboarding template', $template->getDescription());
        $this->assertCount(1, $template->getTasks());
    }

    public function testCreateRequiresName(): void
    {
        $user = UserFactory::createOne(['roles' => ['ROLE_ADMIN']]);

        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/admin/onboarding-templates/create');
        $token   = $crawler->filter('input[name="_token"]')->attr('value');

        $this->client->request('POST', '/admin/onboarding-templates/create', [
            '_token'      => $token,
            'name'        => '', // Empty name
            'description' => 'Description',
        ]);

        $this->assertResponseRedirects();
        $this->client->followRedirect();

        // Should show error message (Bootstrap uses alert-danger for errors)
        $this->assertSelectorExists('.alert-danger');
    }

    public function testShowDisplaysTemplateDetails(): void
    {
        $user     = UserFactory::createOne(['roles' => ['ROLE_ADMIN']]);
        $template = $this->createTemplate('QA Template');

        $this->client->loginUser($user);
        $this->client->request('GET', '/admin/onboarding-templates/'.$template->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h4', 'QA Template');
        $this->assertSelectorTextContains('body', 'Test description');
        $this->assertSelectorTextContains('body', 'Task 1');
    }

    public function testEditRequiresAdminRole(): void
    {
        $user     = UserFactory::createOne(['roles' => ['ROLE_MANAGER']]);
        $template = $this->createTemplate();

        $this->client->loginUser($user);
        $this->client->request('GET', '/admin/onboarding-templates/'.$template->getId().'/edit');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testEditDisplaysFormWithExistingData(): void
    {
        $user     = UserFactory::createOne(['roles' => ['ROLE_ADMIN']]);
        $template = $this->createTemplate('Edit Me');

        $this->client->loginUser($user);
        $this->client->request('GET', '/admin/onboarding-templates/'.$template->getId().'/edit');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[name="name"]');
    }

    public function testEditSubmitUpdatesTemplate(): void
    {
        $user     = UserFactory::createOne(['roles' => ['ROLE_ADMIN']]);
        $template = $this->createTemplate('Original Name');

        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/admin/onboarding-templates/'.$template->getId().'/edit');
        $token   = $crawler->filter('input[name="_token"]')->attr('value');

        $this->client->request('POST', '/admin/onboarding-templates/'.$template->getId().'/edit', [
            '_token'      => $token,
            'name'        => 'Updated Name',
            'description' => 'Updated description',
            'active'      => '1',
            'tasks'       => [
                [
                    'title'            => 'Updated Task',
                    'description'      => 'Updated task description',
                    'type'             => 'lecture',
                    'assigned_to'      => 'manager',
                    'days_after_start' => 5,
                ],
            ],
        ]);

        $this->assertResponseRedirects();

        // Verify changes
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->clear();

        $updatedTemplate = $this->templateRepository->find($template->getId());
        $this->assertEquals('Updated Name', $updatedTemplate->getName());
        $this->assertEquals('Updated description', $updatedTemplate->getDescription());
        $this->assertTrue($updatedTemplate->isActive());
    }

    public function testDuplicateCreatesNewTemplate(): void
    {
        $user     = UserFactory::createOne(['roles' => ['ROLE_ADMIN']]);
        $template = $this->createTemplate('Original Template');

        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/admin/onboarding-templates/'.$template->getId());
        $token   = $crawler->filter('input[name="_token"]')->first()->attr('value');

        $this->client->request('POST', '/admin/onboarding-templates/'.$template->getId().'/duplicate', [
            '_token'   => $token,
            'new_name' => 'Duplicated Template',
        ]);

        $this->assertResponseRedirects();

        // Verify duplicate was created
        $duplicate = $this->templateRepository->findOneBy(['name' => 'Duplicated Template']);
        $this->assertNotNull($duplicate);
        $this->assertNotEquals($template->getId(), $duplicate->getId());
        $this->assertEquals($template->getDescription(), $duplicate->getDescription());
        $this->assertTrue($duplicate->isActive()); // Duplicates are active by default
    }

    public function testToggleChangesActiveStatus(): void
    {
        $user     = UserFactory::createOne(['roles' => ['ROLE_ADMIN']]);
        $template = $this->createTemplate();
        $template->setActive(true);

        $em = static::getContainer()->get('doctrine')->getManager();
        $em->flush();

        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/admin/onboarding-templates');

        // Extract token from the toggle button
        $token = $crawler->filter('button[onclick*="toggleTemplate('.$template->getId().'"]')->attr('data-token');

        $this->client->request('POST', '/admin/onboarding-templates/'.$template->getId().'/toggle', [
            '_token' => $token,
        ]);

        $this->assertResponseRedirects();

        // Verify status changed
        $em->clear();
        $toggledTemplate = $this->templateRepository->find($template->getId());
        $this->assertFalse($toggledTemplate->isActive());
    }

    public function testDeleteRemovesTemplate(): void
    {
        $user       = UserFactory::createOne(['roles' => ['ROLE_ADMIN']]);
        $template   = $this->createTemplate('Delete Me');
        $templateId = $template->getId();

        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/admin/onboarding-templates');

        // Extract token from the delete button
        $token = $crawler->filter('button[onclick*="deleteTemplate('.$templateId.'"]')->attr('data-token');

        $this->client->request('POST', '/admin/onboarding-templates/'.$templateId.'/delete', [
            '_token' => $token,
        ]);

        $this->assertResponseRedirects();

        // Verify template was deleted
        $deletedTemplate = $this->templateRepository->find($templateId);
        $this->assertNull($deletedTemplate);
    }

    public function testCreateWithProfile(): void
    {
        $user = UserFactory::createOne(['roles' => ['ROLE_ADMIN']]);

        // Create a profile
        $profile = new Profile();
        $profile->setName('Developer');
        $profile->setDescription('Development profile');

        $em = static::getContainer()->get('doctrine')->getManager();
        $em->persist($profile);
        $em->flush();

        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/admin/onboarding-templates/create');
        $token   = $crawler->filter('input[name="_token"]')->attr('value');

        $this->client->request('POST', '/admin/onboarding-templates/create', [
            '_token'      => $token,
            'name'        => 'Developer Template',
            'description' => 'For developers',
            'profile_id'  => (string) $profile->getId(),
            'tasks'       => [],
        ]);

        $this->assertResponseRedirects();

        // Verify template has profile
        $template = $this->templateRepository->findOneBy(['name' => 'Developer Template']);
        $this->assertNotNull($template);
        $this->assertNotNull($template->getProfile());
        $this->assertEquals('Developer', $template->getProfile()->getName());
    }
}
