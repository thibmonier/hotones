<?php

declare(strict_types=1);

namespace App\Infrastructure\Project\Persistence\Doctrine;

use App\Domain\Project\Repository\ConversionRateReadModelRepositoryInterface;
use App\Domain\Project\Service\ClientConversionAggregate;
use App\Domain\Project\Service\OrderConversionRecord;
use App\Entity\Client;
use App\Entity\Order;
use App\Entity\Project;
use App\Security\CompanyContext;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

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

    public function findAllClientsAggregated(int $windowDays, DateTimeImmutable $now): array
    {
        if ($windowDays < 1) {
            throw new InvalidArgumentException('Window days must be >= 1');
        }

        $company = $this->companyContext->getCurrentCompany();
        $windowStart = $now->modify(sprintf('-%d days', $windowDays));

        // Order → Project → Client. Statuts contribuant uniquement.
        $rows = $this->entityManager->createQueryBuilder()
            ->select('c.name AS clientName', 'o.status')
            ->from(Order::class, 'o')
            ->innerJoin(Project::class, 'p', 'WITH', 'p.id = o.project')
            ->innerJoin(Client::class, 'c', 'WITH', 'c.id = p.client')
            ->where('o.company = :company')
            ->andWhere('o.status IN (:statuses)')
            ->andWhere('o.createdAt >= :windowStart')
            ->setParameter('company', $company)
            ->setParameter('statuses', [
                OrderConversionRecord::STATUS_CONVERTED_SIGNED,
                OrderConversionRecord::STATUS_CONVERTED_WON,
                OrderConversionRecord::STATUS_FAILED_LOST,
                OrderConversionRecord::STATUS_FAILED_ABANDONED,
            ])
            ->setParameter('windowStart', $windowStart)
            ->getQuery()
            ->getArrayResult();

        $perClient = [];
        foreach ($rows as $row) {
            $name = (string) $row['clientName'];
            $status = (string) $row['status'];

            if (!isset($perClient[$name])) {
                $perClient[$name] = ['emitted' => 0, 'converted' => 0];
            }

            ++$perClient[$name]['emitted'];
            if ($status === OrderConversionRecord::STATUS_CONVERTED_SIGNED
                || $status === OrderConversionRecord::STATUS_CONVERTED_WON) {
                ++$perClient[$name]['converted'];
            }
        }

        $aggregates = [];
        foreach ($perClient as $name => $stats) {
            // emitted >= 1 par construction (entrée créée uniquement si row matchée).
            $rate = $stats['converted'] * 100.0 / $stats['emitted'];
            $aggregates[] = new ClientConversionAggregate(
                clientName: $name,
                ratePercent: round($rate, 1),
                emittedCount: $stats['emitted'],
                convertedCount: $stats['converted'],
            );
        }

        // Tri par taux décroissant (top performers en tête)
        usort(
            $aggregates,
            static fn (ClientConversionAggregate $a, ClientConversionAggregate $b): int => $b->ratePercent <=> $a->ratePercent,
        );

        return $aggregates;
    }

    private static function toImmutable(DateTimeInterface $date): DateTimeImmutable
    {
        return $date instanceof DateTimeImmutable
            ? $date
            : DateTimeImmutable::createFromInterface($date);
    }
}
