<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SaasProvider;
use App\Security\CompanyContext;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CompanyAwareRepository<SaasProvider>
 */
class SaasProviderRepository extends CompanyAwareRepository
{
    public function __construct(
        ManagerRegistry $registry,
        CompanyContext $companyContext
    ) {
        parent::__construct($registry, SaasProvider::class, $companyContext);
    }

    /**
     * Retourne tous les fournisseurs actifs.
     *
     * @return SaasProvider[]
     */
    public function findActive(): array
    {
        return $this->createCompanyQueryBuilder('p')
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
        return $this->createCompanyQueryBuilder('p')
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
        $results = $this->createCompanyQueryBuilder('p')
            ->select('p', 'COUNT(s.id) as serviceCount')
            ->leftJoin('p.services', 's')
            ->groupBy('p.id')
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();

        return array_map(static fn (array $row): array => [
            'provider'     => $row[0],
            'serviceCount' => (int) $row['serviceCount'],
        ], $results);
    }
}
