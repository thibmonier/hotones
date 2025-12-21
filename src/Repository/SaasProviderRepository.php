<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SaasProvider;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SaasProvider>
 */
class SaasProviderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SaasProvider::class);
    }

    /**
     * Retourne tous les fournisseurs actifs.
     *
     * @return SaasProvider[]
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.active = :active')
            ->setParameter('active', true)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche des fournisseurs par nom (recherche partielle).
     *
     * @return SaasProvider[]
     */
    public function searchByName(string $search): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.name LIKE :search')
            ->setParameter('search', '%'.$search.'%')
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre de services par fournisseur.
     *
     * @return array<int, array{provider: SaasProvider, serviceCount: int}>
     */
    public function getProvidersWithServiceCount(): array
    {
        return $this->createQueryBuilder('p')
            ->select('p', 'COUNT(s.id) as serviceCount')
            ->leftJoin('p.services', 's')
            ->groupBy('p.id')
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
