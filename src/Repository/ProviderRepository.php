<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Provider;
use App\Security\CompanyContext;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CompanyAwareRepository<Provider>
 */
class ProviderRepository extends CompanyAwareRepository
{
    public function __construct(
        ManagerRegistry $registry,
        CompanyContext $companyContext
    ) {
        parent::__construct($registry, Provider::class, $companyContext);
    }

    /**
     * @return Provider[]
     */
    public function findAllActive(): array
    {
        return $this->createCompanyQueryBuilder('p')
            ->where('p.active = :active')
            ->setParameter('active', true)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Provider>
     */
    public function findAll(): array
    {
        return $this->createCompanyQueryBuilder('p')
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Provider[]
     */
    public function findByType(string $type): array
    {
        return $this->createCompanyQueryBuilder('p')
            ->where('p.type = :type')
            ->andWhere('p.active = :active')
            ->setParameter('type', $type)
            ->setParameter('active', true)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
