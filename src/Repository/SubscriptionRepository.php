<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Subscription;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Subscription>
 */
class SubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subscription::class);
    }

    /**
     * @return Subscription[]
     */
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.vendor', 'v')
            ->leftJoin('s.provider', 'p')
            ->addSelect('v', 'p')
            ->where('s.active = :active')
            ->andWhere('s.status = :status')
            ->setParameter('active', true)
            ->setParameter('status', Subscription::STATUS_ACTIVE)
            ->orderBy('v.name', 'ASC')
            ->addOrderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Subscription[]
     */
    public function findAll(): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.vendor', 'v')
            ->leftJoin('s.provider', 'p')
            ->addSelect('v', 'p')
            ->orderBy('v.name', 'ASC')
            ->addOrderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Subscription[]
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.vendor', 'v')
            ->leftJoin('s.provider', 'p')
            ->addSelect('v', 'p')
            ->where('s.status = :status')
            ->setParameter('status', $status)
            ->orderBy('v.name', 'ASC')
            ->addOrderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Subscription[]
     */
    public function findUpcomingRenewals(int $days = 30): array
    {
        $today   = new DateTime();
        $endDate = (clone $today)->modify("+{$days} days");

        return $this->createQueryBuilder('s')
            ->leftJoin('s.vendor', 'v')
            ->leftJoin('s.provider', 'p')
            ->addSelect('v', 'p')
            ->where('s.status = :status')
            ->andWhere('s.nextRenewalDate IS NOT NULL')
            ->andWhere('s.nextRenewalDate BETWEEN :today AND :endDate')
            ->setParameter('status', Subscription::STATUS_ACTIVE)
            ->setParameter('today', $today)
            ->setParameter('endDate', $endDate)
            ->orderBy('s.nextRenewalDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule le coût mensuel total de tous les abonnements actifs.
     */
    public function getTotalMonthlyCost(): float
    {
        $subscriptions = $this->findAllActive();
        $total         = 0.0;

        foreach ($subscriptions as $subscription) {
            $total += $subscription->getMonthlyCost();
        }

        return $total;
    }

    /**
     * Calcule le coût annuel total de tous les abonnements actifs.
     */
    public function getTotalYearlyCost(): float
    {
        $subscriptions = $this->findAllActive();
        $total         = 0.0;

        foreach ($subscriptions as $subscription) {
            $total += $subscription->getYearlyCost();
        }

        return $total;
    }

    /**
     * @return array<string, int>
     */
    public function getCountByStatus(): array
    {
        $qb = $this->createQueryBuilder('s');

        $result = $qb
            ->select('s.status', 'COUNT(s.id) as count')
            ->groupBy('s.status')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($result as $row) {
            $counts[$row['status']] = (int) $row['count'];
        }

        return $counts;
    }

    /**
     * @return array<string, float>
     */
    public function getCostByCategory(): array
    {
        $subscriptions = $this->findAllActive();
        $costs         = [];

        foreach ($subscriptions as $subscription) {
            $category         = $subscription->getCategory() ?? 'Non catégorisé';
            $costs[$category] = ($costs[$category] ?? 0.0) + $subscription->getMonthlyCost();
        }

        arsort($costs);

        return $costs;
    }

    /**
     * @return array<string, float>
     */
    public function getCostByVendor(): array
    {
        $subscriptions = $this->findAllActive();
        $costs         = [];

        foreach ($subscriptions as $subscription) {
            $vendorName         = $subscription->getVendor()?->getName() ?? 'Inconnu';
            $costs[$vendorName] = ($costs[$vendorName] ?? 0.0) + $subscription->getMonthlyCost();
        }

        arsort($costs);

        return $costs;
    }

    /**
     * Compte le nombre d'abonnements actifs.
     */
    public function countActive(): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.active = :active')
            ->andWhere('s.status = :status')
            ->setParameter('active', true)
            ->setParameter('status', Subscription::STATUS_ACTIVE)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Subscription[]
     */
    public function findDueForRenewal(): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.vendor', 'v')
            ->leftJoin('s.provider', 'p')
            ->addSelect('v', 'p')
            ->where('s.status = :status')
            ->andWhere('s.autoRenewal = :autoRenewal')
            ->andWhere('s.nextRenewalDate IS NOT NULL')
            ->andWhere('s.nextRenewalDate <= :today')
            ->setParameter('status', Subscription::STATUS_ACTIVE)
            ->setParameter('autoRenewal', true)
            ->setParameter('today', new DateTime())
            ->orderBy('s.nextRenewalDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<string, int>
     */
    public function getStatsByBillingPeriod(): array
    {
        $qb = $this->createQueryBuilder('s');

        $result = $qb
            ->select('s.billingPeriod', 'COUNT(s.id) as count')
            ->where('s.active = :active')
            ->andWhere('s.status = :status')
            ->setParameter('active', true)
            ->setParameter('status', Subscription::STATUS_ACTIVE)
            ->groupBy('s.billingPeriod')
            ->getQuery()
            ->getResult();

        $stats = [];
        foreach ($result as $row) {
            $stats[$row['billingPeriod']] = (int) $row['count'];
        }

        return $stats;
    }

    /**
     * Alias pour compatibilité avec l'ancien code.
     */
    public function calculateTotalMonthlyCost(): float
    {
        return $this->getTotalMonthlyCost();
    }

    /**
     * Alias pour compatibilité avec l'ancien code.
     */
    public function calculateTotalYearlyCost(): float
    {
        return $this->getTotalYearlyCost();
    }

    /**
     * Alias pour compatibilité avec l'ancien code.
     *
     * @return Subscription[]
     */
    public function findExpiringInDays(int $days): array
    {
        return $this->findUpcomingRenewals($days);
    }

    /**
     * Alias pour compatibilité avec l'ancien code.
     *
     * @return Subscription[]
     */
    public function findActive(): array
    {
        return $this->findAllActive();
    }

    /**
     * Alias pour compatibilité avec l'ancien code.
     *
     * @return array<string, int>
     */
    public function getStatsByStatus(): array
    {
        return $this->getCountByStatus();
    }
}
