<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\Project;
use DateTime;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /**
     * Trouve les devis avec filtres optionnels.
     */
    public function findWithFilters(
        ?Project $project = null,
        ?string $status = null,
        ?string $sortField = 'createdAt',
        ?string $sortDir = 'DESC',
        ?int $limit = null,
        ?int $offset = null
    ): array {
        $qb = $this->createQueryBuilder('o')
            ->leftJoin('o.project', 'p');

        if ($project) {
            $qb->andWhere('o.project = :project')
                ->setParameter('project', $project);
        }

        if ($status) {
            $qb->andWhere('o.status = :status')
                ->setParameter('status', $status);
        }

        // Tri sécurisé par liste blanche
        $map = [
            'number'    => 'o.orderNumber',
            'name'      => 'o.name',
            'project'   => 'p.name',
            'status'    => 'o.status',
            'createdAt' => 'o.createdAt',
            'total'     => 'o.totalAmount',
        ];
        $col = $map[$sortField] ?? 'o.createdAt';
        $dir = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';
        $qb->orderBy($col, $dir);

        if ($col !== 'o.createdAt') {
            $qb->addOrderBy('o.createdAt', 'DESC');
        }

        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }
        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    public function countWithFilters(?Project $project = null, ?string $status = null): int
    {
        $qb = $this->createQueryBuilder('o')
            ->select('COUNT(DISTINCT o.id)')
            ->leftJoin('o.project', 'p');

        if ($project) {
            $qb->andWhere('o.project = :project')
                ->setParameter('project', $project);
        }

        if ($status) {
            $qb->andWhere('o.status = :status')
                ->setParameter('status', $status);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Trouve le dernier numéro de devis pour un mois donné.
     */
    public function findLastOrderNumberForMonth(string $year, string $month): ?Order
    {
        return $this->createQueryBuilder('o')
            ->where('o.orderNumber LIKE :pattern')
            ->setParameter('pattern', "D{$year}{$month}%")
            ->orderBy('o.orderNumber', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve les devis par projet.
     */
    public function findByProject(Project $project): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.project = :project')
            ->orderBy('o.createdAt', 'DESC')
            ->setParameter('project', $project)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les devis par statut.
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.status = :status')
            ->orderBy('o.createdAt', 'DESC')
            ->setParameter('status', $status)
            ->getQuery()
            ->getResult();
    }

    /**
     * Charge un devis avec toutes ses relations pour l'affichage.
     */
    public function findOneWithRelations(int $id): ?Order
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.project', 'p')
            ->addSelect('p')
            ->leftJoin('o.sections', 's')
            ->addSelect('s')
            ->leftJoin('s.lines', 'l')
            ->addSelect('l')
            ->leftJoin('o.paymentSchedules', 'ps')
            ->addSelect('ps')
            ->where('o.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Précharge les devis avec sections et lignes pour une liste de projets afin d'éviter le N+1.
     */
    public function preloadForProjects(array $projects): void
    {
        if (empty($projects)) {
            return;
        }

        $this->createQueryBuilder('o')
            ->addSelect('s', 'l')
            ->leftJoin('o.sections', 's')
            ->leftJoin('s.lines', 'l')
            ->where('o.project IN (:projects)')
            ->setParameter('projects', $projects)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre de devis par statut.
     */
    public function countByStatus(string $status, ?int $userId = null, ?string $userRole = null): int
    {
        $qb = $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.status = :status')
            ->setParameter('status', $status);

        if ($userId && $userRole) {
            $qb->leftJoin('o.project', 'p');
            if ($userRole === 'commercial') {
                $qb->andWhere('p.keyAccountManager = :user');
            } elseif ($userRole === 'chef_projet') {
                $qb->andWhere('p.projectManager = :user');
            }
            $qb->setParameter('user', $userId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Calcule le CA total par statut.
     */
    public function getTotalAmountByStatus(string $status): float
    {
        $result = $this->createQueryBuilder('o')
            ->select('SUM(o.totalAmount)')
            ->where('o.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result : 0.0;
    }

    /**
     * Obtient les statistiques par statut (count + CA).
     */
    public function getStatsByStatus(?DateTimeInterface $startDate = null, ?DateTimeInterface $endDate = null, ?int $userId = null, ?string $userRole = null): array
    {
        $qb = $this->createQueryBuilder('o')
            ->select('o.status, COUNT(o.id) as count, SUM(o.totalAmount) as total')
            ->groupBy('o.status');

        if ($startDate && $endDate) {
            $qb->andWhere('o.createdAt BETWEEN :start AND :end')
                ->setParameter('start', $startDate)
                ->setParameter('end', $endDate);
        }

        if ($userId && $userRole) {
            $qb->leftJoin('o.project', 'p');
            if ($userRole === 'commercial') {
                $qb->andWhere('p.keyAccountManager = :user');
            } elseif ($userRole === 'chef_projet') {
                $qb->andWhere('p.projectManager = :user');
            }
            $qb->setParameter('user', $userId);
        }

        $results = $qb->getQuery()->getResult();

        $stats = [];
        foreach ($results as $row) {
            $stats[$row['status']] = [
                'count' => (int) $row['count'],
                'total' => $row['total'] ? (float) $row['total'] : 0.0,
            ];
        }

        return $stats;
    }

    /**
     * Calcule le CA signé sur une période.
     * Statuts considérés comme signés: signe, gagne, termine.
     */
    public function getSignedRevenueForPeriod(DateTimeInterface $startDate, DateTimeInterface $endDate, ?int $userId = null, ?string $userRole = null): float
    {
        $qb = $this->createQueryBuilder('o')
            ->select('SUM(o.totalAmount)')
            ->where('o.status IN (:statuses)')
            ->andWhere('o.validatedAt BETWEEN :start AND :end')
            ->setParameter('statuses', ['signe', 'gagne', 'termine'])
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate);

        if ($userId && $userRole) {
            $qb->leftJoin('o.project', 'p');
            if ($userRole === 'commercial') {
                $qb->andWhere('p.keyAccountManager = :user');
            } elseif ($userRole === 'chef_projet') {
                $qb->andWhere('p.projectManager = :user');
            }
            $qb->setParameter('user', $userId);
        }

        $result = $qb->getQuery()->getSingleScalarResult();

        return $result ? (float) $result : 0.0;
    }

    /**
     * Obtient l'évolution mensuelle du CA signé sur une période.
     * Retourne un tableau [month => CA].
     */
    public function getRevenueEvolution(DateTimeInterface $startDate, DateTimeInterface $endDate): array
    {
        $conn = $this->getEntityManager()->getConnection();

        // Support both MySQL and PostgreSQL
        $platform   = $conn->getDatabasePlatform()->getName();
        $dateFormat = match ($platform) {
            'postgresql' => "TO_CHAR(validated_at, 'YYYY-MM')",
            default      => "DATE_FORMAT(validated_at, '%Y-%m')", // MySQL/MariaDB
        };

        $sql = "
            SELECT
                {$dateFormat} as month,
                SUM(total_amount) as total
            FROM orders
            WHERE status IN (:statuses)
            AND validated_at BETWEEN :start AND :end
            GROUP BY month
            ORDER BY month ASC
        ";

        $results = $conn->executeQuery(
            $sql,
            [
                'statuses' => ['signe', 'gagne', 'termine'],
                'start'    => $startDate->format('Y-m-d'),
                'end'      => $endDate->format('Y-m-d'),
            ],
            [
                'statuses' => \Doctrine\DBAL\Connection::PARAM_STR_ARRAY,
            ],
        )->fetchAllAssociative();

        $evolution = [];
        foreach ($results as $row) {
            $evolution[$row['month']] = $row['total'] ? (float) $row['total'] : 0.0;
        }

        return $evolution;
    }

    /**
     * Obtient les devis récents (par défaut 10).
     */
    public function getRecentOrders(int $limit = 10): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.project', 'p')
            ->addSelect('p')
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule le taux de conversion sur une période.
     * Retourne le pourcentage de devis signés par rapport au total de devis créés.
     */
    public function getConversionRate(DateTimeInterface $startDate, DateTimeInterface $endDate, ?int $userId = null, ?string $userRole = null): float
    {
        $qbTotal = $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.createdAt BETWEEN :start AND :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate);

        if ($userId && $userRole) {
            $qbTotal->leftJoin('o.project', 'p');
            if ($userRole === 'commercial') {
                $qbTotal->andWhere('p.keyAccountManager = :user');
            } elseif ($userRole === 'chef_projet') {
                $qbTotal->andWhere('p.projectManager = :user');
            }
            $qbTotal->setParameter('user', $userId);
        }

        $total = $qbTotal->getQuery()->getSingleScalarResult();

        if (!$total || $total == 0) {
            return 0.0;
        }

        $qbSigned = $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.createdAt BETWEEN :start AND :end')
            ->andWhere('o.status IN (:statuses)')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->setParameter('statuses', ['signe', 'gagne', 'termine']);

        if ($userId && $userRole) {
            $qbSigned->leftJoin('o.project', 'p');
            if ($userRole === 'commercial') {
                $qbSigned->andWhere('p.keyAccountManager = :user');
            } elseif ($userRole === 'chef_projet') {
                $qbSigned->andWhere('p.projectManager = :user');
            }
            $qbSigned->setParameter('user', $userId);
        }

        $signed = $qbSigned->getQuery()->getSingleScalarResult();

        return round(($signed / $total) * 100, 2);
    }

    /**
     * Obtient les statistiques comparatives entre deux années.
     */
    public function getYearComparison(int $currentYear, int $previousYear, ?int $userId = null, ?string $userRole = null): array
    {
        $currentStart  = new DateTime("$currentYear-01-01");
        $currentEnd    = new DateTime("$currentYear-12-31");
        $previousStart = new DateTime("$previousYear-01-01");
        $previousEnd   = new DateTime("$previousYear-12-31");

        return [
            'current' => [
                'year'            => $currentYear,
                'revenue'         => $this->getSignedRevenueForPeriod($currentStart, $currentEnd, $userId, $userRole),
                'count'           => $this->countOrdersInPeriod($currentStart, $currentEnd, $userId, $userRole),
                'conversion_rate' => $this->getConversionRate($currentStart, $currentEnd, $userId, $userRole),
            ],
            'previous' => [
                'year'            => $previousYear,
                'revenue'         => $this->getSignedRevenueForPeriod($previousStart, $previousEnd, $userId, $userRole),
                'count'           => $this->countOrdersInPeriod($previousStart, $previousEnd, $userId, $userRole),
                'conversion_rate' => $this->getConversionRate($previousStart, $previousEnd, $userId, $userRole),
            ],
        ];
    }

    /**
     * Compte le nombre de devis créés sur une période.
     */
    public function countOrdersInPeriod(DateTimeInterface $startDate, DateTimeInterface $endDate, ?int $userId = null, ?string $userRole = null): int
    {
        $qb = $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.createdAt BETWEEN :start AND :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate);

        if ($userId && $userRole) {
            $qb->leftJoin('o.project', 'p');
            if ($userRole === 'commercial') {
                $qb->andWhere('p.keyAccountManager = :user');
            } elseif ($userRole === 'chef_projet') {
                $qb->andWhere('p.projectManager = :user');
            }
            $qb->setParameter('user', $userId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Recherche full-text dans les devis.
     *
     * @return Order[]
     */
    public function search(string $query, int $limit = 5): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.client', 'c')
            ->where('o.reference LIKE :query')
            ->orWhere('c.name LIKE :query')
            ->setParameter('query', '%'.$query.'%')
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
