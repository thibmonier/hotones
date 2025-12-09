<?php

namespace App\Repository;

use App\Entity\Contributor;
use App\Entity\ExpenseReport;
use App\Entity\Order;
use App\Entity\Project;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ExpenseReport>
 */
class ExpenseReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExpenseReport::class);
    }

    /**
     * Trouve les notes de frais d'un contributeur avec filtres optionnels.
     *
     * @param array<string, mixed> $filters
     *
     * @return array<ExpenseReport>
     */
    public function findByContributor(Contributor $contributor, array $filters = []): array
    {
        $qb = $this->createQueryBuilder('e')
            ->where('e.contributor = :contributor')
            ->setParameter('contributor', $contributor)
            ->orderBy('e.expenseDate', 'DESC')
            ->addOrderBy('e.createdAt', 'DESC');

        // Filtre par statut
        if (isset($filters['status']) && $filters['status']) {
            $qb->andWhere('e.status = :status')
                ->setParameter('status', $filters['status']);
        }

        // Filtre par catégorie
        if (isset($filters['category']) && $filters['category']) {
            $qb->andWhere('e.category = :category')
                ->setParameter('category', $filters['category']);
        }

        // Filtre par projet
        if (isset($filters['project']) && $filters['project'] instanceof Project) {
            $qb->andWhere('e.project = :project')
                ->setParameter('project', $filters['project']);
        }

        // Filtre par période
        if (isset($filters['start_date']) && $filters['start_date'] instanceof DateTimeInterface) {
            $qb->andWhere('e.expenseDate >= :start_date')
                ->setParameter('start_date', $filters['start_date']);
        }

        if (isset($filters['end_date']) && $filters['end_date'] instanceof DateTimeInterface) {
            $qb->andWhere('e.expenseDate <= :end_date')
                ->setParameter('end_date', $filters['end_date']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve toutes les notes de frais en attente de validation.
     *
     * @return array<ExpenseReport>
     */
    public function findPending(): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.status = :status')
            ->setParameter('status', ExpenseReport::STATUS_PENDING)
            ->orderBy('e.expenseDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les notes de frais d'un projet.
     *
     * @return array<ExpenseReport>
     */
    public function findByProject(Project $project): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.project = :project')
            ->setParameter('project', $project)
            ->orderBy('e.expenseDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les notes de frais d'un devis.
     *
     * @return array<ExpenseReport>
     */
    public function findByOrder(Order $order): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.order = :order')
            ->setParameter('order', $order)
            ->orderBy('e.expenseDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule le total des frais par catégorie sur une période.
     *
     * @return array<array{category: string, total: string, count: int}>
     */
    public function calculateTotalByCategory(DateTimeInterface $start, DateTimeInterface $end): array
    {
        $results = $this->createQueryBuilder('e')
            ->select('e.category', 'SUM(e.amountTTC) as total', 'COUNT(e.id) as count')
            ->where('e.expenseDate >= :start')
            ->andWhere('e.expenseDate <= :end')
            ->andWhere('e.status IN (:statuses)')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('statuses', [ExpenseReport::STATUS_VALIDATED, ExpenseReport::STATUS_PAID])
            ->groupBy('e.category')
            ->getQuery()
            ->getResult();

        $totals = [];
        foreach ($results as $result) {
            $totals[] = [
                'category' => $result['category'],
                'total'    => (string) $result['total'],
                'count'    => (int) $result['count'],
            ];
        }

        return $totals;
    }

    /**
     * Calcule le total des frais refacturables pour un devis.
     */
    public function calculateTotalRebillable(Order $order): string
    {
        $result = $this->createQueryBuilder('e')
            ->select('SUM(e.amountTTC) as total')
            ->where('e.order = :order')
            ->andWhere('e.status IN (:statuses)')
            ->setParameter('order', $order)
            ->setParameter('statuses', [ExpenseReport::STATUS_VALIDATED, ExpenseReport::STATUS_PAID])
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (string) $result : '0.00';
    }

    /**
     * Calcule les statistiques globales sur une période.
     *
     * @return array{total: string, validated: string, pending: string, count: int}
     */
    public function calculateStats(DateTimeInterface $start, DateTimeInterface $end): array
    {
        $qb = $this->createQueryBuilder('e')
            ->where('e.expenseDate >= :start')
            ->andWhere('e.expenseDate <= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        // Total TTC
        $total = $qb->select('SUM(e.amountTTC)')
            ->getQuery()
            ->getSingleScalarResult();

        // Total validé (à rembourser)
        $validated = $this->createQueryBuilder('e')
            ->select('SUM(e.amountTTC)')
            ->where('e.expenseDate >= :start')
            ->andWhere('e.expenseDate <= :end')
            ->andWhere('e.status = :status')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('status', ExpenseReport::STATUS_VALIDATED)
            ->getQuery()
            ->getSingleScalarResult();

        // Total en attente
        $pending = $this->createQueryBuilder('e')
            ->select('SUM(e.amountTTC)')
            ->where('e.expenseDate >= :start')
            ->andWhere('e.expenseDate <= :end')
            ->andWhere('e.status = :status')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('status', ExpenseReport::STATUS_PENDING)
            ->getQuery()
            ->getSingleScalarResult();

        // Nombre de frais
        $count = $qb->select('COUNT(e.id)')
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total'     => $total ? (string) $total : '0.00',
            'validated' => $validated ? (string) $validated : '0.00',
            'pending'   => $pending ? (string) $pending : '0.00',
            'count'     => (int) $count,
        ];
    }

    /**
     * Trouve les top contributeurs par montant de frais.
     *
     * @return array<array{contributor: Contributor, total: string, count: int}>
     */
    public function findTopContributors(DateTimeInterface $start, DateTimeInterface $end, int $limit = 5): array
    {
        $results = $this->createQueryBuilder('e')
            ->select('IDENTITY(e.contributor) as contributor_id', 'SUM(e.amountTTC) as total', 'COUNT(e.id) as count')
            ->where('e.expenseDate >= :start')
            ->andWhere('e.expenseDate <= :end')
            ->andWhere('e.status IN (:statuses)')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('statuses', [ExpenseReport::STATUS_VALIDATED, ExpenseReport::STATUS_PAID])
            ->groupBy('e.contributor')
            ->orderBy('total', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $contributorRepo = $this->getEntityManager()->getRepository(Contributor::class);
        $topContributors = [];

        foreach ($results as $result) {
            $contributor = $contributorRepo->find($result['contributor_id']);
            if ($contributor instanceof Contributor) {
                $topContributors[] = [
                    'contributor' => $contributor,
                    'total'       => (string) $result['total'],
                    'count'       => (int) $result['count'],
                ];
            }
        }

        return $topContributors;
    }

    /**
     * Trouve tous les frais avec filtres avancés (pour l'écran comptabilité).
     *
     * @param array<string, mixed> $filters
     *
     * @return array<ExpenseReport>
     */
    public function findAllWithFilters(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.contributor', 'c')
            ->leftJoin('e.project', 'p')
            ->leftJoin('e.order', 'o')
            ->addSelect('c', 'p', 'o')
            ->orderBy('e.expenseDate', 'DESC')
            ->addOrderBy('e.createdAt', 'DESC');

        // Filtre par statut
        if (isset($filters['status']) && $filters['status']) {
            $qb->andWhere('e.status = :status')
                ->setParameter('status', $filters['status']);
        }

        // Filtre par catégorie
        if (isset($filters['category']) && $filters['category']) {
            $qb->andWhere('e.category = :category')
                ->setParameter('category', $filters['category']);
        }

        // Filtre par contributeur
        if (isset($filters['contributor']) && $filters['contributor'] instanceof Contributor) {
            $qb->andWhere('e.contributor = :contributor')
                ->setParameter('contributor', $filters['contributor']);
        }

        // Filtre par projet
        if (isset($filters['project']) && $filters['project'] instanceof Project) {
            $qb->andWhere('e.project = :project')
                ->setParameter('project', $filters['project']);
        }

        // Filtre refacturable
        if (isset($filters['rebillable'])) {
            if ($filters['rebillable']) {
                $qb->andWhere('e.order IS NOT NULL')
                    ->andWhere('o.expensesRebillable = true');
            } else {
                $qb->andWhere('e.order IS NULL OR o.expensesRebillable = false');
            }
        }

        // Filtre par période
        if (isset($filters['start_date']) && $filters['start_date'] instanceof DateTimeInterface) {
            $qb->andWhere('e.expenseDate >= :start_date')
                ->setParameter('start_date', $filters['start_date']);
        }

        if (isset($filters['end_date']) && $filters['end_date'] instanceof DateTimeInterface) {
            $qb->andWhere('e.expenseDate <= :end_date')
                ->setParameter('end_date', $filters['end_date']);
        }

        return $qb->getQuery()->getResult();
    }
}
