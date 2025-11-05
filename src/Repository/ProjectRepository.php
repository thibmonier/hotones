<?php

namespace App\Repository;

use App\Entity\Project;
use DateTimeInterface;
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
            ->leftJoin('p.client', 'c')
            ->addSelect('c')
            ->where('p.name LIKE :query OR c.name LIKE :query')
            ->setParameter('query', '%'.$query.'%')
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Projets "ouverts et actifs" qui intersectent une période.
     * Un projet est considéré dans la période si:
     *  - p.startDate <= end
     *  - et (p.endDate IS NULL ou p.endDate >= start)
     * Et avec statut = 'active'.
     */
    public function findActiveBetweenDates(DateTimeInterface $start, DateTimeInterface $end): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.status = :status')
            ->andWhere('p.startDate IS NULL OR p.startDate <= :end')
            ->andWhere('p.endDate IS NULL OR p.endDate >= :start')
            ->setParameter('status', 'active')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Projets qui intersectent une période avec filtres optionnels.
     */
    public function findBetweenDatesFiltered(
        DateTimeInterface $start,
        DateTimeInterface $end,
        ?string $status = null,
        ?string $projectType = null,
        ?int $technologyId = null,
        ?int $limit = null,
        ?int $offset = null
    ): array {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.technologies', 't')
            ->addSelect('t')
            ->where('p.startDate IS NULL OR p.startDate <= :end')
            ->andWhere('p.endDate IS NULL OR p.endDate >= :start')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('p.name', 'ASC');

        if ($status) {
            $qb->andWhere('p.status = :status')->setParameter('status', $status);
        }
        if ($projectType) {
            $qb->andWhere('p.projectType = :ptype')->setParameter('ptype', $projectType);
        }
        if ($technologyId) {
            $qb->andWhere('t.id = :tech')->setParameter('tech', $technologyId);
        }

        $qb->groupBy('p.id');

        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }
        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte les projets pour cette sélection (DISTINCT p.id).
     */
    public function countBetweenDatesFiltered(
        DateTimeInterface $start,
        DateTimeInterface $end,
        ?string $status = null,
        ?string $projectType = null,
        ?int $technologyId = null
    ): int {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(DISTINCT p.id)')
            ->leftJoin('p.technologies', 't')
            ->where('p.startDate IS NULL OR p.startDate <= :end')
            ->andWhere('p.endDate IS NULL OR p.endDate >= :start')
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        if ($status) {
            $qb->andWhere('p.status = :status')->setParameter('status', $status);
        }
        if ($projectType) {
            $qb->andWhere('p.projectType = :ptype')->setParameter('ptype', $projectType);
        }
        if ($technologyId) {
            $qb->andWhere('t.id = :tech')->setParameter('tech', $technologyId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Liste des types de projet distincts.
     */
    public function getDistinctProjectTypes(): array
    {
        $rows = $this->createQueryBuilder('p')
            ->select('DISTINCT p.projectType AS type')
            ->where('p.projectType IS NOT NULL')
            ->orderBy('p.projectType', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_values(array_filter(array_map(fn($r) => $r['type'], $rows)));
    }

    /**
     * Liste des statuts distincts.
     */
    public function getDistinctStatuses(): array
    {
        $rows = $this->createQueryBuilder('p')
            ->select('DISTINCT p.status AS status')
            ->where('p.status IS NOT NULL')
            ->orderBy('p.status', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_values(array_filter(array_map(fn($r) => $r['status'], $rows)));
    }
}
