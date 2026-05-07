<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Company;
use App\Entity\Notification;
use App\Entity\NotificationPreference;
use App\Entity\User;
use App\Enum\NotificationType;
use App\Repository\NotificationPreferenceRepository;
use App\Repository\NotificationRepository;
use App\Security\CompanyContext;
use App\Service\NotificationService;
use App\Tests\Unit\Service\Fixture\TestNotificationEvent;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for NotificationService.
 *
 * Covers:
 *  - createNotification: persistence + flush + log + company assignment
 *  - createFromEvent: per-recipient preference filtering
 *  - markAsRead / markAllAsRead: idempotency + repo delegation
 *  - shouldSendEmail / shouldSendWebhook: default fallbacks
 *  - cleanupOldNotifications: repo delegation + log
 */
#[AllowMockObjectsWithoutExpectations]
final class NotificationServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private NotificationRepository&MockObject $notificationRepository;
    private NotificationPreferenceRepository&Stub $preferenceRepository;
    private CompanyContext&MockObject $companyContext;
    private LoggerInterface&MockObject $logger;
    private NotificationService $service;
    private Company $company;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->notificationRepository = $this->createMock(NotificationRepository::class);
        $this->preferenceRepository = $this->createStub(NotificationPreferenceRepository::class);
        $this->companyContext = $this->createMock(CompanyContext::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->company = new Company();

        $this->service = new NotificationService(
            $this->em,
            $this->notificationRepository,
            $this->preferenceRepository,
            $this->companyContext,
            $this->logger,
        );
    }

    #[Test]
    public function createNotificationPersistsAndFlushesWithCurrentCompany(): void
    {
        $user = $this->createUser('alice@example.com');

        $this->companyContext->expects(self::once())->method('getCurrentCompany')->willReturn($this->company);

        $this->em->expects(self::once())->method('persist')->with(self::isInstanceOf(Notification::class));

        $this->em->expects(self::once())->method('flush');

        $this->logger
            ->expects(self::once())
            ->method('info')
            ->with(
                'Notification created',
                self::callback(
                    static fn (array $ctx): bool =>
                        $ctx['recipient'] === 'alice@example.com'
                        && $ctx['type'] === 'quote_won'
                    ,
                ),
            );

        $notification = $this->service->createNotification(
            recipient: $user,
            type: NotificationType::QUOTE_WON,
            title: 'Devis gagné',
            message: 'Bravo, devis #42 signé.',
            data: ['quoteId' => 42],
            entityType: 'Quote',
            entityId: 42,
        );

        self::assertSame(NotificationType::QUOTE_WON, $notification->getType());
        self::assertSame('Devis gagné', $notification->getTitle());
        self::assertSame('Bravo, devis #42 signé.', $notification->getMessage());
        self::assertSame(['quoteId' => 42], $notification->getData());
        self::assertSame('Quote', $notification->getEntityType());
        self::assertSame(42, $notification->getEntityId());
        self::assertSame($user, $notification->getRecipient());
        self::assertSame($this->company, $notification->getCompany());
    }

    #[Test]
    public function createNotificationAcceptsNullOptionalFields(): void
    {
        $user = $this->createUser('bob@example.com');
        $this->companyContext->method('getCurrentCompany')->willReturn($this->company);

        $notification = $this->service->createNotification(
            recipient: $user,
            type: NotificationType::QUOTE_TO_SIGN,
            title: 'À signer',
            message: 'Devis prêt',
        );

        self::assertNull($notification->getData());
        self::assertNull($notification->getEntityType());
        self::assertNull($notification->getEntityId());
    }

    #[Test]
    public function createFromEventCreatesOneNotificationPerEligibleRecipient(): void
    {
        $alice = $this->createUser('alice@example.com');
        $bob = $this->createUser('bob@example.com');

        $event = new TestNotificationEvent(
            type: NotificationType::QUOTE_WON,
            title: 'Devis gagné',
            message: 'Hello',
            recipients: [$alice, $bob],
            data: null,
            entityType: null,
            entityId: null,
        );

        $this->companyContext->method('getCurrentCompany')->willReturn($this->company);

        // Both recipients have no preference => default true
        $this->preferenceRepository->method('findByUserAndEventType')->willReturn(null);

        $this->em->expects(self::exactly(2))->method('persist');
        $this->em->expects(self::exactly(2))->method('flush');

        $notifications = $this->service->createFromEvent($event);

        self::assertCount(2, $notifications);
        self::assertSame($alice, $notifications[0]->getRecipient());
        self::assertSame($bob, $notifications[1]->getRecipient());
    }

    #[Test]
    public function createFromEventSkipsRecipientWithInAppDisabled(): void
    {
        $alice = $this->createUser('alice@example.com');
        $bob = $this->createUser('bob@example.com');

        $event = new TestNotificationEvent(
            type: NotificationType::QUOTE_WON,
            title: 'Won',
            message: '...',
            recipients: [$alice, $bob],
        );

        $bobPreference = new NotificationPreference()
            ->setUser($bob)
            ->setEventType(NotificationType::QUOTE_WON)
            ->setInApp(false)
            ->setEmail(true)
            ->setWebhook(false);

        $this->preferenceRepository
            ->method('findByUserAndEventType')
            ->willReturnCallback(static fn (User $u): ?NotificationPreference => $u === $bob ? $bobPreference : null);

        $this->companyContext->method('getCurrentCompany')->willReturn($this->company);

        $this->em->expects(self::once())->method('persist');
        $this->em->expects(self::once())->method('flush');

        $notifications = $this->service->createFromEvent($event);

        self::assertCount(1, $notifications);
        self::assertSame($alice, $notifications[0]->getRecipient());
    }

    #[Test]
    public function markAsReadDoesNothingWhenAlreadyRead(): void
    {
        $notification = new Notification()->markAsRead();

        $this->em->expects(self::never())->method('flush');

        $this->service->markAsRead($notification);

        self::assertTrue($notification->isRead());
    }

    #[Test]
    public function markAsReadFlushesWhenUnread(): void
    {
        $notification = new Notification();

        $this->em->expects(self::once())->method('flush');

        $this->service->markAsRead($notification);

        self::assertTrue($notification->isRead());
    }

    #[Test]
    public function markAllAsReadReturnsRepositoryCount(): void
    {
        $user = $this->createUser('alice@example.com');

        $this->notificationRepository
            ->expects(self::once())
            ->method('markAllAsReadForUser')
            ->with($user)
            ->willReturn(7);

        self::assertSame(7, $this->service->markAllAsRead($user));
    }

    #[Test]
    public function deleteNotificationRemovesAndFlushes(): void
    {
        $notification = new Notification();

        $this->em->expects(self::once())->method('remove')->with($notification);
        $this->em->expects(self::once())->method('flush');

        $this->service->deleteNotification($notification);
    }

    #[Test]
    public function getUnreadNotificationsDelegatesToRepository(): void
    {
        $user = $this->createUser('alice@example.com');
        $expected = [new Notification(), new Notification()];

        $this->notificationRepository
            ->expects(self::once())
            ->method('findUnreadByUser')
            ->with($user, 5)
            ->willReturn($expected);

        self::assertSame($expected, $this->service->getUnreadNotifications($user, 5));
    }

    #[Test]
    public function countUnreadNotificationsDelegatesToRepository(): void
    {
        $user = $this->createUser('alice@example.com');

        $this->notificationRepository->expects(self::once())->method('countUnreadByUser')->with($user)->willReturn(3);

        self::assertSame(3, $this->service->countUnreadNotifications($user));
    }

    /**
     * @return iterable<string, array{?NotificationPreference, bool}>
     */
    public static function emailPreferenceProvider(): iterable
    {
        yield 'no preference => default true' => [null, true];
        yield 'preference email=true' => [self::makePref(true, true, false), true];
        yield 'preference email=false' => [self::makePref(true, false, false), false];
    }

    #[Test]
    #[DataProvider('emailPreferenceProvider')]
    public function shouldSendEmailRespectsPreference(?NotificationPreference $preference, bool $expected): void
    {
        $user = $this->createUser('alice@example.com');

        $this->preferenceRepository->method('findByUserAndEventType')->willReturn($preference);

        self::assertSame($expected, $this->service->shouldSendEmail($user, NotificationType::QUOTE_WON));
    }

    /**
     * @return iterable<string, array{?NotificationPreference, bool}>
     */
    public static function webhookPreferenceProvider(): iterable
    {
        yield 'no preference => default false' => [null, false];
        yield 'preference webhook=true' => [self::makePref(true, true, true), true];
        yield 'preference webhook=false' => [self::makePref(true, true, false), false];
    }

    #[Test]
    #[DataProvider('webhookPreferenceProvider')]
    public function shouldSendWebhookDefaultsToFalseWhenNoPreference(
        ?NotificationPreference $preference,
        bool $expected,
    ): void {
        $user = $this->createUser('alice@example.com');

        $this->preferenceRepository->method('findByUserAndEventType')->willReturn($preference);

        self::assertSame($expected, $this->service->shouldSendWebhook($user, NotificationType::QUOTE_WON));
    }

    #[Test]
    public function cleanupOldNotificationsReturnsRepositoryCountAndLogs(): void
    {
        $this->notificationRepository
            ->expects(self::once())
            ->method('deleteOldReadNotifications')
            ->with(45)
            ->willReturn(12);

        $this->logger
            ->expects(self::once())
            ->method('info')
            ->with('Old notifications cleaned up', ['deleted_count' => 12, 'days_old' => 45]);

        self::assertSame(12, $this->service->cleanupOldNotifications(45));
    }

    private function createUser(string $email): User
    {
        return new User()->setEmail($email);
    }

    private static function makePref(bool $inApp, bool $email, bool $webhook): NotificationPreference
    {
        return new NotificationPreference()
            ->setEventType(NotificationType::QUOTE_WON)
            ->setInApp($inApp)
            ->setEmail($email)
            ->setWebhook($webhook);
    }
}
