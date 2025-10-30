<?php

namespace App\Repository;

use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Project>
 *
 * @method Project|null find($id, $lockMode = null, $lockVersion = null)
 * @method Project|null findOneBy(array $criteria, array $orderBy = null)
 * @method Project[]    findAll()
 * @method Project[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    /**
     * Récupère tous les projets triés par nom.
     */
    public function findAllOrderedByName(): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les projets actifs triés par nom.
     */
    public function findActiveOrderedByName(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.status = :status')
            ->setParameter('status', 'active')
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les projets récents (limité).
     */
    public function findRecentProjects(int $limit = 5): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les projets actifs.
     */
    public function countActiveProjects(): int
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.status = :status')
            ->setParameter('status', 'active')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère les projets avec leurs statistiques par statut.
     */
    public function getProjectsByStatus(): array
    {
        $result = $this->createQueryBuilder('p')
            ->select('p.status, COUNT(p.id) as count')
            ->groupBy('p.status')
            ->getQuery()
            ->getResult();

        $stats = [];
        foreach ($result as $row) {
            $stats[$row['status']] = $row['count'];
        }

        return $stats;
    }

    /**
     * Recherche de projets par nom ou client.
     */
    public function searchProjects(string $query): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.name LIKE :query OR p.client LIKE :query')
            ->setParameter('query', '%'.$query.'%')
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
