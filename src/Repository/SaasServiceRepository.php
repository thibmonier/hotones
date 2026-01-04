<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SaasProvider;
use App\Entity\SaasService;
use App\Security\CompanyContext;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CompanyAwareRepository<SaasService>
 */
class SaasServiceRepository extends CompanyAwareRepository
{
    public function __construct(
        ManagerRegistry $registry,
        CompanyContext $companyContext
    ) {
        parent::__construct($registry, SaasService::class, $companyContext);
    }

    /**
     * Retourne tous les services actifs.
     *
     * @return SaasService[]
     */
    public function findActive(): array
    {
        return $this->createCompanyQueryBuilder('s')
            ->andWhere('s.active = :active')
            ->setParameter('active', true)
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les services d'un fournisseur spécifique.
     *
     * @return SaasService[]
     */
    public function findByProvider(SaasProvider $provider): array
    {
        return $this->createCompanyQueryBuilder('s')
            ->andWhere('s.provider = :provider')
            ->setParameter('provider', $provider)
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les services par catégorie.
     *
     * @return SaasService[]
     */
    public function findByCategory(string $category): array
    {
        return $this->createCompanyQueryBuilder('s')
            ->andWhere('s.category = :category')
            ->setParameter('category', $category)
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche des services par nom (recherche partielle).
     *
     * @return SaasService[]
     */
    public function searchByName(string $search): array
    {
        return $this->createCompanyQueryBuilder('s')
            ->andWhere('s.name LIKE :search')
            ->setParameter('search', '%'.$search.'%')
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne toutes les catégories de services utilisées.
     *
     * @return string[]
     */
    public function findAllCategories(): array
    {
        $result = $this->createCompanyQueryBuilder('s')
            ->select('DISTINCT s.category')
            ->andWhere('s.category IS NOT NULL')
            ->orderBy('s.category', 'ASC')
            ->getQuery()
            ->getScalarResult();

        return array_column($result, 'category');
    }

    /**
     * Compte le nombre d'abonnements par service.
     *
     * @return array<int, array{service: SaasService, subscriptionCount: int}>
     */
    public function getServicesWithSubscriptionCount(): array
    {
        $results = $this->createCompanyQueryBuilder('s')
            ->select('s', 'COUNT(sub.id) as subscriptionCount')
            ->leftJoin('s.subscriptions', 'sub')
            ->groupBy('s.id')
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();

        return array_map(static fn (array $row): array => [
            'service'           => $row[0],
            'subscriptionCount' => (int) $row['subscriptionCount'],
        ], $results);
    }
}
