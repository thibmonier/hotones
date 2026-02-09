<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\CookieConsent;
use App\Entity\User;
use App\Security\CompanyContext;
use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CompanyAwareRepository<CookieConsent>
 */
class CookieConsentRepository extends CompanyAwareRepository
{
    public function __construct(ManagerRegistry $registry, CompanyContext $companyContext)
    {
        parent::__construct($registry, CookieConsent::class, $companyContext);
    }

    /**
     * Récupère le dernier consentement valide pour un utilisateur.
     */
    public function findLatestValidConsentForUser(?User $user): ?CookieConsent
    {
        if (!$user) {
            return null;
        }

        return $this
            ->createCompanyQueryBuilder('c')
            ->andWhere('c.user = :user')
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
        return $this
            ->createCompanyQueryBuilder('c')
            ->andWhere('c.expiresAt < :now')
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
        $company   = $this->companyContext->getCurrentCompany();

        return $this
            ->createQueryBuilder('c')
            ->delete()
            ->where('c.company = :company')
            ->andWhere('c.expiresAt < :threshold')
            ->setParameter('company', $company)
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->execute();
    }

    /**
     * Compte le nombre de consentements par type de cookie.
     */
    public function getConsentStatistics(): array
    {
        $qb = $this
            ->createCompanyQueryBuilder('c')
            ->select(
                'COUNT(c.id) as total',
                'SUM(CASE WHEN c.functional = true THEN 1 ELSE 0 END) as functional',
                'SUM(CASE WHEN c.analytics = true THEN 1 ELSE 0 END) as analytics',
            )
            ->andWhere('c.expiresAt > :now')
            ->setParameter('now', new DateTimeImmutable());

        return $qb->getQuery()->getSingleResult();
    }
}
