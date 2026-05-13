<?php

declare(strict_types=1);

namespace App\Infrastructure\Project\Persistence\Doctrine;

use App\Domain\Project\Repository\BillingLeadTimeReadModelRepositoryInterface;
use App\Domain\Project\Service\QuoteInvoiceRecord;
use App\Entity\Invoice;
use App\Security\CompanyContext;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

/**
 * Doctrine adapter for {@see BillingLeadTimeReadModelRepositoryInterface}.
 *
 * DQL projection on Invoice ↔ Order (Quote) ↔ Client joints. No full
 * aggregate hydration — only the three columns needed by the calculator
 * + clientId/clientName for top-3-slow aggregation downstream.
 *
 * Multi-tenant: filters by current company via {@see CompanyContext}.
 * Excludes cancelled + draft invoices + un-signed quotes.
 *
 * EPIC-003 Phase 4 sprint-024 US-111 T-111-02.
 */
final readonly class DoctrineBillingLeadTimeReadModelRepository implements BillingLeadTimeReadModelRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CompanyContext $companyContext,
    ) {
    }

    public function findEmittedInRollingWindow(int $windowDays, DateTimeImmutable $now): array
    {
        if ($windowDays < 1) {
            throw new InvalidArgumentException('Window days must be >= 1');
        }

        $windowStart = $now->modify(sprintf('-%d days', $windowDays));
        $company = $this->companyContext->getCurrentCompany();

        $rows = $this->entityManager->createQueryBuilder()
            ->select('o.validatedAt AS signedAt', 'i.issuedAt AS emittedAt', 'c.id AS clientId', 'c.name AS clientName')
            ->from(Invoice::class, 'i')
            ->join('i.order', 'o')
            ->join('i.client', 'c')
            ->where('i.company = :company')
            ->andWhere('o.validatedAt IS NOT NULL')
            ->andWhere('i.issuedAt >= :windowStart')
            ->andWhere('i.status NOT IN (:excluded)')
            ->setParameter('company', $company)
            ->setParameter('windowStart', $windowStart)
            ->setParameter('excluded', [Invoice::STATUS_DRAFT, Invoice::STATUS_CANCELLED])
            ->getQuery()
            ->getArrayResult();

        $records = [];
        foreach ($rows as $row) {
            $signedAt = self::toImmutable($row['signedAt']);
            $emittedAt = self::toImmutable($row['emittedAt']);

            // Defensive : skip rows where emittedAt < signedAt (data anomaly).
            if ($emittedAt < $signedAt) {
                continue;
            }

            $records[] = new QuoteInvoiceRecord(
                signedAt: $signedAt,
                emittedAt: $emittedAt,
                clientId: $row['clientId'],
                clientName: $row['clientName'],
            );
        }

        return $records;
    }

    private static function toImmutable(DateTimeInterface $date): DateTimeImmutable
    {
        return $date instanceof DateTimeImmutable
            ? $date
            : DateTimeImmutable::createFromInterface($date);
    }
}
