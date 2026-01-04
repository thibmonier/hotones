<?php

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\User;
use App\Security\CompanyContext;
use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CompanyAwareRepository<Notification>
 */
class NotificationRepository extends CompanyAwareRepository
{
    public function __construct(
        ManagerRegistry $registry,
        CompanyContext $companyContext
    ) {
        parent::__construct($registry, Notification::class, $companyContext);
    }

    /**
     * Récupère les notifications non lues d'un utilisateur.
     *
     * @return Notification[]
     */
    public function findUnreadByUser(User $user, ?int $limit = null): array
    {
        $qb = $this->createCompanyQueryBuilder('n')
            ->andWhere('n.recipient = :user')
            ->andWhere('n.readAt IS NULL')
            ->setParameter('user', $user)
            ->orderBy('n.createdAt', 'DESC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte les notifications non lues d'un utilisateur.
     */
    public function countUnreadByUser(User $user): int
    {
        return (int) $this->createCompanyQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->andWhere('n.recipient = :user')
            ->andWhere('n.readAt IS NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Marque toutes les notifications d'un utilisateur comme lues.
     */
    public function markAllAsReadForUser(User $user): int
    {
        return $this->createCompanyQueryBuilder('n')
            ->update()
            ->set('n.readAt', ':now')
            ->andWhere('n.recipient = :user')
            ->andWhere('n.readAt IS NULL')
            ->setParameter('now', new DateTimeImmutable())
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    /**
     * Supprime les anciennes notifications lues (plus de X jours).
     */
    public function deleteOldReadNotifications(int $daysOld = 30): int
    {
        $date = new DateTimeImmutable("-{$daysOld} days");

        return $this->createCompanyQueryBuilder('n')
            ->delete()
            ->andWhere('n.readAt IS NOT NULL')
            ->andWhere('n.readAt < :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->execute();
    }
}
