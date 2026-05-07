<?php

declare(strict_types=1);

namespace App\Infrastructure\Vacation\Persistence\Doctrine;

use App\Domain\Vacation\Entity\Vacation;
use App\Domain\Vacation\Exception\VacationNotFoundException;
use App\Domain\Vacation\Repository\VacationRepositoryInterface;
use App\Domain\Vacation\ValueObject\VacationId;
use App\Entity\Contributor;
use App\Security\CompanyContext;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Vacation>
 */
class DoctrineVacationRepository extends ServiceEntityRepository implements VacationRepositoryInterface
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly CompanyContext $companyContext,
    ) {
        parent::__construct($registry, Vacation::class);
    }

    public function findById(VacationId $id): Vacation
    {
        $vacation = $this->findByIdOrNull($id);

        if ($vacation === null) {
            throw VacationNotFoundException::withId($id);
        }

        return $vacation;
    }

    public function findByIdOrNull(VacationId $id): ?Vacation
    {
        return $this
            ->createCompanyQueryBuilder('v')
            ->andWhere('v.id = :id')
            ->setParameter('id', $id->getValue())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Vacation[]
     */
    public function findByContributor(Contributor $contributor): array
    {
        return $this
            ->createCompanyQueryBuilder('v')
            ->andWhere('v.contributor = :contributor')
            ->setParameter('contributor', $contributor)
            ->orderBy('v.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Contributor[] $contributors
     *
     * @return Vacation[]
     */
    public function findPendingForContributors(array $contributors): array
    {
        if (empty($contributors)) {
            return [];
        }

        return $this
            ->createCompanyQueryBuilder('v')
            ->leftJoin('v.contributor', 'c')
            ->addSelect('c')
            ->andWhere('v.contributor IN (:contributors)')
            ->andWhere('v.status = :status')
            ->setParameter('contributors', $contributors)
            ->setParameter('status', 'pending')
            ->orderBy('v.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countApprovedDaysBetween(DateTimeInterface $startDate, DateTimeInterface $endDate): float
    {
        $vacations = $this
            ->createCompanyQueryBuilder('v')
            ->andWhere('v.status = :approved')
            ->andWhere('v.dateRange.startDate <= :endDate')
            ->andWhere('v.dateRange.endDate >= :startDate')
            ->setParameter('approved', 'approved')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getResult();

        $totalDays = 0.0;
        foreach ($vacations as $vacation) {
            $start = max($vacation->getStartDate(), $startDate);
            $end = min($vacation->getEndDate(), $endDate);

            $interval = $start->diff($end);
            $days = $interval->days + 1;

            $totalDays += $days;
        }

        return $totalDays;
    }

    public function save(Vacation $vacation): void
    {
        $this->getEntityManager()->persist($vacation);
        $this->getEntityManager()->flush();
    }

    private function createCompanyQueryBuilder(string $alias): QueryBuilder
    {
        $company = $this->companyContext->getCurrentCompany();

        return $this
            ->createQueryBuilder($alias)
            ->andWhere("{$alias}.company = :company")
            ->setParameter('company', $company);
    }
}
