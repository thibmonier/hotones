<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Project\EventListener;

use App\Application\Project\EventListener\CreateInAppNotificationOnMarginThresholdExceeded;
use App\Domain\Project\Event\MarginThresholdExceededEvent;
use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\Shared\ValueObject\Money;
use App\Entity\Project as FlatProject;
use App\Entity\User;
use App\Enum\NotificationType;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class CreateInAppNotificationOnMarginThresholdExceededTest extends TestCase
{
    public function testNoOpWhenProjectNotFound(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('find')->willReturn(null);

        $notificationService = $this->createMock(NotificationService::class);
        $notificationService->expects(self::never())->method('createNotification');

        $userRepo = $this->createMock(UserRepository::class);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::atLeastOnce())
            ->method('warning')
            ->with(self::stringContains('not found'));

        $handler = new CreateInAppNotificationOnMarginThresholdExceeded(
            $notificationService,
            $em,
            $userRepo,
            $logger,
        );

        $handler($this->makeEvent());
    }

    public function testNoOpWhenNoRecipients(): void
    {
        $project = $this->createMock(FlatProject::class);
        $project->method('getProjectManager')->willReturn(null);
        $project->method('getKeyAccountManager')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('find')->willReturn($project);

        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->method('findByRole')->willReturn([]);

        $notificationService = $this->createMock(NotificationService::class);
        $notificationService->expects(self::never())->method('createNotification');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::atLeastOnce())
            ->method('info')
            ->with(self::stringContains('No recipients'));

        $handler = new CreateInAppNotificationOnMarginThresholdExceeded(
            $notificationService,
            $em,
            $userRepo,
            $logger,
        );

        $handler($this->makeEvent());
    }

    public function testCreatesNotificationForProjectManager(): void
    {
        $pm = $this->createMock(User::class);
        $pm->method('getId')->willReturn(7);

        $project = $this->createMock(FlatProject::class);
        $project->method('getProjectManager')->willReturn($pm);
        $project->method('getKeyAccountManager')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('find')->willReturn($project);

        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->method('findByRole')->willReturn([]);

        $notificationService = $this->createMock(NotificationService::class);
        $notificationService->expects(self::once())
            ->method('createNotification')
            ->with(
                $pm,
                NotificationType::LOW_MARGIN_ALERT,
                self::stringContains('Alerte marge'),
                self::stringContains('Test Project'),
                self::callback(function (array $data): bool {
                    return ($data['margin_percent'] ?? null) === 5.0;
                }),
                'Project',
                self::anything(),
            );

        $logger = $this->createMock(LoggerInterface::class);

        $handler = new CreateInAppNotificationOnMarginThresholdExceeded(
            $notificationService,
            $em,
            $userRepo,
            $logger,
        );

        $handler($this->makeEvent());
    }

    public function testDedupRecipientsByUserId(): void
    {
        // PM = KAM = same user ID 7 → 1 notification (pas 2)
        $sharedUser = $this->createMock(User::class);
        $sharedUser->method('getId')->willReturn(7);

        $project = $this->createMock(FlatProject::class);
        $project->method('getProjectManager')->willReturn($sharedUser);
        $project->method('getKeyAccountManager')->willReturn($sharedUser);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('find')->willReturn($project);

        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->method('findByRole')->willReturn([]);

        $notificationService = $this->createMock(NotificationService::class);
        $notificationService->expects(self::once())->method('createNotification');

        $logger = $this->createMock(LoggerInterface::class);

        $handler = new CreateInAppNotificationOnMarginThresholdExceeded(
            $notificationService,
            $em,
            $userRepo,
            $logger,
        );

        $handler($this->makeEvent());
    }

    private function makeEvent(): MarginThresholdExceededEvent
    {
        return MarginThresholdExceededEvent::create(
            projectId: ProjectId::fromLegacyInt(42),
            projectName: 'Test Project',
            costTotal: Money::fromAmount(9500.00),
            invoicedPaidTotal: Money::fromAmount(10000.00),
            marginPercent: 5.0,
            thresholdPercent: 10.0,
        );
    }
}
