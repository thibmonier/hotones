<?php

declare(strict_types=1);

namespace App\Infrastructure\Project\Persistence\Doctrine;

use App\Domain\Project\Repository\RevenueForecastReadModelRepositoryInterface;
use App\Domain\Project\Service\PipelineOrderRecord;
use App\Entity\Invoice;
use App\Entity\Order;
use App\Security\CompanyContext;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Doctrine adapter for {@see RevenueForecastReadModelRepositoryInterface} (US-114 T-114-02).
 *
 * Multi-tenant via CompanyContext. Pré-filtre coarse (statut + horizon 90 j) ;
 * le {@see \App\Domain\Project\Service\RevenueForecastCalculator} affine
 * sur les fenêtres 30 / 90 j.
 *
 * Exclusion des Orders déjà facturés via sous-requête `Invoice.order`
 * (pas de double comptage forecast / facturé).
 */
final readonly class DoctrineRevenueForecastReadModelRepository implements RevenueForecastReadModelRepositoryInterface
{
    private const MAX_HORIZON_DAYS = 90;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private CompanyContext $companyContext,
    ) {
    }

    public function findPipelineOrders(DateTimeImmutable $now): array
    {
        $company = $this->companyContext->getCurrentCompany();
        $horizonEnd = $now->modify(sprintf('+%d days', self::MAX_HORIZON_DAYS));

        $invoicedOrderIds = $this->entityManager->createQueryBuilder()
            ->select('IDENTITY(inv.order)')
            ->from(Invoice::class, 'inv')
            ->where('inv.company = :company')
            ->andWhere('inv.order IS NOT NULL')
            ->setParameter('company', $company)
            ->getQuery()
            ->getSingleColumnResult();

        $qb = $this->entityManager->createQueryBuilder()
            ->select('o.status', 'o.totalAmount', 'o.validUntil')
            ->from(Order::class, 'o')
            ->where('o.company = :company')
            ->andWhere('o.status IN (:statuses)')
            ->andWhere('o.validUntil IS NOT NULL')
            ->andWhere('o.validUntil >= :now')
            ->andWhere('o.validUntil <= :horizonEnd')
            ->setParameter('company', $company)
            ->setParameter('statuses', [
                PipelineOrderRecord::STATUS_QUOTE,
                PipelineOrderRecord::STATUS_SIGNED,
                PipelineOrderRecord::STATUS_WON,
            ])
            ->setParameter('now', $now)
            ->setParameter('horizonEnd', $horizonEnd);

        if ($invoicedOrderIds !== []) {
            $qb->andWhere('o.id NOT IN (:invoicedOrderIds)')
                ->setParameter('invoicedOrderIds', $invoicedOrderIds);
        }

        $rows = $qb->getQuery()->getArrayResult();

        return array_map(
            static fn (array $row): PipelineOrderRecord => new PipelineOrderRecord(
                status: (string) $row['status'],
                amountCents: self::eurosToCents((string) ($row['totalAmount'] ?? '0')),
                validUntil: self::toImmutable($row['validUntil']),
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

    private static function eurosToCents(string $amount): int
    {
        return (int) round((float) $amount * 100);
    }
}
