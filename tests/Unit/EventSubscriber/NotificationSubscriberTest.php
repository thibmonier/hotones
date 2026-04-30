<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\Entity\Notification;
use App\Entity\User;
use App\Enum\NotificationType;
use App\Event\NotificationEvent;
use App\EventSubscriber\NotificationSubscriber;
use App\Service\NotificationService;
use App\Tests\Unit\Service\Fixture\TestNotificationEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Unit tests for NotificationSubscriber.
 *
 * Verifies:
 *  - subscribed events list is exhaustive (NotificationEvent base class only)
 *  - on event, delegates to NotificationService::createFromEvent
 *  - errors raised by the service are caught and logged (does not break dispatch)
 *  - log payload contains event_type / recipient count / created count
 */
final class NotificationSubscriberTest extends TestCase
{
    private NotificationService&MockObject $notificationService;
    private LoggerInterface&MockObject $logger;
    private NotificationSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->notificationService = $this->createMock(NotificationService::class);
        $this->logger              = $this->createMock(LoggerInterface::class);

        $this->subscriber = new NotificationSubscriber(
            $this->notificationService,
            $this->logger,
        );
    }

    #[Test]
    public function getSubscribedEventsSubscribesToNotificationEventBaseClass(): void
    {
        self::assertSame(
            [NotificationEvent::class => 'onNotificationEvent'],
            NotificationSubscriber::getSubscribedEvents(),
        );
    }

    #[Test]
    public function onNotificationEventDelegatesToServiceAndLogsSuccess(): void
    {
        $alice = (new User())->setEmail('alice@example.com');
        $bob   = (new User())->setEmail('bob@example.com');
        $event = $this->makeEvent([$alice, $bob]);

        $created = [new Notification(), new Notification()];

        $this->notificationService->expects(self::once())
            ->method('createFromEvent')
            ->with($event)
            ->willReturn($created);

        $this->logger->expects(self::once())
            ->method('info')
            ->with('Notifications created from event', [
                'event_type'            => 'quote_won',
                'recipients_count'      => 2,
                'notifications_created' => 2,
            ]);

        $this->subscriber->onNotificationEvent($event);
    }

    #[Test]
    public function onNotificationEventLogsZeroWhenAllRecipientsFiltered(): void
    {
        $alice = (new User())->setEmail('alice@example.com');
        $event = $this->makeEvent([$alice]);

        $this->notificationService->method('createFromEvent')->willReturn([]);

        $this->logger->expects(self::once())
            ->method('info')
            ->with('Notifications created from event', [
                'event_type'            => 'quote_won',
                'recipients_count'      => 1,
                'notifications_created' => 0,
            ]);

        $this->subscriber->onNotificationEvent($event);
    }

    #[Test]
    public function onNotificationEventSwallowsServiceExceptionsAndLogsError(): void
    {
        $event = $this->makeEvent([(new User())->setEmail('alice@example.com')]);

        $this->notificationService
            ->method('createFromEvent')
            ->willThrowException(new RuntimeException('DB unreachable'));

        $this->logger->expects(self::once())
            ->method('error')
            ->with('Error creating notifications from event', [
                'event_type' => 'quote_won',
                'error'      => 'DB unreachable',
            ]);

        // Must not propagate — event dispatch chain must keep going.
        $this->subscriber->onNotificationEvent($event);
    }

    /**
     * @param User[] $recipients
     */
    private function makeEvent(array $recipients): NotificationEvent
    {
        return new TestNotificationEvent(NotificationType::QUOTE_WON, 'Won', 'Hello', $recipients);
    }
}
