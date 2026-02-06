<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Technology;
use App\Security\CompanyContext;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CompanyAwareRepository<Technology>
 */
class TechnologyRepository extends CompanyAwareRepository
{
    public function __construct(
        ManagerRegistry $registry,
        CompanyContext $companyContext
    ) {
        parent::__construct($registry, Technology::class, $companyContext);
    }

    /**
     * Trouve les technologies utilisées par au moins un contributeur actif.
     *
     * @return Technology[]
     */
    public function findUsedByActiveContributors(): array
    {
        return $this->createCompanyQueryBuilder('t')
            ->innerJoin('t.contributorTechnologies', 'ct')
            ->innerJoin('ct.contributor', 'c')
            ->andWhere('c.active = :active')
            ->setParameter('active', true)
            ->andWhere('t.active = :techActive')
            ->setParameter('techActive', true)
            ->orderBy('t.category', 'ASC')
            ->addOrderBy('t.name', 'ASC')
            ->groupBy('t.id')
            ->getQuery()
            ->getResult();
    }
}
