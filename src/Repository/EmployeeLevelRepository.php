<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Company;
use App\Entity\EmployeeLevel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EmployeeLevel>
 *
 * @method EmployeeLevel|null find($id, $lockMode = null, $lockVersion = null)
 * @method EmployeeLevel|null findOneBy(array $criteria, array $orderBy = null)
 * @method EmployeeLevel[]    findAll()
 * @method EmployeeLevel[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EmployeeLevelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmployeeLevel::class);
    }

    /**
     * Trouve tous les niveaux actifs d'une entreprise, triés par niveau.
     *
     * @return EmployeeLevel[]
     */
    public function findActiveByCompany(Company $company): array
    {
        return $this
            ->createQueryBuilder('el')
            ->andWhere('el.company = :company')
            ->andWhere('el.active = :active')
            ->setParameter('company', $company)
            ->setParameter('active', true)
            ->orderBy('el.level', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve tous les niveaux d'une entreprise, triés par niveau.
     *
     * @return EmployeeLevel[]
     */
    public function findByCompany(Company $company): array
    {
        return $this
            ->createQueryBuilder('el')
            ->andWhere('el.company = :company')
            ->setParameter('company', $company)
            ->orderBy('el.level', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve un niveau spécifique pour une entreprise.
     */
    public function findByCompanyAndLevel(Company $company, int $level): ?EmployeeLevel
    {
        return $this
            ->createQueryBuilder('el')
            ->andWhere('el.company = :company')
            ->andWhere('el.level = :level')
            ->setParameter('company', $company)
            ->setParameter('level', $level)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve les niveaux par catégorie (junior, experienced, senior, lead).
     *
     * @return EmployeeLevel[]
     */
    public function findByCompanyAndCategory(Company $company, string $category): array
    {
        $levels = match ($category) {
            EmployeeLevel::CATEGORY_JUNIOR      => [1, 2, 3],
            EmployeeLevel::CATEGORY_EXPERIENCED => [4, 5, 6],
            EmployeeLevel::CATEGORY_SENIOR      => [7, 8, 9],
            EmployeeLevel::CATEGORY_LEAD        => [10, 11, 12],
            default                             => [],
        };

        if (empty($levels)) {
            return [];
        }

        return $this
            ->createQueryBuilder('el')
            ->andWhere('el.company = :company')
            ->andWhere('el.level IN (:levels)')
            ->andWhere('el.active = :active')
            ->setParameter('company', $company)
            ->setParameter('levels', $levels)
            ->setParameter('active', true)
            ->orderBy('el.level', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Suggère un niveau basé sur un salaire annuel.
     */
    public function suggestLevelBySalary(Company $company, float $annualSalary): ?EmployeeLevel
    {
        $levels = $this->findActiveByCompany($company);

        foreach ($levels as $level) {
            if ($level->isSalaryInRange($annualSalary) === true) {
                return $level;
            }
        }

        // Si aucun niveau ne correspond exactement, trouve le plus proche
        $bestMatch = null;
        $minDiff   = PHP_FLOAT_MAX;

        foreach ($levels as $level) {
            if ($level->salaryTarget !== null) {
                $diff = abs($annualSalary - (float) $level->salaryTarget);
                if ($diff < $minDiff) {
                    $minDiff   = $diff;
                    $bestMatch = $level;
                }
            }
        }

        return $bestMatch;
    }

    /**
     * Vérifie si tous les niveaux (1-12) sont définis pour une entreprise.
     */
    public function hasAllLevelsDefined(Company $company): bool
    {
        $count = $this
            ->createQueryBuilder('el')
            ->select('COUNT(el.id)')
            ->andWhere('el.company = :company')
            ->setParameter('company', $company)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count === 12;
    }

    /**
     * Retourne les niveaux manquants pour une entreprise.
     *
     * @return int[]
     */
    public function getMissingLevels(Company $company): array
    {
        $existingLevels = $this
            ->createQueryBuilder('el')
            ->select('el.level')
            ->andWhere('el.company = :company')
            ->setParameter('company', $company)
            ->getQuery()
            ->getSingleColumnResult();

        $allLevels = range(1, 12);

        return array_values(array_diff($allLevels, $existingLevels));
    }
}
