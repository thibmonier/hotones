<?php

declare(strict_types=1);

namespace App\Infrastructure\Project\Persistence\Doctrine;

use App\Domain\Project\Repository\ConversionRateReadModelRepositoryInterface;
use App\Domain\Project\Service\OrderConversionRecord;
use App\Entity\Order;
use App\Security\CompanyContext;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Doctrine adapter for {@see ConversionRateReadModelRepositoryInterface} (US-115 T-115-02).
 *
 * Multi-tenant via CompanyContext. Pré-filtre coarse :
 *  - statuts contribuant à la conversion (signe / gagne / perdu / abandonne)
 *  - createdAt ∈ [now - 365 j, now] (couvre fenêtres 30/90/365 + tendance)
 *
 * Le calculator filtre ensuite par fenêtre précise.
 * Utilise l'index `idx_order_created_at` existant.
 */
final readonly class DoctrineConversionRateReadModelRepository implements ConversionRateReadModelRepositoryInterface
{
    private const int LOOKBACK_DAYS = 365;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private CompanyContext $companyContext,
    ) {
    }

    public function findConversionRecords(DateTimeImmutable $now): array
    {
        $company = $this->companyContext->getCurrentCompany();
        $lookbackStart = $now->modify(sprintf('-%d days', self::LOOKBACK_DAYS));

        $rows = $this->entityManager->createQueryBuilder()
            ->select('o.status', 'o.createdAt')
            ->from(Order::class, 'o')
            ->where('o.company = :company')
            ->andWhere('o.status IN (:statuses)')
            ->andWhere('o.createdAt >= :lookbackStart')
            ->setParameter('company', $company)
            ->setParameter('statuses', [
                OrderConversionRecord::STATUS_CONVERTED_SIGNED,
                OrderConversionRecord::STATUS_CONVERTED_WON,
                OrderConversionRecord::STATUS_FAILED_LOST,
                OrderConversionRecord::STATUS_FAILED_ABANDONED,
            ])
            ->setParameter('lookbackStart', $lookbackStart)
            ->getQuery()
            ->getArrayResult();

        return array_map(
            static fn (array $row): OrderConversionRecord => new OrderConversionRecord(
                status: (string) $row['status'],
                createdAt: self::toImmutable($row['createdAt']),
            ),
            $rows,
        );
    }

    private static function toImmutable(DateTimeInterface $date): DateTimeImmutable
    {
        return $date instanceof DateTimeImmutable
            ? $date
            : DateTimeImmutable::createFromInterface($date);
    }
}
