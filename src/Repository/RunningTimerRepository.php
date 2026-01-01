<?php

namespace App\Repository;

use App\Entity\Contributor;
use App\Entity\RunningTimer;
use App\Security\CompanyContext;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CompanyAwareRepository<RunningTimer>
 */
class RunningTimerRepository extends CompanyAwareRepository
{
    public function __construct(
        ManagerRegistry $registry,
        CompanyContext $companyContext
    ) {
        parent::__construct($registry, RunningTimer::class, $companyContext);
    }

    public function findActiveByContributor(Contributor $contributor): ?RunningTimer
    {
        return $this->createCompanyQueryBuilder('rt')
            ->where('rt.contributor = :contributor')
            ->andWhere('rt.stoppedAt IS NULL')
            ->setParameter('contributor', $contributor)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
