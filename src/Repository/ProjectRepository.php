<?php

namespace App\Repository;

use App\Entity\Project;
use App\Security\CompanyContext;
use DateTimeInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CompanyAwareRepository<Project>
 *
 * @method Project|null find($id, $lockMode = null, $lockVersion = null)
 * @method Project|null findOneBy(array $criteria, array $orderBy = null)
 * @method Project[]    findAll()
 * @method Project[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectRepository extends CompanyAwareRepository
{
    public function __construct(
        ManagerRegistry $registry,
        CompanyContext $companyContext
    ) {
        parent::__construct($registry, Project::class, $companyContext);
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

        $qb = $this->createCompanyQueryBuilder('p')
            ->select('p.id AS projectId')
            ->leftJoin('p.orders', 'o')
            ->leftJoin('o.sections', 's')
            ->leftJoin('s.lines', 'l')
            ->leftJoin('l.profile', 'prof')
            ->andWhere('p.id IN (:ids)')
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
        $qb1 = $this->createCompanyQueryBuilder('p')
            ->select('COALESCE(SUM(p.purchasesAmount), 0) AS totalProjectPurchases')
            ->andWhere('p.id IN (:ids)')
            ->setParameter('ids', $projectIds);
        $row1             = $qb1->getQuery()->getSingleResult();
        $projectPurchases = (string) ($row1['totalProjectPurchases'] ?? '0');

        // Achats attachés aux lignes de service
        $qb2 = $this->createCompanyQueryBuilder('p')
            ->select("COALESCE(SUM(CASE WHEN l.type = 'service' THEN COALESCE(l.attachedPurchaseAmount,0) ELSE 0 END), 0) AS totalLinePurchases")
            ->leftJoin('p.orders', 'o')
            ->leftJoin('o.sections', 's')
            ->leftJoin('s.lines', 'l')
            ->andWhere('p.id IN (:ids)')
            ->setParameter('ids', $projectIds);
        $row2          = $qb2->getQuery()->getSingleResult();
        $linePurchases = (string) ($row2['totalLinePurchases'] ?? '0');

        return bcadd($projectPurchases, $linePurchases, 2);
    }

    /**
     * Calcule le CA total de tous les projets (devis signés uniquement) en une seule requête.
     * Retourne un string (decimal).
     */
    public function getTotalRevenue(): string
    {
        $validStatuses = ['signe', 'gagne', 'termine', 'signed', 'won', 'completed'];

        $result = $this->createCompanyQueryBuilder('p')
            ->select('COALESCE(SUM(o.totalAmount), 0) AS total')
            ->leftJoin('p.orders', 'o')
            ->andWhere('o.status IN (:validStatuses)')
            ->setParameter('validStatuses', $validStatuses)
            ->getQuery()
            ->getSingleScalarResult();

        return (string) ($result ?? '0');
    }

    /**
     * Récupère tous les projets triés par nom.
     * Optimisé avec eager loading des relations client et serviceCategory.
     */
    public function findAllOrderedByName(): array
    {
        return $this->createCompanyQueryBuilder('p')
            ->leftJoin('p.client', 'c')
            ->addSelect('c')
            ->leftJoin('p.serviceCategory', 'sc')
            ->addSelect('sc')
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les projets actifs triés par nom.
     * Optimisé avec eager loading des relations client et serviceCategory.
     */
    public function findActiveOrderedByName(): array
    {
        return $this->createCompanyQueryBuilder('p')
            ->leftJoin('p.client', 'c')
            ->addSelect('c')
            ->leftJoin('p.serviceCategory', 'sc')
            ->addSelect('sc')
            ->andWhere('p.status = :status')
            ->setParameter('status', 'active')
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les projets récents (limité) avec leurs relations.
     * Optimisé pour éviter les N+1 queries.
     */
    public function findRecentProjects(int $limit = 5): array
    {
        return $this->createCompanyQueryBuilder('p')
            ->leftJoin('p.client', 'c')
            ->addSelect('c')
            ->leftJoin('p.projectManager', 'pm')
            ->addSelect('pm')
            ->leftJoin('p.serviceCategory', 'sc')
            ->addSelect('sc')
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
        return $this->createCompanyQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.status = :status')
            ->setParameter('status', 'active')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère les projets avec leurs statistiques par statut.
     */
    public function getProjectsByStatus(): array
    {
        $result = $this->createCompanyQueryBuilder('p')
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
        return $this->createCompanyQueryBuilder('p')
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
            ->andWhere('p.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Recherche de projets par nom ou client.
     */
    public function searchProjects(string $query): array
    {
        return $this->createCompanyQueryBuilder('p')
            ->leftJoin('p.client', 'c')
            ->addSelect('c')
            ->andWhere('p.name LIKE :query OR c.name LIKE :query')
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
        return $this->createCompanyQueryBuilder('p')
            ->andWhere('p.status = :status')
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
        ?string $sortField = 'name',
        ?string $sortDir = 'ASC',
        ?int $limit = null,
        ?int $offset = null,
        ?bool $isInternal = null,
        ?int $projectManagerId = null,
        ?int $salesPersonId = null,
        ?int $serviceCategoryId = null,
        ?string $search = null
    ): array {
        $qb = $this->createCompanyQueryBuilder('p')
            ->leftJoin('p.technologies', 't')
            ->addSelect('t')
            ->leftJoin('p.client', 'c')
            ->addSelect('c')
            ->leftJoin('p.serviceCategory', 'sc')
            ->addSelect('sc')
            ->leftJoin('p.projectManager', 'pm')
            ->addSelect('pm')
            ->leftJoin('p.salesPerson', 'sp')
            ->addSelect('sp')
            ->andWhere('p.startDate IS NULL OR p.startDate <= :end')
            ->andWhere('p.endDate IS NULL OR p.endDate >= :start')
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        // Tri sécurisé par liste blanche
        $map = [
            'name'   => 'p.name',
            'client' => 'c.name',
            'status' => 'p.status',
            'type'   => 'p.projectType',
            'start'  => 'p.startDate',
            'end'    => 'p.endDate',
        ];
        $col = $map[$sortField] ?? 'p.name';
        $dir = strtoupper($sortDir) === 'DESC' ? 'DESC' : 'ASC';
        $qb->orderBy($col, $dir);

        if ($status) {
            $qb->andWhere('p.status = :status')->setParameter('status', $status);
        }
        if ($projectType) {
            $qb->andWhere('p.projectType = :ptype')->setParameter('ptype', $projectType);
        }
        if ($technologyId) {
            $qb->andWhere('t.id = :tech')->setParameter('tech', $technologyId);
        }
        if ($isInternal !== null) {
            $qb->andWhere('p.isInternal = :internal')->setParameter('internal', $isInternal);
        }
        if ($projectManagerId) {
            $qb->andWhere('pm.id = :pmId')->setParameter('pmId', $projectManagerId);
        }
        if ($salesPersonId) {
            $qb->andWhere('sp.id = :spId')->setParameter('spId', $salesPersonId);
        }
        if ($serviceCategoryId) {
            $qb->andWhere('sc.id = :scId')->setParameter('scId', $serviceCategoryId);
        }
        if ($search) {
            $qb->andWhere('p.name LIKE :search OR p.description LIKE :search OR c.name LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        // No GROUP BY needed: Doctrine's identity map handles entity deduplication automatically
        // when hydrating entities from joins. GROUP BY would require listing all selected columns
        // which is incompatible with ONLY_FULL_GROUP_BY mode.

        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }
        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        // Use Paginator for collection joins to avoid partial hydration (data loss)
        // Technologies is a ManyToMany collection - setMaxResults alone would cause issues
        $paginator = new Paginator($qb, true);

        return iterator_to_array($paginator);
    }

    /**
     * Compte les projets pour cette sélection (DISTINCT p.id).
     */
    public function countBetweenDatesFiltered(
        DateTimeInterface $start,
        DateTimeInterface $end,
        ?string $status = null,
        ?string $projectType = null,
        ?int $technologyId = null,
        ?string $search = null,
        ?int $serviceCategoryId = null
    ): int {
        $qb = $this->createCompanyQueryBuilder('p')
            ->select('COUNT(DISTINCT p.id)')
            ->leftJoin('p.technologies', 't')
            ->leftJoin('p.client', 'c')
            ->andWhere('p.startDate IS NULL OR p.startDate <= :end')
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
        if ($serviceCategoryId) {
            $qb->leftJoin('p.serviceCategory', 'sc');
            $qb->andWhere('sc.id = :scId')->setParameter('scId', $serviceCategoryId);
        }
        if ($search) {
            $qb->andWhere('p.name LIKE :search OR p.description LIKE :search OR c.name LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Liste distincte des Chefs de projet (Users) sur la période.
     * Retourne un tableau de ['id' => int, 'name' => string].
     */
    public function getDistinctProjectManagersBetweenDates(DateTimeInterface $start, DateTimeInterface $end): array
    {
        $rows = $this->createCompanyQueryBuilder('p')
            ->select('pm.id AS id, pm.firstName AS firstName, pm.lastName AS lastName')
            ->leftJoin('p.projectManager', 'pm')
            ->andWhere('pm.id IS NOT NULL')
            ->andWhere('p.startDate IS NULL OR p.startDate <= :end')
            ->andWhere('p.endDate IS NULL OR p.endDate >= :start')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->groupBy('pm.id, pm.firstName, pm.lastName')
            ->orderBy('pm.lastName', 'ASC')
            ->addOrderBy('pm.firstName', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_map(static function (array $r) {
            return [
                'id'   => (int) $r['id'],
                'name' => trim(($r['firstName'] ?? '').' '.($r['lastName'] ?? '')),
            ];
        }, $rows);
    }

    /**
     * Liste distincte des Commerciaux (Users) sur la période.
     */
    public function getDistinctSalesPersonsBetweenDates(DateTimeInterface $start, DateTimeInterface $end): array
    {
        $rows = $this->createCompanyQueryBuilder('p')
            ->select('sp.id AS id, sp.firstName AS firstName, sp.lastName AS lastName')
            ->leftJoin('p.salesPerson', 'sp')
            ->andWhere('sp.id IS NOT NULL')
            ->andWhere('p.startDate IS NULL OR p.startDate <= :end')
            ->andWhere('p.endDate IS NULL OR p.endDate >= :start')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->groupBy('sp.id, sp.firstName, sp.lastName')
            ->orderBy('sp.lastName', 'ASC')
            ->addOrderBy('sp.firstName', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_map(static function (array $r) {
            return [
                'id'   => (int) $r['id'],
                'name' => trim(($r['firstName'] ?? '').' '.($r['lastName'] ?? '')),
            ];
        }, $rows);
    }

    /**
     * Liste distincte des technologies utilisées sur la période.
     */
    public function getDistinctTechnologiesBetweenDates(DateTimeInterface $start, DateTimeInterface $end): array
    {
        $rows = $this->createCompanyQueryBuilder('p')
            ->select('t.id AS id, t.name AS name')
            ->leftJoin('p.technologies', 't')
            ->andWhere('t.id IS NOT NULL')
            ->andWhere('p.startDate IS NULL OR p.startDate <= :end')
            ->andWhere('p.endDate IS NULL OR p.endDate >= :start')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->groupBy('t.id, t.name')
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_map(static function (array $r) {
            return [
                'id'   => (int) $r['id'],
                'name' => (string) ($r['name'] ?? ''),
            ];
        }, $rows);
    }

    /**
     * Liste distincte des catégories de service utilisées sur la période.
     */
    public function getDistinctServiceCategoriesBetweenDates(DateTimeInterface $start, DateTimeInterface $end): array
    {
        $rows = $this->createCompanyQueryBuilder('p')
            ->select('sc.id AS id, sc.name AS name')
            ->leftJoin('p.serviceCategory', 'sc')
            ->andWhere('sc.id IS NOT NULL')
            ->andWhere('p.startDate IS NULL OR p.startDate <= :end')
            ->andWhere('p.endDate IS NULL OR p.endDate >= :start')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->groupBy('sc.id, sc.name')
            ->orderBy('sc.name', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_map(static function (array $r) {
            return [
                'id'   => (int) $r['id'],
                'name' => (string) ($r['name'] ?? ''),
            ];
        }, $rows);
    }

    /**
     * Liste des types de projet distincts.
     */
    public function getDistinctProjectTypes(): array
    {
        $rows = $this->createCompanyQueryBuilder('p')
            ->select('DISTINCT p.projectType AS type')
            ->andWhere('p.projectType IS NOT NULL')
            ->orderBy('p.projectType', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_values(array_filter(array_map(fn ($r) => $r['type'], $rows), fn ($v) => $v !== null && $v !== ''));
    }

    /**
     * Liste des statuts distincts.
     */
    public function getDistinctStatuses(): array
    {
        $rows = $this->createCompanyQueryBuilder('p')
            ->select('DISTINCT p.status AS status')
            ->andWhere('p.status IS NOT NULL')
            ->orderBy('p.status', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_values(array_filter(array_map(fn ($r) => $r['status'], $rows), fn ($v) => $v !== null && $v !== ''));
    }

    /**
     * Recherche full-text dans les projets.
     * Optimisé avec eager loading des relations client, serviceCategory et technologies.
     *
     * @return Project[]
     */
    public function search(string $query, int $limit = 5): array
    {
        $qb = $this->createCompanyQueryBuilder('p')
            ->leftJoin('p.client', 'c')
            ->addSelect('c')
            ->leftJoin('p.serviceCategory', 'sc')
            ->addSelect('sc')
            ->leftJoin('p.technologies', 't')
            ->addSelect('t')
            ->leftJoin('p.projectManager', 'pm')
            ->addSelect('pm')
            ->andWhere('p.name LIKE :query OR p.description LIKE :query OR c.name LIKE :query')
            ->setParameter('query', '%'.$query.'%')
            ->orderBy('p.id', 'DESC')
            ->setMaxResults($limit);

        // Use Paginator for collection joins to avoid partial hydration (data loss)
        // Technologies is a ManyToMany collection - setMaxResults alone would cause issues
        $paginator = new Paginator($qb, true);

        return iterator_to_array($paginator);
    }

    /**
     * Trouve les projets actifs qui sont "à risque".
     * Un projet est à risque si:
     * - Sa marge réelle est < 20%
     * - Le temps passé dépasse le temps vendu/révisé.
     *
     * @return Project[]
     */
    public function findProjectsAtRisk(int $limit = 5): array
    {
        // Cette méthode est complexe à écrire en DQL pur car elle dépend de logiques métier
        // calculées dans l'entité (getRealMarginPercentage, etc.).
        // On va donc récupérer les projets actifs et les filtrer en PHP.
        $activeProjects = $this->findBy(['status' => 'active']);

        $projectsAtRisk = [];
        foreach ($activeProjects as $project) {
            $performance = $project->getPerformanceComparison();
            $marginRate  = (float) $project->getRealMarginPercentage();

            $isOverTime  = bccomp($performance['real_hours'], $performance['target_hours'], 2) > 0;
            $isLowMargin = $marginRate < 20.0;

            if ($isOverTime || $isLowMargin) {
                $projectsAtRisk[] = $project;
            }

            if (count($projectsAtRisk) >= $limit) {
                break;
            }
        }

        return $projectsAtRisk;
    }
}
