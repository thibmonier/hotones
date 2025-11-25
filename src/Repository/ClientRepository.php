<?php

namespace App\Repository;

use App\Entity\Client;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Client>
 */
class ClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Client::class);
    }

    /**
     * @return Client[]
     */
    public function findAllOrderedByName(): array
    {
        return $this->createQueryBuilder('c')
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
        return $this->createQueryBuilder('c')
            ->where('c.name LIKE :query')
            ->orWhere('c.description LIKE :query')
            ->setParameter('query', '%'.$query.'%')
            ->orderBy('c.name', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
