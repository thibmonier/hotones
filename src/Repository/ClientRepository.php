<?php

namespace App\Repository;

use App\Entity\Client;
use App\Security\CompanyContext;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CompanyAwareRepository<Client>
 */
class ClientRepository extends CompanyAwareRepository
{
    public function __construct(
        ManagerRegistry $registry,
        CompanyContext $companyContext
    ) {
        parent::__construct($registry, Client::class, $companyContext);
    }

    /**
     * @return Client[]
     */
    public function findAllOrderedByName(): array
    {
        return $this->createCompanyQueryBuilder('c')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche full-text dans les clients.
     *
     * @return Client[]
     */
    public function search(string $query, int $limit = 5): array
    {
        return $this->createCompanyQueryBuilder('c')
            ->andWhere('c.name LIKE :query OR c.description LIKE :query')
            ->setParameter('query', '%'.$query.'%')
            ->orderBy('c.name', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
