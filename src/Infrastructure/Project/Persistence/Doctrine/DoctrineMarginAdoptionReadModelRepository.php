<?php

declare(strict_types=1);

namespace App\Infrastructure\Project\Persistence\Doctrine;

use App\Domain\Project\Repository\MarginAdoptionReadModelRepositoryInterface;
use App\Domain\Project\Service\ClientMarginAdoptionAggregate;
use App\Domain\Project\Service\MarginAdoptionCalculator;
use App\Domain\Project\Service\ProjectMarginSnapshotRecord;
use App\Entity\Client;
use App\Entity\Project;
use App\Security\CompanyContext;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Doctrine adapter for {@see MarginAdoptionReadModelRepositoryInterface}.
 *
 * DQL scalar projection on Project — only id, name, margeCalculatedAt
 * needed by {@see App\Domain\Project\Service\MarginAdoptionCalculator}.
 *
 * Multi-tenant: filters by current company via {@see CompanyContext}.
 * Filtre `status = 'active'` (vs 'completed'/'cancelled') — KPI cible
 * projets en cours uniquement (US-112 ADR-0013 cas 2 indicator).
 *
 * Classification (Fresh / StaleWarning / StaleCritical) déléguée au
 * Calculator. La query Doctrine ne fait PAS de CASE WHEN serveur —
 * approche simple : remonter la liste + classify PHP. Volume attendu
 * faible (quelques dizaines de projets actifs par tenant).
 *
 * EPIC-003 Phase 4 sprint-024 US-112 T-112-02.
 */
final readonly class DoctrineMarginAdoptionReadModelRepository implements MarginAdoptionReadModelRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CompanyContext $companyContext,
    ) {
    }

    public function findActiveWithMarginSnapshot(): array
    {
        $company = $this->companyContext->getCurrentCompany();

        $rows = $this->entityManager->createQueryBuilder()
            ->select('p.id', 'p.name', 'p.margeCalculatedAt')
            ->from(Project::class, 'p')
            ->where('p.company = :company')
            ->andWhere('p.status = :status')
            ->setParameter('company', $company)
            ->setParameter('status', 'active')
            ->getQuery()
            ->getArrayResult();

        return array_map(
            static fn (array $row): ProjectMarginSnapshotRecord => new ProjectMarginSnapshotRecord(
                projectId: (int) $row['id'],
                projectName: (string) $row['name'],
                marginCalculatedAt: $row['margeCalculatedAt'] instanceof DateTimeInterface
                    ? self::toImmutable($row['margeCalculatedAt'])
                    : null,
            ),
            $rows,
        );
    }

    public function findAllClientsAggregated(int $windowDays, DateTimeImmutable $now): array
    {
        // windowDays ignoré (adoption = snapshot, pas fenêtre roulante) ;
        // conservé pour signature cohérente.
        unset($windowDays);

        $company = $this->companyContext->getCurrentCompany();

        $rows = $this->entityManager->createQueryBuilder()
            ->select('c.name AS clientName', 'p.margeCalculatedAt')
            ->from(Project::class, 'p')
            ->innerJoin(Client::class, 'c', 'WITH', 'c.id = p.client')
            ->where('p.company = :company')
            ->andWhere('p.status = :status')
            ->setParameter('company', $company)
            ->setParameter('status', 'active')
            ->getQuery()
            ->getArrayResult();

        $perClient = [];
        foreach ($rows as $row) {
            $name = (string) $row['clientName'];
            $calculatedAt = $row['margeCalculatedAt'] instanceof DateTimeInterface
                ? self::toImmutable($row['margeCalculatedAt'])
                : null;

            if (!isset($perClient[$name])) {
                $perClient[$name] = ['total' => 0, 'fresh' => 0, 'staleCritical' => 0];
            }

            ++$perClient[$name]['total'];

            if ($calculatedAt === null) {
                ++$perClient[$name]['staleCritical'];
                continue;
            }

            $age = ($now->getTimestamp() - $calculatedAt->getTimestamp()) / 86400.0;

            if ($age >= MarginAdoptionCalculator::STALE_WARNING_THRESHOLD_DAYS) {
                ++$perClient[$name]['staleCritical'];
                continue;
            }

            if ($age < MarginAdoptionCalculator::FRESH_THRESHOLD_DAYS) {
                ++$perClient[$name]['fresh'];
            }
        }

        $aggregates = [];
        foreach ($perClient as $name => $stats) {
            // total >= 1 par construction (entrée créée uniquement si row matchée).
            $freshPercent = round($stats['fresh'] * 100.0 / $stats['total'], 1);

            $aggregates[] = new ClientMarginAdoptionAggregate(
                clientName: $name,
                freshPercent: $freshPercent,
                totalActive: $stats['total'],
                freshCount: $stats['fresh'],
                staleCriticalCount: $stats['staleCritical'],
            );
        }

        // Tri par adoption croissante (clients en retard en tête)
        usort(
            $aggregates,
            static fn (ClientMarginAdoptionAggregate $a, ClientMarginAdoptionAggregate $b): int => $a->freshPercent <=> $b->freshPercent,
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
