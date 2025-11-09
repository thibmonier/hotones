<?php

namespace App\Repository;

use App\Entity\NotificationPreference;
use App\Entity\User;
use App\Enum\NotificationType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NotificationPreference>
 */
class NotificationPreferenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NotificationPreference::class);
    }

    /**
     * Récupère la préférence d'un utilisateur pour un type d'événement.
     */
    public function findByUserAndEventType(User $user, NotificationType $eventType): ?NotificationPreference
    {
        return $this->findOneBy([
            'user'      => $user,
            'eventType' => $eventType,
        ]);
    }

    /**
     * Récupère toutes les préférences d'un utilisateur, indexées par type d'événement.
     *
     * @return array<string, NotificationPreference>
     */
    public function findByUserIndexedByEventType(User $user): array
    {
        $preferences = $this->findBy(['user' => $user]);
        $indexed     = [];

        foreach ($preferences as $preference) {
            $indexed[$preference->getEventType()->value] = $preference;
        }

        return $indexed;
    }

    /**
     * Crée ou met à jour une préférence.
     */
    public function upsert(User $user, NotificationType $eventType, bool $inApp, bool $email, bool $webhook): NotificationPreference
    {
        $preference = $this->findByUserAndEventType($user, $eventType);

        if ($preference === null) {
            $preference = new NotificationPreference();
            $preference->setUser($user);
            $preference->setEventType($eventType);
        }

        $preference->setInApp($inApp);
        $preference->setEmail($email);
        $preference->setWebhook($webhook);

        $this->getEntityManager()->persist($preference);
        $this->getEntityManager()->flush();

        return $preference;
    }
}
