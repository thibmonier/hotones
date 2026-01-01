<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\AccountDeletionRequest;
use App\Entity\User;
use App\Security\CompanyContext;
use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CompanyAwareRepository<AccountDeletionRequest>
 */
class AccountDeletionRequestRepository extends CompanyAwareRepository
{
    public function __construct(
        ManagerRegistry $registry,
        CompanyContext $companyContext
    ) {
        parent::__construct($registry, AccountDeletionRequest::class, $companyContext);
    }

    /**
     * Récupère la demande de suppression active pour un utilisateur.
     */
    public function findActiveDeletionRequestForUser(User $user): ?AccountDeletionRequest
    {
        return $this->createCompanyQueryBuilder('adr')
            ->where('adr.user = :user')
            ->andWhere('adr.status IN (:statuses)')
            ->setParameter('user', $user)
            ->setParameter('statuses', [
                AccountDeletionRequest::STATUS_PENDING,
                AccountDeletionRequest::STATUS_CONFIRMED,
            ])
            ->orderBy('adr.requestedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère toutes les demandes dont la période de grâce est expirée (prêtes pour suppression).
     */
    public function findDueDeletions(): array
    {
        return $this->createCompanyQueryBuilder('adr')
            ->where('adr.status = :status')
            ->andWhere('adr.scheduledDeletionAt <= :now')
            ->setParameter('status', AccountDeletionRequest::STATUS_CONFIRMED)
            ->setParameter('now', new DateTimeImmutable())
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les demandes en attente de confirmation avec token expiré (48h).
     */
    public function findExpiredPendingRequests(): array
    {
        $expiryThreshold = new DateTimeImmutable('-48 hours');

        return $this->createCompanyQueryBuilder('adr')
            ->where('adr.status = :status')
            ->andWhere('adr.requestedAt < :threshold')
            ->setParameter('status', AccountDeletionRequest::STATUS_PENDING)
            ->setParameter('threshold', $expiryThreshold)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve une demande par son token de confirmation.
     */
    public function findByConfirmationToken(string $token): ?AccountDeletionRequest
    {
        return $this->createCompanyQueryBuilder('adr')
            ->where('adr.confirmationToken = :token')
            ->andWhere('adr.status = :status')
            ->setParameter('token', $token)
            ->setParameter('status', AccountDeletionRequest::STATUS_PENDING)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
