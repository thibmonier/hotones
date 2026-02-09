<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Vendor;
use App\Security\CompanyContext;
use Doctrine\Persistence\ManagerRegistry;
use Override;

/**
 * @extends CompanyAwareRepository<Vendor>
 */
class VendorRepository extends CompanyAwareRepository
{
    public function __construct(ManagerRegistry $registry, CompanyContext $companyContext)
    {
        parent::__construct($registry, Vendor::class, $companyContext);
    }

    /**
     * @return Vendor[]
     */
    public function findAllActive(): array
    {
        return $this
            ->createCompanyQueryBuilder('v')
            ->andWhere('v.active = :active')
            ->setParameter('active', true)
            ->orderBy('v.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Vendor>
     */
    #[Override]
    public function findAll(): array
    {
        return $this
            ->createCompanyQueryBuilder('v')
            ->orderBy('v.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
