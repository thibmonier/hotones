<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\AiUsageLog;
use App\Entity\Company;
use App\Security\CompanyContext;
use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CompanyAwareRepository<AiUsageLog>
 */
class AiUsageLogRepository extends CompanyAwareRepository
{
    public function __construct(ManagerRegistry $registry, CompanyContext $companyContext)
    {
        parent::__construct($registry, AiUsageLog::class, $companyContext);
    }

    /**
     * Cumul des coûts USD pour la company sur un mois donné.
     */
    public function sumMonthlyCostUsd(Company $company, DateTimeImmutable $monthStart): string
    {
        $monthEnd = $monthStart->modify('first day of next month');

        $result = $this->createQueryBuilder('a')
            ->select('COALESCE(SUM(a.costUsd), 0) AS total')
            ->where('a.company = :company')
            ->andWhere('a.occurredAt >= :start')
            ->andWhere('a.occurredAt < :end')
            ->setParameter('company', $company)
            ->setParameter('start', $monthStart)
            ->setParameter('end', $monthEnd)
            ->getQuery()
            ->getSingleScalarResult();

        return (string) $result;
    }
}
