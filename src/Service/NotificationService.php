<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use App\Enum\NotificationType;
use App\Event\NotificationEvent;
use App\Repository\NotificationPreferenceRepository;
use App\Repository\NotificationRepository;
use App\Security\CompanyContext;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class NotificationService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly NotificationRepository $notificationRepository,
        private readonly NotificationPreferenceRepository $preferenceRepository,
        private readonly CompanyContext $companyContext,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Crée une notification pour un utilisateur.
     */
    public function createNotification(
        User $recipient,
        NotificationType $type,
        string $title,
        string $message,
        ?array $data = null,
        ?string $entityType = null,
        ?int $entityId = null,
    ): Notification {
        $notification = new Notification();
        $notification->setCompany($this->companyContext->getCurrentCompany());
        $notification->setRecipient($recipient);
        $notification->setType($type);
        $notification->setTitle($title);
        $notification->setMessage($message);
        $notification->setData($data);
        $notification->setEntityType($entityType);
        $notification->setEntityId($entityId);

        $this->em->persist($notification);
        $this->em->flush();

        $this->logger->info('Notification created', [
            'notification_id' => $notification->getId(),
            'recipient'       => $recipient->getEmail(),
            'type'            => $type->value,
        ]);

        return $notification;
    }

    /**
     * Crée des notifications à partir d'un événement.
     *
     * @return Notification[]
     */
    public function createFromEvent(NotificationEvent $event): array
    {
        $notifications = [];

        foreach ($event->getRecipients() as $recipient) {
            // Vérifie les préférences de l'utilisateur
            if (!$this->shouldSendInApp($recipient, $event->getType())) {
                continue;
            }

            $notification = $this->createNotification(
                recipient: $recipient,
                type: $event->getType(),
                title: $event->getTitle(),
                message: $event->getMessage(),
                data: $event->getData(),
                entityType: $event->getEntityType(),
                entityId: $event->getEntityId(),
            );

            $notifications[] = $notification;
        }

        return $notifications;
    }

    /**
     * Marque une notification comme lue.
     */
    public function markAsRead(Notification $notification): void
    {
        if (!$notification->isRead()) {
            $notification->markAsRead();
            $this->em->flush();
        }
    }

    /**
     * Marque toutes les notifications d'un utilisateur comme lues.
     */
    public function markAllAsRead(UserInterface $user): int
    {
        return $this->notificationRepository->markAllAsReadForUser($user);
    }

    /**
     * Supprime une notification.
     */
    public function deleteNotification(Notification $notification): void
    {
        $this->em->remove($notification);
        $this->em->flush();
    }

    /**
     * Récupère les notifications non lues d'un utilisateur.
     *
     * @return Notification[]
     */
    public function getUnreadNotifications(UserInterface $user, ?int $limit = null): array
    {
        return $this->notificationRepository->findUnreadByUser($user, $limit);
    }

    /**
     * Compte les notifications non lues d'un utilisateur.
     */
    public function countUnreadNotifications(UserInterface $user): int
    {
        return $this->notificationRepository->countUnreadByUser($user);
    }

    /**
     * Vérifie si l'utilisateur souhaite recevoir des notifications in-app pour ce type d'événement.
     */
    private function shouldSendInApp(User $user, NotificationType $type): bool
    {
        $preference = $this->preferenceRepository->findByUserAndEventType($user, $type);

        // Par défaut, on envoie si pas de préférence définie
        return $preference === null || $preference->isInApp();
    }

    /**
     * Vérifie si l'utilisateur souhaite recevoir des emails pour ce type d'événement.
     */
    public function shouldSendEmail(User $user, NotificationType $type): bool
    {
        $preference = $this->preferenceRepository->findByUserAndEventType($user, $type);

        // Par défaut, on envoie si pas de préférence définie
        return $preference === null || $preference->isEmail();
    }

    /**
     * Vérifie si l'utilisateur souhaite recevoir des webhooks pour ce type d'événement.
     */
    public function shouldSendWebhook(User $user, NotificationType $type): bool
    {
        $preference = $this->preferenceRepository->findByUserAndEventType($user, $type);

        // Par défaut, on n'envoie pas si pas de préférence définie
        return $preference !== null && $preference->isWebhook();
    }

    /**
     * Nettoie les anciennes notifications lues.
     */
    public function cleanupOldNotifications(int $daysOld = 30): int
    {
        $deleted = $this->notificationRepository->deleteOldReadNotifications($daysOld);

        $this->logger->info('Old notifications cleaned up', [
            'deleted_count' => $deleted,
            'days_old'      => $daysOld,
        ]);

        return $deleted;
    }
}
