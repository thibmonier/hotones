<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\CookieConsent;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CookieConsent>
 */
class CookieConsentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CookieConsent::class);
    }

    /**
     * Récupère le dernier consentement valide pour un utilisateur.
     */
    public function findLatestValidConsentForUser(?User $user): ?CookieConsent
    {
        if (!$user) {
            return null;
        }

        return $this->createQueryBuilder('c')
            ->where('c.user = :user')
            ->andWhere('c.expiresAt > :now')
            ->setParameter('user', $user)
            ->setParameter('now', new DateTimeImmutable())
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère tous les consentements expirés pour nettoyage.
     */
    public function findExpiredConsents(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.expiresAt < :now')
            ->setParameter('now', new DateTimeImmutable())
            ->getQuery()
            ->getResult();
    }

    /**
     * Supprime les consentements expirés depuis plus de 30 jours.
     */
    public function deleteOldExpiredConsents(): int
    {
        $threshold = new DateTimeImmutable('-30 days');

        return $this->createQueryBuilder('c')
            ->delete()
            ->where('c.expiresAt < :threshold')
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->execute();
    }

    /**
     * Compte le nombre de consentements par type de cookie.
     */
    public function getConsentStatistics(): array
    {
        $qb = $this->createQueryBuilder('c')
            ->select(
                'COUNT(c.id) as total',
                'SUM(CASE WHEN c.functional = true THEN 1 ELSE 0 END) as functional',
                'SUM(CASE WHEN c.analytics = true THEN 1 ELSE 0 END) as analytics',
            )
            ->where('c.expiresAt > :now')
            ->setParameter('now', new DateTimeImmutable());

        return $qb->getQuery()->getSingleResult();
    }
}
