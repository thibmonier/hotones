<?php

namespace App\EventSubscriber;

use App\Event\NotificationEvent;
use App\Service\NotificationService;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Écoute tous les événements de notification et crée les notifications in-app.
 */
class NotificationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly LoggerInterface $logger
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        // On s'abonne à tous les événements qui héritent de NotificationEvent
        return [
            NotificationEvent::class => 'onNotificationEvent',
        ];
    }

    /**
     * Gère tous les événements de notification.
     */
    public function onNotificationEvent(NotificationEvent $event): void
    {
        try {
            // Créer les notifications in-app
            $notifications = $this->notificationService->createFromEvent($event);

            $this->logger->info('Notifications created from event', [
                'event_type'            => $event->getType()->value,
                'recipients_count'      => count($event->getRecipients()),
                'notifications_created' => count($notifications),
            ]);

            // TODO Phase 4 : Envoyer les emails et webhooks de manière asynchrone via Messenger
            // foreach ($event->getRecipients() as $recipient) {
            //     if ($this->notificationService->shouldSendEmail($recipient, $event->getType())) {
            //         $this->messageBus->dispatch(new SendEmailNotificationMessage($notification->getId()));
            //     }
            //     if ($this->notificationService->shouldSendWebhook($recipient, $event->getType())) {
            //         $this->messageBus->dispatch(new SendWebhookNotificationMessage($notification->getId()));
            //     }
            // }
        } catch (Exception $e) {
            $this->logger->error('Error creating notifications from event', [
                'event_type' => $event->getType()->value,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
