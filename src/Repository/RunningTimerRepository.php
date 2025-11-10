<?php

namespace App\Repository;

use App\Entity\Contributor;
use App\Entity\RunningTimer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RunningTimer>
 */
class RunningTimerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RunningTimer::class);
    }

    public function findActiveByContributor(Contributor $contributor): ?RunningTimer
    {
        return $this->createQueryBuilder('rt')
            ->where('rt.contributor = :contributor')
            ->andWhere('rt.stoppedAt IS NULL')
            ->setParameter('contributor', $contributor)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
