<?php

namespace App\Repository;

use App\Entity\Contributor;
use App\Entity\Profile;
use App\Entity\User;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Contributor>
 *
 * @method Contributor|null find($id, $lockMode = null, $lockVersion = null)
 * @method Contributor|null findOneBy(array $criteria, array $orderBy = null)
 * @method Contributor[]    findAll()
 * @method Contributor[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ContributorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contributor::class);
    }

    /**
     * Récupère tous les contributeurs actifs.
     */
    public function findActiveContributors(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.active = :active')
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
        return $this->createQueryBuilder('c')
            ->innerJoin('c.profiles', 'p')
            ->where('c.active = :active')
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
        return $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.active = :active')
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
        return $this->createQueryBuilder('c')
            ->leftJoin('c.profiles', 'p')
            ->addSelect('p')
            ->where('c.active = :active')
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
        return $this->createQueryBuilder('c')
            ->where('c.firstName LIKE :query OR c.lastName LIKE :query')
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
        return $this->createQueryBuilder('c')
            ->leftJoin('c.timesheets', 't', 'WITH', 't.date BETWEEN :start AND :end')
            ->addSelect('COALESCE(SUM(t.hours), 0) as totalHours')
            ->where('c.active = :active')
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
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('DISTINCT p')
            ->from('App\Entity\Project', 'p')
            ->innerJoin('p.tasks', 't')
            ->where('t.assignedContributor = :contributor')
            ->andWhere('p.status != :archived')
            ->andWhere('t.active = :active')
            ->setParameter('contributor', $contributor)
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

        // Pour chaque projet, récupérer les tâches assignées au contributeur
        $result = [];
        foreach ($projects as $project) {
            $assignedTasks = $this->getEntityManager()
                ->createQueryBuilder()
                ->select('t')
                ->from('App\Entity\ProjectTask', 't')
                ->where('t.project = :project')
                ->andWhere('t.assignedContributor = :contributor')
                ->andWhere('t.active = :active')
                ->setParameter('project', $project)
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
}
