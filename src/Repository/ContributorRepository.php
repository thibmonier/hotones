<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Contributor;
use App\Entity\Profile;
use App\Entity\User;
use App\Security\CompanyContext;
use DateTime;
use DateTimeInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CompanyAwareRepository<Contributor>
 *
 * @method Contributor|null find($id, $lockMode = null, $lockVersion = null)
 * @method Contributor|null findOneBy(array $criteria, array $orderBy = null)
 * @method Contributor[]    findAll()
 * @method Contributor[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ContributorRepository extends CompanyAwareRepository
{
    public function __construct(ManagerRegistry $registry, CompanyContext $companyContext)
    {
        parent::__construct($registry, Contributor::class, $companyContext);
    }

    /**
     * Récupère tous les contributeurs actifs.
     */
    public function findActiveContributors(): array
    {
        return $this
            ->createCompanyQueryBuilder('c')
            ->andWhere('c.active = :active')
            ->setParameter('active', true)
            ->orderBy('c.lastName', 'ASC')
            ->addOrderBy('c.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les contributeurs actifs pour un profil donné.
     */
    public function findActiveContributorsByProfile(Profile $profile): array
    {
        return $this
            ->createCompanyQueryBuilder('c')
            ->innerJoin('c.profiles', 'p')
            ->andWhere('c.active = :active')
            ->andWhere('p = :profile')
            ->setParameter('active', true)
            ->setParameter('profile', $profile)
            ->orderBy('c.lastName', 'ASC')
            ->addOrderBy('c.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les contributeurs actifs.
     */
    public function countActiveContributors(): int
    {
        return $this
            ->createCompanyQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.active = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Trouve un contributeur par utilisateur associé.
     */
    public function findByUser(User $user): ?Contributor
    {
        return $this->findOneBy(['user' => $user]);
    }

    /**
     * Récupère les contributeurs avec leurs profils.
     */
    public function findWithProfiles(): array
    {
        return $this
            ->createCompanyQueryBuilder('c')
            ->leftJoin('c.profiles', 'p')
            ->addSelect('p')
            ->andWhere('c.active = :active')
            ->setParameter('active', true)
            ->orderBy('c.lastName', 'ASC')
            ->addOrderBy('c.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche de contributeurs par nom.
     */
    public function searchByName(string $query): array
    {
        return $this
            ->createCompanyQueryBuilder('c')
            ->andWhere('c.firstName LIKE :query OR c.lastName LIKE :query')
            ->setParameter('query', '%'.$query.'%')
            ->andWhere('c.active = :active')
            ->setParameter('active', true)
            ->orderBy('c.lastName', 'ASC')
            ->addOrderBy('c.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les contributeurs avec leur nombre d'heures sur une période.
     */
    public function findWithHoursForPeriod(DateTimeInterface $startDate, DateTimeInterface $endDate): array
    {
        return $this
            ->createCompanyQueryBuilder('c')
            ->leftJoin('c.timesheets', 't', 'WITH', 't.date BETWEEN :start AND :end')
            ->addSelect('COALESCE(SUM(t.hours), 0) as totalHours')
            ->andWhere('c.active = :active')
            ->setParameter('active', true)
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->groupBy('c.id')
            ->orderBy('totalHours', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les projets où un contributeur a des tâches assignées.
     */
    public function findProjectsWithAssignedTasks(Contributor $contributor): array
    {
        $company = $this->companyContext->getCurrentCompany();

        return $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('DISTINCT p')
            ->from(\App\Entity\Project::class, 'p')
            ->innerJoin('p.tasks', 't')
            ->where('t.assignedContributor = :contributor')
            ->andWhere('p.company = :company')
            ->andWhere('p.status != :archived')
            ->andWhere('t.active = :active')
            ->setParameter('contributor', $contributor)
            ->setParameter('company', $company)
            ->setParameter('archived', 'archived')
            ->setParameter('active', true)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les projets avec leurs tâches assignées pour un contributeur.
     */
    public function findProjectsWithTasksForContributor(Contributor $contributor): array
    {
        // Récupérer les projets
        $projects = $this->findProjectsWithAssignedTasks($contributor);
        $company  = $this->companyContext->getCurrentCompany();

        // Pour chaque projet, récupérer les tâches assignées au contributeur
        $result = [];
        foreach ($projects as $project) {
            $assignedTasks = $this
                ->getEntityManager()
                ->createQueryBuilder()
                ->select('t')
                ->from(\App\Entity\ProjectTask::class, 't')
                ->where('t.project = :project')
                ->andWhere('t.company = :company')
                ->andWhere('t.assignedContributor = :contributor')
                ->andWhere('t.active = :active')
                ->setParameter('project', $project)
                ->setParameter('company', $company)
                ->setParameter('contributor', $contributor)
                ->setParameter('active', true)
                ->orderBy('t.position', 'ASC')
                ->getQuery()
                ->getResult();

            if (!empty($assignedTasks)) {
                $result[] = [
                    'project' => $project,
                    'tasks'   => $assignedTasks,
                ];
            }
        }

        return $result;
    }

    /**
     * Builds a filtered query for contributors with optional search, active and employment status filters.
     */
    public function buildFilteredQuery(
        string $search = '',
        string $active = 'all',
        string $employmentStatus = 'all',
        string $sort = 'name',
        string $dir = 'ASC',
    ): \Doctrine\ORM\QueryBuilder {
        $qb = $this
            ->createQueryBuilder('c')
            ->leftJoin('c.profiles', 'p')
            ->addSelect('p');

        if ($search) {
            $qb->andWhere('c.firstName LIKE :search OR c.lastName LIKE :search OR c.email LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        if ($active !== 'all') {
            $qb->andWhere('c.active = :active')
                ->setParameter('active', $active === 'active');
        }

        if ($employmentStatus === 'current') {
            $today = new DateTime();
            $qb->innerJoin('c.employmentPeriods', 'ep')
                ->andWhere('ep.startDate <= :today')
                ->andWhere('(ep.endDate IS NULL OR ep.endDate >= :today)')
                ->setParameter('today', $today);
        } elseif ($employmentStatus === 'inactive_employment') {
            $today    = new DateTime();
            $subQuery = $this->getEntityManager()
                ->createQueryBuilder()
                ->select('IDENTITY(ep2.contributor)')
                ->from(\App\Entity\EmploymentPeriod::class, 'ep2')
                ->where('ep2.startDate <= :today')
                ->andWhere('(ep2.endDate IS NULL OR ep2.endDate >= :today)')
                ->getDQL();
            $qb->andWhere($qb->expr()->notIn('c.id', $subQuery))
                ->setParameter('today', $today);
        }

        $map       = ['name' => ['c.lastName', 'c.firstName'], 'email' => ['c.email'], 'active' => ['c.active']];
        $columns   = $map[$sort] ?? ['c.lastName', 'c.firstName'];
        $direction = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';
        $first     = true;
        foreach ($columns as $col) {
            if ($first) {
                $qb->orderBy($col, $direction);
                $first = false;
            } else {
                $qb->addOrderBy($col, $direction);
            }
        }

        return $qb;
    }

    /**
     * Recherche full-text dans les contributeurs.
     *
     * @return Contributor[]
     */
    public function search(string $query, int $limit = 5): array
    {
        return $this
            ->createCompanyQueryBuilder('c')
            ->leftJoin('c.user', 'u')
            ->andWhere('c.firstName LIKE :query OR c.lastName LIKE :query OR u.email LIKE :query')
            ->setParameter('query', '%'.$query.'%')
            ->orderBy('c.lastName', 'ASC')
            ->addOrderBy('c.firstName', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
