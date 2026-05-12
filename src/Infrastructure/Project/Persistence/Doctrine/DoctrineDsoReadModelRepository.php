<?php

declare(strict_types=1);

namespace App\Infrastructure\Project\Persistence\Doctrine;

use App\Domain\Project\Repository\DsoReadModelRepositoryInterface;
use App\Domain\Project\Service\InvoicePaymentRecord;
use App\Entity\Invoice;
use App\Security\CompanyContext;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

/**
 * Doctrine adapter for {@see DsoReadModelRepositoryInterface}.
 *
 * Uses DQL projection (scalar result) to avoid hydrating full Invoice
 * aggregates — DSO calculation only needs issuedAt / paidAt / amountTtc.
 *
 * Multi-tenant: filters by current company via {@see CompanyContext}.
 * Excludes cancelled invoices.
 */
final readonly class DoctrineDsoReadModelRepository implements DsoReadModelRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CompanyContext $companyContext,
    ) {
    }

    public function findPaidInRollingWindow(int $windowDays, DateTimeImmutable $now): array
    {
        if ($windowDays < 1) {
            throw new InvalidArgumentException('Window days must be >= 1');
        }

        $windowStart = $now->modify(sprintf('-%d days', $windowDays));
        $company = $this->companyContext->getCurrentCompany();

        $rows = $this->entityManager->createQueryBuilder()
            ->select('i.issuedAt', 'i.paidAt', 'i.amountTtc')
            ->from(Invoice::class, 'i')
            ->where('i.company = :company')
            ->andWhere('i.paidAt IS NOT NULL')
            ->andWhere('i.paidAt >= :windowStart')
            ->andWhere('i.status != :statusCancelled')
            ->setParameter('company', $company)
            ->setParameter('windowStart', $windowStart)
            ->setParameter('statusCancelled', Invoice::STATUS_CANCELLED)
            ->getQuery()
            ->getArrayResult();

        return array_map(
            static fn (array $row): InvoicePaymentRecord => new InvoicePaymentRecord(
                issuedAt: self::toImmutable($row['issuedAt']),
                paidAt: self::toImmutable($row['paidAt']),
                amountPaidCents: self::eurosToCents((string) $row['amountTtc']),
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

    /**
     * Convert decimal string ("123.45") to cents (12345).
     */
    private static function eurosToCents(string $amount): int
    {
        return (int) round((float) $amount * 100);
    }
}
