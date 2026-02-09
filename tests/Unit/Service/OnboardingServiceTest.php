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
use App\Security\CompanyContext;
use App\Service\OnboardingService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class OnboardingServiceTest extends TestCase
{
    private \PHPUnit\Framework\MockObject\MockObject $em;
    private \PHPUnit\Framework\MockObject\MockObject $companyContext;
    private \PHPUnit\Framework\MockObject\MockObject $taskRepository;
    private \PHPUnit\Framework\MockObject\MockObject $templateRepository;
    private OnboardingService $service;

    protected function setUp(): void
    {
        $this->em                 = $this->createMock(EntityManagerInterface::class);
        $this->companyContext     = $this->createMock(CompanyContext::class);
        $this->templateRepository = $this->createMock(OnboardingTemplateRepository::class);
        $this->taskRepository     = $this->createMock(OnboardingTaskRepository::class);
        $this->service            = new OnboardingService(
            $this->em,
            $this->companyContext,
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

        $this->templateRepository
            ->expects($this->once())
            ->method('findByProfile')
            ->with($profile)
            ->willReturn($template);

        $this->em
            ->expects($this->exactly(2))
            ->method('persist')
            ->with($this->isInstanceOf(OnboardingTask::class));

        $this->em->expects($this->once())->method('flush');

        $taskCount = $this->service->createOnboardingFromTemplate($contributor, $employmentPeriod);

        $this->assertEquals(2, $taskCount);
    }

    public function testCreateOnboardingFromTemplateWithDefaultTemplate(): void
    {
        $contributor      = new Contributor();
        $employmentPeriod = new EmploymentPeriod();

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

        $this->templateRepository
            ->expects($this->once())
            ->method('findDefault')
            ->willReturn($template);

        $this->em
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(OnboardingTask::class));

        $this->em->expects($this->once())->method('flush');

        $taskCount = $this->service->createOnboardingFromTemplate($contributor, $employmentPeriod);

        $this->assertEquals(1, $taskCount);
    }

    public function testCreateOnboardingFromTemplateNoTemplateFound(): void
    {
        $contributor      = new Contributor();
        $employmentPeriod = new EmploymentPeriod();

        $this->templateRepository
            ->expects($this->once())
            ->method('findDefault')
            ->willReturn(null);

        $this->em->expects($this->never())->method('persist');

        $taskCount = $this->service->createOnboardingFromTemplate($contributor, $employmentPeriod);

        $this->assertEquals(0, $taskCount);
    }

    public function testCompleteTask(): void
    {
        $task = new OnboardingTask();
        $task->setStatus('a_faire');

        $this->assertFalse($task->isCompleted());
        $this->assertNull($task->getCompletedAt());

        $this->em->expects($this->once())->method('flush');

        $this->service->completeTask($task);

        $this->assertTrue($task->isCompleted());
        $this->assertInstanceOf(DateTimeImmutable::class, $task->getCompletedAt());
        $this->assertEquals('termine', $task->getStatus());
    }

    public function testCompleteTaskIdempotent(): void
    {
        $task        = new OnboardingTask();
        $completedAt = new DateTimeImmutable('2024-12-01 10:00:00');
        $task->setStatus('termine');
        $task->setCompletedAt($completedAt);

        $this->em->expects($this->once())->method('flush');

        $this->service->completeTask($task);

        // Should remain completed
        $this->assertTrue($task->isCompleted());
        $this->assertInstanceOf(DateTimeImmutable::class, $task->getCompletedAt());
    }

    public function testCalculateProgress(): void
    {
        $contributor = new Contributor();

        $this->taskRepository
            ->expects($this->once())
            ->method('calculateProgress')
            ->with($contributor)
            ->willReturn(50);

        $progress = $this->service->calculateProgress($contributor);

        $this->assertEquals(50, $progress);
    }

    public function testCreateTemplate(): void
    {
        $name        = 'Developer Template';
        $description = 'For new developers';
        $profileId   = 42;
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

        $this->em
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(
                fn (OnboardingTemplate $template): bool => $template->getName() === $name
                    && $template->getDescription()                              === $description
                    && $template->isActive()                                    === true
                    && count($template->getTasks())                             === 1
                ,
            ));

        $this->em->expects($this->once())->method('flush');

        $template = $this->service->createTemplate($name, $description, $profileId, $tasks);

        $this->assertInstanceOf(OnboardingTemplate::class, $template);
        $this->assertEquals($name, $template->getName());
        $this->assertEquals($description, $template->getDescription());
        $this->assertTrue($template->isActive());
        $this->assertCount(1, $template->getTasks());
    }

    public function testCreateTemplateWithoutProfile(): void
    {
        $name        = 'Default Template';
        $description = 'Default onboarding';
        $tasks       = [];

        $this->em
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(OnboardingTemplate::class));

        $this->em->expects($this->once())->method('flush');

        $template = $this->service->createTemplate($name, $description, null, $tasks);

        $this->assertInstanceOf(OnboardingTemplate::class, $template);
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

        $this->em
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(
                fn (OnboardingTemplate $duplicate): bool => str_contains($duplicate->getName(), 'Copie')
                    && $duplicate->getDescription()  === 'Original description'
                    && $duplicate->isActive()        === true
                    && count($duplicate->getTasks()) === 1
                ,
            ));

        $this->em->expects($this->once())->method('flush');

        $duplicate = $this->service->duplicateTemplate($original, 'Copie de Original Template');

        $this->assertInstanceOf(OnboardingTemplate::class, $duplicate);
        $this->assertNotSame($original, $duplicate);
        $this->assertTrue($duplicate->isActive());
    }

    public function testDuplicateTemplateWithCustomName(): void
    {
        $original = new OnboardingTemplate();
        $original->setName('Original Template');
        $original->setDescription('Description');
        $original->setTasks([]);

        $this->em
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(
                fn (OnboardingTemplate $duplicate): bool => $duplicate->getName() === 'Custom Copy Name',
            ));

        $this->em->expects($this->once())->method('flush');

        $duplicate = $this->service->duplicateTemplate($original, 'Custom Copy Name');

        $this->assertEquals('Custom Copy Name', $duplicate->getName());
    }

    public function testGetTeamStatistics(): void
    {
        $contributorIds = [1, 2, 3];
        $stats          = [
            ['contributor_id' => 1, 'total' => 10, 'completed' => 5, 'progress' => 50, 'overdue' => 1],
            ['contributor_id' => 2, 'total' => 8, 'completed' => 8, 'progress' => 100, 'overdue' => 0],
        ];

        $this->taskRepository
            ->expects($this->once())
            ->method('getTeamStatistics')
            ->with($contributorIds)
            ->willReturn($stats);

        $result = $this->service->getTeamStatistics($contributorIds);

        $this->assertSame($stats, $result);
        $this->assertCount(2, $result);
    }
}
