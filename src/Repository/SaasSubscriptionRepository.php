<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SaasService;
use App\Entity\SaasSubscription;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SaasSubscription>
 */
class SaasSubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SaasSubscription::class);
    }

    /**
     * Retourne tous les abonnements actifs.
     *
     * @return SaasSubscription[]
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('sub')
            ->where('sub.status = :status')
            ->setParameter('status', SaasSubscription::STATUS_ACTIVE)
            ->orderBy('sub.nextRenewalDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les abonnements par statut.
     *
     * @return SaasSubscription[]
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('sub')
            ->where('sub.status = :status')
            ->setParameter('status', $status)
            ->orderBy('sub.nextRenewalDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les abonnements d'un service spécifique.
     *
     * @return SaasSubscription[]
     */
    public function findByService(SaasService $service): array
    {
        return $this->createQueryBuilder('sub')
            ->where('sub.service = :service')
            ->setParameter('service', $service)
            ->orderBy('sub.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les abonnements qui doivent être renouvelés.
     * (actifs, avec auto-renewal, et dont la date de renouvellement est dépassée).
     *
     * @return SaasSubscription[]
     */
    public function findDueForRenewal(): array
    {
        $today = new DateTime();
        $today->setTime(0, 0, 0);

        return $this->createQueryBuilder('sub')
            ->where('sub.status = :status')
            ->andWhere('sub.autoRenewal = :autoRenewal')
            ->andWhere('sub.nextRenewalDate <= :today')
            ->setParameter('status', SaasSubscription::STATUS_ACTIVE)
            ->setParameter('autoRenewal', true)
            ->setParameter('today', $today)
            ->orderBy('sub.nextRenewalDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les abonnements qui arrivent à échéance dans les N prochains jours.
     *
     * @return SaasSubscription[]
     */
    public function findExpiringInDays(int $days): array
    {
        $today = new DateTime();
        $today->setTime(0, 0, 0);

        $futureDate = clone $today;
        $futureDate->modify("+{$days} days");

        return $this->createQueryBuilder('sub')
            ->where('sub.status = :status')
            ->andWhere('sub.nextRenewalDate BETWEEN :today AND :futureDate')
            ->setParameter('status', SaasSubscription::STATUS_ACTIVE)
            ->setParameter('today', $today)
            ->setParameter('futureDate', $futureDate)
            ->orderBy('sub.nextRenewalDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule le coût mensuel total de tous les abonnements actifs.
     */
    public function calculateTotalMonthlyCost(): float
    {
        $subscriptions = $this->findActive();
        $total         = 0.0;

        foreach ($subscriptions as $subscription) {
            $total += $subscription->getMonthlyCost();
        }

        return $total;
    }

    /**
     * Calcule le coût annuel total de tous les abonnements actifs.
     */
    public function calculateTotalYearlyCost(): float
    {
        $subscriptions = $this->findActive();
        $total         = 0.0;

        foreach ($subscriptions as $subscription) {
            $total += $subscription->getYearlyCost();
        }

        return $total;
    }

    /**
     * Retourne les statistiques des abonnements groupées par statut.
     *
     * @return array<string, int>
     */
    public function getStatsByStatus(): array
    {
        $results = $this->createQueryBuilder('sub')
            ->select('sub.status', 'COUNT(sub.id) as count')
            ->groupBy('sub.status')
            ->getQuery()
            ->getScalarResult();

        $stats = [];
        foreach ($results as $result) {
            $stats[$result['status']] = (int) $result['count'];
        }

        return $stats;
    }

    /**
     * Retourne les statistiques des abonnements groupées par période de facturation.
     *
     * @return array<string, int>
     */
    public function getStatsByBillingPeriod(): array
    {
        $results = $this->createQueryBuilder('sub')
            ->select('sub.billingPeriod', 'COUNT(sub.id) as count')
            ->where('sub.status = :status')
            ->setParameter('status', SaasSubscription::STATUS_ACTIVE)
            ->groupBy('sub.billingPeriod')
            ->getQuery()
            ->getScalarResult();

        $stats = [];
        foreach ($results as $result) {
            $stats[$result['billingPeriod']] = (int) $result['count'];
        }

        return $stats;
    }

    /**
     * Retourne le nombre total d'abonnements actifs.
     */
    public function countActive(): int
    {
        return (int) $this->createQueryBuilder('sub')
            ->select('COUNT(sub.id)')
            ->where('sub.status = :status')
            ->setParameter('status', SaasSubscription::STATUS_ACTIVE)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Recherche des abonnements par nom (recherche dans customName et service.name).
     *
     * @return SaasSubscription[]
     */
    public function searchByName(string $search): array
    {
        return $this->createQueryBuilder('sub')
            ->leftJoin('sub.service', 's')
            ->addSelect('s')
            ->where('sub.customName LIKE :search')
            ->orWhere('s.name LIKE :search')
            ->setParameter('search', '%'.$search.'%')
            ->orderBy('sub.nextRenewalDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
