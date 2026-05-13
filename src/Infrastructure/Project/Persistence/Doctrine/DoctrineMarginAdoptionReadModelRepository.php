<?php

declare(strict_types=1);

namespace App\Infrastructure\Project\Persistence\Doctrine;

use App\Domain\Project\Repository\MarginAdoptionReadModelRepositoryInterface;
use App\Domain\Project\Service\ProjectMarginSnapshotRecord;
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

    private static function toImmutable(DateTimeInterface $date): DateTimeImmutable
    {
        return $date instanceof DateTimeImmutable
            ? $date
            : DateTimeImmutable::createFromInterface($date);
    }
}
