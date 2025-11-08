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
     * Agrégats de métriques pour une liste de projets.
     * Retourne un tableau indexé par projectId avec:
     * - total_revenue, total_margin, margin_rate, total_purchases, orders_count, signed_orders_count.
     */
    public function getAggregatedMetricsFor(array $projectIds, array $signedStatuses = ['signed', 'won', 'completed', 'signe', 'gagne', 'termine']): array
    {
        if (empty($projectIds)) {
            return [];
        }

        $qb = $this->createQueryBuilder('p')
            ->select('p.id AS projectId')
            ->leftJoin('p.orders', 'o')
            ->leftJoin('o.sections', 's')
            ->leftJoin('s.lines', 'l')
            ->leftJoin('l.profile', 'prof')
            ->where('p.id IN (:ids)')
            ->setParameter('ids', $projectIds)
            // Totaux
            ->addSelect(
                // CA signé (services + achats attachés + montants fixes/achats directs)
                "COALESCE(SUM(CASE 
                    WHEN o.status IN (:signed) AND l.type = 'service' THEN (COALESCE(l.dailyRate,0) * COALESCE(l.days,0) + COALESCE(l.attachedPurchaseAmount,0))
                    WHEN o.status IN (:signed) AND l.type IN ('purchase','fixed_amount') THEN COALESCE(l.directAmount,0)
                    ELSE 0
                END), 0) AS totalRevenue",
            )
            ->addSelect(
                // Marge brute sur services (CA service - coût estimé), seulement sur les devis signés
                "COALESCE(SUM(CASE 
                    WHEN o.status IN (:signed) AND l.type = 'service' THEN (COALESCE(l.dailyRate,0) * COALESCE(l.days,0) - (COALESCE(l.days,0) * COALESCE(prof.defaultDailyRate,0) * 0.7))
                    ELSE 0
                END), 0) AS totalMargin",
            )
            ->addSelect(
                // Achats attachés aux lignes de service signées
                "COALESCE(SUM(CASE 
                    WHEN o.status IN (:signed) AND l.type = 'service' THEN COALESCE(l.attachedPurchaseAmount,0)
                    ELSE 0
                END), 0) AS totalLinePurchases",
            )
            ->addSelect('COUNT(DISTINCT o.id) AS ordersCount')
            ->addSelect('SUM(CASE WHEN o.status IN (:signed) THEN 1 ELSE 0 END) AS signedOrdersCount')
            ->groupBy('p.id')
            ->setParameter('signed', $signedStatuses);

        $rows = $qb->getQuery()->getArrayResult();

        // Reformater et additionner l'achat projet
        $byId = [];
        foreach ($rows as $r) {
            $pid        = (int) $r['projectId'];
            $byId[$pid] = [
                'total_revenue'       => (string) $r['totalRevenue'],
                'total_margin'        => (string) $r['totalMargin'],
                'total_purchases'     => (string) $r['totalLinePurchases'], // on ajoutera purchasesAmount du projet côté contrôleur
                'orders_count'        => (int) $r['ordersCount'],
                'signed_orders_count' => (int) $r['signedOrdersCount'],
            ];
        }

        return $byId;
    }

    /**
     * Somme globale des achats pour un ensemble de projets (achats projet + achats attachés aux lignes de service).
     * Retourne un string (decimal) totalPurchases.
     */
    public function getTotalPurchasesForProjects(array $projectIds): string
    {
        if (empty($projectIds)) {
            return '0';
        }

        // Achats au niveau projet
        $qb1 = $this->createQueryBuilder('p')
            ->select('COALESCE(SUM(p.purchasesAmount), 0) AS totalProjectPurchases')
            ->where('p.id IN (:ids)')
            ->setParameter('ids', $projectIds);
        $row1             = $qb1->getQuery()->getSingleResult();
        $projectPurchases = (string) ($row1['totalProjectPurchases'] ?? '0');

        // Achats attachés aux lignes de service
        $qb2 = $this->createQueryBuilder('p')
            ->select("COALESCE(SUM(CASE WHEN l.type = 'service' THEN COALESCE(l.attachedPurchaseAmount,0) ELSE 0 END), 0) AS totalLinePurchases")
            ->leftJoin('p.orders', 'o')
            ->leftJoin('o.sections', 's')
            ->leftJoin('s.lines', 'l')
            ->where('p.id IN (:ids)')
            ->setParameter('ids', $projectIds);
        $row2          = $qb2->getQuery()->getSingleResult();
        $linePurchases = (string) ($row2['totalLinePurchases'] ?? '0');

        return bcadd($projectPurchases, $linePurchases, 2);
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
     * Charge un projet avec toutes ses relations pour l'affichage.
     */
    public function findOneWithRelations(int $id): ?Project
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.client', 'c')
            ->addSelect('c')
            ->leftJoin('p.serviceCategory', 'sc')
            ->addSelect('sc')
            ->leftJoin('p.technologies', 't')
            ->addSelect('t')
            ->leftJoin('p.orders', 'o')
            ->addSelect('o')
            ->leftJoin('p.keyAccountManager', 'kam')
            ->addSelect('kam')
            ->leftJoin('p.projectManager', 'pm')
            ->addSelect('pm')
            ->leftJoin('p.projectDirector', 'pd')
            ->addSelect('pd')
            ->leftJoin('p.salesPerson', 'sp')
            ->addSelect('sp')
            ->where('p.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Recherche de projets par nom ou client.
     */
    public function searchProjects(string $query): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.client', 'c')
            ->addSelect('c')
            ->where('p.name LIKE :query OR CONCAT(c.firstName, \' \', c.lastName) LIKE :query')
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
            ->leftJoin('p.client', 'c')
            ->addSelect('c')
            ->leftJoin('p.serviceCategory', 'sc')
            ->addSelect('sc')
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

        return array_values(array_filter(array_map(fn ($r) => $r['type'], $rows)));
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

        return array_values(array_filter(array_map(fn ($r) => $r['status'], $rows)));
    }
}
