<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Contributor;
use App\Entity\EmploymentPeriod;
use App\Entity\OnboardingTask;
use App\Entity\OnboardingTemplate;
use App\Entity\Profile;
use App\Repository\OnboardingTaskRepository;
use App\Repository\OnboardingTemplateRepository;
use App\Service\OnboardingService;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class OnboardingServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private OnboardingTaskRepository $taskRepository;
    private OnboardingTemplateRepository $templateRepository;
    private OnboardingService $service;

    protected function setUp(): void
    {
        $this->em                 = $this->createMock(EntityManagerInterface::class);
        $this->templateRepository = $this->createMock(OnboardingTemplateRepository::class);
        $this->taskRepository     = $this->createMock(OnboardingTaskRepository::class);
        $this->service            = new OnboardingService(
            $this->em,
            $this->templateRepository,
            $this->taskRepository,
        );
    }

    public function testCreateOnboardingFromTemplateWithProfileMatch(): void
    {
        $contributor      = new Contributor();
        $employmentPeriod = new EmploymentPeriod();
        $profile          = new Profile();

        // Setup contributor with profile
        $contributor->addProfile($profile);

        // Setup template with tasks
        $template = new OnboardingTemplate();
        $template->setName('Developer Onboarding');
        $template->setTasks([
            [
                'title'            => 'Setup workstation',
                'description'      => 'Install necessary software',
                'type'             => 'action',
                'assigned_to'      => 'contributor',
                'days_after_start' => 0,
                'order'            => 0,
            ],
            [
                'title'            => 'Meet the team',
                'description'      => 'Introduction meeting',
                'type'             => 'meeting',
                'assigned_to'      => 'manager',
                'days_after_start' => 1,
                'order'            => 1,
            ],
        ]);

        $this->templateRepository->expects($this->once())
            ->method('findByProfile')
            ->with($profile)
            ->willReturn($template);

        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Onboarding::class));

        $this->em->expects($this->once())
            ->method('flush');

        $taskCount = $this->service->createOnboardingFromTemplate($contributor, $employmentPeriod);

        $this->assertEquals(2, $taskCount);
    }

    public function testCreateOnboardingFromTemplateWithDefaultTemplate(): void
    {
        $contributor      = new Contributor();
        $employmentPeriod = new EmploymentPeriod();

        // Contributor has no profiles
        $contributor->setProfiles(new ArrayCollection());

        // Default template
        $template = new OnboardingTemplate();
        $template->setName('Default Onboarding');
        $template->setTasks([
            [
                'title'            => 'Welcome task',
                'description'      => 'General welcome',
                'type'             => 'action',
                'assigned_to'      => 'contributor',
                'days_after_start' => 0,
                'order'            => 0,
            ],
        ]);

        $this->templateRepository->expects($this->once())
            ->method('findDefault')
            ->willReturn($template);

        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Onboarding::class));

        $this->em->expects($this->once())
            ->method('flush');

        $taskCount = $this->service->createOnboardingFromTemplate($contributor, $employmentPeriod);

        $this->assertEquals(1, $taskCount);
    }

    public function testCreateOnboardingFromTemplateNoTemplateFound(): void
    {
        $contributor      = new Contributor();
        $employmentPeriod = new EmploymentPeriod();

        $this->templateRepository->expects($this->once())
            ->method('findDefault')
            ->willReturn(null);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                $this->stringContains('No onboarding template found'),
                $this->isType('array'),
            );

        $this->em->expects($this->never())
            ->method('persist');

        $taskCount = $this->service->createOnboardingFromTemplate($contributor, $employmentPeriod);

        $this->assertEquals(0, $taskCount);
    }

    public function testCompleteTask(): void
    {
        $task = new OnboardingTask();
        $task->setStatus('pending');

        $this->assertFalse($task->isCompleted());
        $this->assertNull($task->getCompletedAt());

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->completeTask($task);

        $this->assertTrue($task->isCompleted());
        $this->assertInstanceOf(DateTimeInterface::class, $task->getCompletedAt());
        $this->assertEquals('completed', $task->getStatus());
    }

    public function testCompleteTaskIdempotent(): void
    {
        $task        = new OnboardingTask();
        $completedAt = new DateTime('2024-12-01 10:00:00');
        $task->setStatus('completed');
        $task->setCompletedAt($completedAt);

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->completeTask($task);

        // Should remain completed with same date
        $this->assertTrue($task->isCompleted());
        $this->assertEquals($completedAt, $task->getCompletedAt());
    }

    public function testGetProgress(): void
    {
        $onboarding = new Onboarding();

        // Create tasks with different statuses
        $task1 = new OnboardingTask();
        $task1->setStatus('completed');
        $onboarding->addTask($task1);

        $task2 = new OnboardingTask();
        $task2->setStatus('completed');
        $onboarding->addTask($task2);

        $task3 = new OnboardingTask();
        $task3->setStatus('pending');
        $onboarding->addTask($task3);

        $task4 = new OnboardingTask();
        $task4->setStatus('in_progress');
        $onboarding->addTask($task4);

        $progress = $this->service->getProgress($onboarding);

        // 2 completed out of 4 tasks = 50%
        $this->assertEquals(50, $progress);
    }

    public function testGetProgressNoTasks(): void
    {
        $onboarding = new Onboarding();

        $progress = $this->service->getProgress($onboarding);

        $this->assertEquals(0, $progress);
    }

    public function testGetProgressAllCompleted(): void
    {
        $onboarding = new Onboarding();

        $task1 = new OnboardingTask();
        $task1->setStatus('completed');
        $onboarding->addTask($task1);

        $task2 = new OnboardingTask();
        $task2->setStatus('completed');
        $onboarding->addTask($task2);

        $progress = $this->service->getProgress($onboarding);

        $this->assertEquals(100, $progress);
    }

    public function testCreateTemplate(): void
    {
        $name        = 'Developer Template';
        $description = 'For new developers';
        $profile     = new Profile();
        $tasks       = [
            [
                'title'            => 'Task 1',
                'description'      => 'Description 1',
                'type'             => 'action',
                'assigned_to'      => 'contributor',
                'days_after_start' => 0,
                'order'            => 0,
            ],
        ];

        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (OnboardingTemplate $template) use ($name, $description, $profile) {
                return $template->getName()         === $name
                    && $template->getDescription()  === $description
                    && $template->getProfile()      === $profile
                    && $template->isActive()        === true
                    && count($template->getTasks()) === 1;
            }));

        $this->em->expects($this->once())
            ->method('flush');

        $template = $this->service->createTemplate($name, $description, $tasks, $profile);

        $this->assertInstanceOf(OnboardingTemplate::class, $template);
        $this->assertEquals($name, $template->getName());
        $this->assertEquals($description, $template->getDescription());
        $this->assertSame($profile, $template->getProfile());
        $this->assertTrue($template->isActive());
        $this->assertCount(1, $template->getTasks());
    }

    public function testCreateTemplateWithoutProfile(): void
    {
        $name        = 'Default Template';
        $description = 'Default onboarding';
        $tasks       = [];

        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (OnboardingTemplate $template) {
                return $template->getProfile() === null;
            }));

        $this->em->expects($this->once())
            ->method('flush');

        $template = $this->service->createTemplate($name, $description, $tasks);

        $this->assertNull($template->getProfile());
    }

    public function testDuplicateTemplate(): void
    {
        $original = new OnboardingTemplate();
        $original->setName('Original Template');
        $original->setDescription('Original description');
        $original->setActive(true);
        $original->setTasks([
            [
                'title'            => 'Original Task',
                'description'      => 'Task description',
                'type'             => 'action',
                'assigned_to'      => 'contributor',
                'days_after_start' => 0,
                'order'            => 0,
            ],
        ]);

        $profile = new Profile();
        $original->setProfile($profile);

        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (OnboardingTemplate $duplicate) use ($profile) {
                return $duplicate->getName()         === 'Copy of Original Template'
                    && $duplicate->getDescription()  === 'Original description'
                    && $duplicate->getProfile()      === $profile
                    && $duplicate->isActive()        === false // New template should be inactive
                    && count($duplicate->getTasks()) === 1;
            }));

        $this->em->expects($this->once())
            ->method('flush');

        $duplicate = $this->service->duplicateTemplate($original);

        $this->assertInstanceOf(OnboardingTemplate::class, $duplicate);
        $this->assertNotSame($original, $duplicate);
        $this->assertEquals('Copy of Original Template', $duplicate->getName());
        $this->assertFalse($duplicate->isActive());
    }

    public function testDuplicateTemplateWithCustomName(): void
    {
        $original = new OnboardingTemplate();
        $original->setName('Original Template');
        $original->setDescription('Description');
        $original->setTasks([]);

        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (OnboardingTemplate $duplicate) {
                return $duplicate->getName() === 'Custom Copy Name';
            }));

        $this->em->expects($this->once())
            ->method('flush');

        $duplicate = $this->service->duplicateTemplate($original, 'Custom Copy Name');

        $this->assertEquals('Custom Copy Name', $duplicate->getName());
    }

    public function testGetActiveOnboardings(): void
    {
        $contributor       = new Contributor();
        $activeOnboardings = [
            new Onboarding(),
            new Onboarding(),
        ];

        $this->onboardingRepository->expects($this->once())
            ->method('findActiveForContributor')
            ->with($contributor)
            ->willReturn($activeOnboardings);

        $result = $this->service->getActiveOnboardings($contributor);

        $this->assertSame($activeOnboardings, $result);
        $this->assertCount(2, $result);
    }

    public function testGetPendingTasksForContributor(): void
    {
        $contributor  = new Contributor();
        $pendingTasks = [
            new OnboardingTask(),
            new OnboardingTask(),
            new OnboardingTask(),
        ];

        $this->onboardingRepository->expects($this->once())
            ->method('findPendingTasksForContributor')
            ->with($contributor)
            ->willReturn($pendingTasks);

        $result = $this->service->getPendingTasksForContributor($contributor);

        $this->assertSame($pendingTasks, $result);
        $this->assertCount(3, $result);
    }

    public function testGetTeamOnboardings(): void
    {
        $manager         = new Contributor();
        $teamOnboardings = [
            new Onboarding(),
            new Onboarding(),
        ];

        $this->onboardingRepository->expects($this->once())
            ->method('findForTeam')
            ->with($manager)
            ->willReturn($teamOnboardings);

        $result = $this->service->getTeamOnboardings($manager);

        $this->assertSame($teamOnboardings, $result);
        $this->assertCount(2, $result);
    }
}
