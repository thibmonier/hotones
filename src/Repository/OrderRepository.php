<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /**
     * Trouve les devis avec filtres optionnels
     */
    public function findWithFilters(?Project $project = null, ?string $status = null): array
    {
        $qb = $this->createQueryBuilder('o')
            ->leftJoin('o.project', 'p')
            ->orderBy('o.createdAt', 'DESC');

        if ($project) {
            $qb->andWhere('o.project = :project')
                ->setParameter('project', $project);
        }

        if ($status) {
            $qb->andWhere('o.status = :status')
                ->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve le dernier numéro de devis pour un mois donné
     */
    public function findLastOrderNumberForMonth(string $year, string $month): ?Order
    {
        return $this->createQueryBuilder('o')
            ->where('o.orderNumber LIKE :pattern')
            ->setParameter('pattern', "D{$year}{$month}%")
            ->orderBy('o.orderNumber', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve les devis par projet
     */
    public function findByProject(Project $project): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.project = :project')
            ->orderBy('o.createdAt', 'DESC')
            ->setParameter('project', $project)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les devis par statut
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.status = :status')
            ->orderBy('o.createdAt', 'DESC')
            ->setParameter('status', $status)
            ->getQuery()
            ->getResult();
    }
}