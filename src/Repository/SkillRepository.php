<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Skill;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Skill>
 */
class SkillRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Skill::class);
    }

    /**
     * Trouve toutes les compétences actives.
     *
     * @return Skill[]
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.active = :active')
            ->setParameter('active', true)
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les compétences par catégorie.
     *
     * @return Skill[]
     */
    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.category = :category')
            ->andWhere('s.active = :active')
            ->setParameter('category', $category)
            ->setParameter('active', true)
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les compétences par catégorie.
     *
     * @return array<string, int>
     */
    public function countByCategory(): array
    {
        $results = $this->createQueryBuilder('s')
            ->select('s.category, COUNT(s.id) as count')
            ->where('s.active = :active')
            ->setParameter('active', true)
            ->groupBy('s.category')
            ->getQuery()
            ->getResult();

        $countByCategory = [];
        foreach ($results as $result) {
            $countByCategory[$result['category']] = (int) $result['count'];
        }

        return $countByCategory;
    }

    /**
     * Recherche des compétences par nom.
     *
     * @return Skill[]
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.name LIKE :query')
            ->orWhere('s.description LIKE :query')
            ->setParameter('query', '%'.$query.'%')
            ->andWhere('s.active = :active')
            ->setParameter('active', true)
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les compétences avec le nombre de contributeurs qui les possèdent.
     *
     * @return array<int, array{skill: Skill, contributorCount: int}>
     */
    public function findWithContributorCount(): array
    {
        $results = $this->createQueryBuilder('s')
            ->leftJoin('s.contributorSkills', 'cs')
            ->select('s', 'COUNT(DISTINCT cs.contributor) as contributorCount')
            ->where('s.active = :active')
            ->setParameter('active', true)
            ->groupBy('s.id')
            ->orderBy('contributorCount', 'DESC')
            ->addOrderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();

        // Restructurer pour avoir skill et count séparés
        $formatted = [];
        foreach ($results as $result) {
            $formatted[] = [
                'skill'            => $result[0],
                'contributorCount' => (int) $result['contributorCount'],
            ];
        }

        return $formatted;
    }

    /**
     * Trouve les compétences les plus demandées (avec le plus de contributeurs).
     *
     * @return Skill[]
     */
    public function findMostPopular(int $limit = 10): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.contributorSkills', 'cs')
            ->select('s', 'COUNT(DISTINCT cs.contributor) as HIDDEN contributorCount')
            ->where('s.active = :active')
            ->setParameter('active', true)
            ->groupBy('s.id')
            ->orderBy('contributorCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
